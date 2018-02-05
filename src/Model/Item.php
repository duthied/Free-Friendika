<?php

/**
 * @file src/Model/Item.php
 */

namespace Friendica\Model;

use Friendica\BaseObject;
use Friendica\Core\Addon;
use Friendica\Core\Config;
use Friendica\Core\L10n;
use Friendica\Core\PConfig;
use Friendica\Core\System;
use Friendica\Core\Worker;
use Friendica\Database\DBM;
use Friendica\Model\Contact;
use Friendica\Model\Conversation;
use Friendica\Model\GContact;
use Friendica\Model\Group;
use Friendica\Model\Term;
use Friendica\Object\Image;
use Friendica\Protocol\Diaspora;
use Friendica\Protocol\OStatus;
use Friendica\Util\DateTimeFormat;
use dba;
use Text_LanguageDetect;

require_once 'boot.php';
require_once 'include/threads.php';
require_once 'include/items.php';
require_once 'include/text.php';

class Item extends BaseObject
{
	/**
	 * @brief Update existing item entries
	 *
	 * @param array $fields The fields that are to be changed
	 * @param array $condition The condition for finding the item entries
	 *
	 * In the future we may have to change permissions as well.
	 * Then we had to add the user id as third parameter.
	 *
	 * A return value of "0" doesn't mean an error - but that 0 rows had been changed.
	 *
	 * @return integer|boolean number of affected rows - or "false" if there was an error
	 */
	public static function update(array $fields, array $condition)
	{
		if (empty($condition) || empty($fields)) {
			return false;
		}

		$success = dba::update('item', $fields, $condition);

		if (!$success) {
			return false;
		}

		$rows = dba::affected_rows();

		// We cannot simply expand the condition to check for origin entries
		// The condition needn't to be a simple array but could be a complex condition.
		$items = dba::select('item', ['id', 'origin'], $condition);
		while ($item = dba::fetch($items)) {
			// We only need to notfiy others when it is an original entry from us
			if (!$item['origin']) {
				continue;
			}

			Term::insertFromTagFieldByItemId($item['id']);
			Term::insertFromFileFieldByItemId($item['id']);
			self::updateThread($item['id']);

			Worker::add(PRIORITY_HIGH, "Notifier", 'edit_post', $item['id']);
		}

		return $rows;
	}

	/**
	 * @brief Delete an item and notify others about it - if it was ours
	 *
	 * @param integer $item_id Item ID that should be delete
	 *
	 * @return $boolean success
	 */
	public static function delete($item_id, $priority = PRIORITY_HIGH)
	{
		// locate item to be deleted
		$fields = ['id', 'uid', 'parent', 'parent-uri', 'origin', 'deleted', 'file', 'resource-id', 'event-id', 'attach'];
		$item = dba::selectFirst('item', $fields, ['id' => $item_id]);
		if (!DBM::is_result($item)) {
			return false;
		}

		if ($item['deleted']) {
			return false;
		}

		$parent = dba::selectFirst('item', ['origin'], ['id' => $item['parent']]);
		if (!DBM::is_result($parent)) {
			$parent = ['origin' => false];
		}

		logger('delete item: ' . $item['id'], LOGGER_DEBUG);

		// clean up categories and tags so they don't end up as orphans

		$matches = false;
		$cnt = preg_match_all('/<(.*?)>/', $item['file'], $matches, PREG_SET_ORDER);
		if ($cnt) {
			foreach ($matches as $mtch) {
				file_tag_unsave_file($item['uid'], $item['id'], $mtch[1],true);
			}
		}

		$matches = false;

		$cnt = preg_match_all('/\[(.*?)\]/', $item['file'], $matches, PREG_SET_ORDER);
		if ($cnt) {
			foreach ($matches as $mtch) {
				file_tag_unsave_file($item['uid'], $item['id'], $mtch[1],false);
			}
		}

		/*
		 * If item is a link to a photo resource, nuke all the associated photos
		 * (visitors will not have photo resources)
		 * This only applies to photos uploaded from the photos page. Photos inserted into a post do not
		 * generate a resource-id and therefore aren't intimately linked to the item.
		 */
		if (strlen($item['resource-id'])) {
			dba::delete('photo', ['resource-id' => $item['resource-id'], 'uid' => $item['uid']]);
		}

		// If item is a link to an event, nuke the event record.
		if (intval($item['event-id'])) {
			dba::delete('event', ['id' => $item['event-id'], 'uid' => $item['uid']]);
		}

		// If item has attachments, drop them
		foreach (explode(", ", $item['attach']) as $attach) {
			preg_match("|attach/(\d+)|", $attach, $matches);
			dba::delete('attach', ['id' => $matches[1], 'uid' => $item['uid']]);
		}

		// Set the item to "deleted"
		dba::update('item', ['deleted' => true, 'title' => '', 'body' => '',
					'edited' => DateTimeFormat::utcNow(), 'changed' => DateTimeFormat::utcNow()],
				['id' => $item['id']]);

		Term::insertFromTagFieldByItemId($item['id']);
		Term::insertFromFileFieldByItemId($item['id']);
		self::deleteThread($item['id'], $item['parent-uri']);

		// If it's the parent of a comment thread, kill all the kids
		if ($item['id'] == $item['parent']) {
			$items = dba::select('item', ['id'], ['parent' => $item['parent']]);
			while ($row = dba::fetch($items)) {
				self::delete($row['id'], $priority);
			}
		}

		// send the notification upstream/downstream
		if ($item['origin'] || $parent['origin']) {
			Worker::add(['priority' => $priority, 'dont_fork' => true], "Notifier", "drop", intval($item['id']));
		}

		return true;
	}

	public static function insert($arr, $force_parent = false, $notify = false, $dontcache = false)
	{
		$a = get_app();

		// If it is a posting where users should get notifications, then define it as wall posting
		if ($notify) {
			$arr['wall'] = 1;
			$arr['type'] = 'wall';
			$arr['origin'] = 1;
			$arr['network'] = NETWORK_DFRN;
			$arr['protocol'] = PROTOCOL_DFRN;

			// We have to avoid duplicates. So we create the GUID in form of a hash of the plink or uri.
			// In difference to the call to "self::guidFromUri" several lines below we add the hash of our own host.
			// This is done because our host is the original creator of the post.
			if (!isset($arr['guid'])) {
				if (isset($arr['plink'])) {
					$arr['guid'] = self::guidFromUri($arr['plink'], $a->get_hostname());
				} elseif (isset($arr['uri'])) {
					$arr['guid'] = self::guidFromUri($arr['uri'], $a->get_hostname());
				}
			}
		} else {
			$arr['network'] = trim(defaults($arr, 'network', NETWORK_PHANTOM));
		}

		if ($notify) {
			$guid_prefix = "";
		} elseif ((trim($arr['guid']) == "") && (trim($arr['plink']) != "")) {
			$arr['guid'] = self::guidFromUri($arr['plink']);
		} elseif ((trim($arr['guid']) == "") && (trim($arr['uri']) != "")) {
			$arr['guid'] = self::guidFromUri($arr['uri']);
		} else {
			$parsed = parse_url($arr["author-link"]);
			$guid_prefix = hash("crc32", $parsed["host"]);
		}

		$arr['guid'] = notags(trim(defaults($arr, 'guid', get_guid(32, $guid_prefix))));
		$arr['uri'] = notags(trim(defaults($arr, 'uri', item_new_uri($a->get_hostname(), $uid, $arr['guid']))));

		// Store conversation data
		$arr = Conversation::insert($arr);

		/*
		 * If a Diaspora signature structure was passed in, pull it out of the
		 * item array and set it aside for later storage.
		 */

		$dsprsig = null;
		if (x($arr, 'dsprsig')) {
			$encoded_signature = $arr['dsprsig'];
			$dsprsig = json_decode(base64_decode($arr['dsprsig']));
			unset($arr['dsprsig']);
		}

		// Converting the plink
		/// @TODO Check if this is really still needed
		if ($arr['network'] == NETWORK_OSTATUS) {
			if (isset($arr['plink'])) {
				$arr['plink'] = OStatus::convertHref($arr['plink']);
			} elseif (isset($arr['uri'])) {
				$arr['plink'] = OStatus::convertHref($arr['uri']);
			}
		}

		if (x($arr, 'gravity')) {
			$arr['gravity'] = intval($arr['gravity']);
		} elseif ($arr['parent-uri'] === $arr['uri']) {
			$arr['gravity'] = 0;
		} elseif (activity_match($arr['verb'],ACTIVITY_POST)) {
			$arr['gravity'] = 6;
		} else {
			$arr['gravity'] = 6;   // extensible catchall
		}

		$arr['type'] = defaults($arr, 'type', 'remote');

		$uid = intval($arr['uid']);

		// check for create date and expire time
		$expire_interval = Config::get('system', 'dbclean-expire-days', 0);

		$user = dba::selectFirst('user', ['expire'], ['uid' => $uid]);
		if (DBM::is_result($user) && ($user['expire'] > 0) && (($user['expire'] < $expire_interval) || ($expire_interval == 0))) {
			$expire_interval = $user['expire'];
		}

		if (($expire_interval > 0) && !empty($arr['created'])) {
			$expire_date = time() - ($expire_interval * 86400);
			$created_date = strtotime($arr['created']);
			if ($created_date < $expire_date) {
				logger('item-store: item created ('.date('c', $created_date).') before expiration time ('.date('c', $expire_date).'). ignored. ' . print_r($arr,true), LOGGER_DEBUG);
				return 0;
			}
		}

		/*
		 * Do we already have this item?
		 * We have to check several networks since Friendica posts could be repeated
		 * via OStatus (maybe Diasporsa as well)
		 */
		if (in_array($arr['network'], [NETWORK_DIASPORA, NETWORK_DFRN, NETWORK_OSTATUS, ""])) {
			$r = q("SELECT `id`, `network` FROM `item` WHERE `uri` = '%s' AND `uid` = %d AND `network` IN ('%s', '%s', '%s')  LIMIT 1",
					dbesc(trim($arr['uri'])),
					intval($uid),
					dbesc(NETWORK_DIASPORA),
					dbesc(NETWORK_DFRN),
					dbesc(NETWORK_OSTATUS)
				);
			if (DBM::is_result($r)) {
				// We only log the entries with a different user id than 0. Otherwise we would have too many false positives
				if ($uid != 0) {
					logger("Item with uri ".$arr['uri']." already existed for user ".$uid." with id ".$r[0]["id"]." target network ".$r[0]["network"]." - new network: ".$arr['network']);
				}

				return $r[0]["id"];
			}
		}

		self::addLanguageInPostopts($arr);

		$arr['wall']          = intval(defaults($arr, 'wall', 0));
		$arr['extid']         = trim(defaults($arr, 'extid', ''));
		$arr['author-name']   = trim(defaults($arr, 'author-name', ''));
		$arr['author-link']   = trim(defaults($arr, 'author-link', ''));
		$arr['author-avatar'] = trim(defaults($arr, 'author-avatar', ''));
		$arr['owner-name']    = trim(defaults($arr, 'owner-name', ''));
		$arr['owner-link']    = trim(defaults($arr, 'owner-link', ''));
		$arr['owner-avatar']  = trim(defaults($arr, 'owner-avatar', ''));
		$arr['received']      = ((x($arr, 'received') !== false) ? DateTimeFormat::utc($arr['received']) : DateTimeFormat::utcNow());
		$arr['created']       = ((x($arr, 'created') !== false) ? DateTimeFormat::utc($arr['created']) : $arr['received']);
		$arr['edited']        = ((x($arr, 'edited') !== false) ? DateTimeFormat::utc($arr['edited']) : $arr['created']);
		$arr['changed']       = ((x($arr, 'changed') !== false) ? DateTimeFormat::utc($arr['changed']) : $arr['created']);
		$arr['commented']     = ((x($arr, 'commented') !== false) ? DateTimeFormat::utc($arr['commented']) : $arr['created']);
		$arr['title']         = trim(defaults($arr, 'title', ''));
		$arr['location']      = trim(defaults($arr, 'location', ''));
		$arr['coord']         = trim(defaults($arr, 'coord', ''));
		$arr['visible']       = ((x($arr, 'visible') !== false) ? intval($arr['visible'])         : 1);
		$arr['deleted']       = 0;
		$arr['parent-uri']    = trim(defaults($arr, 'parent-uri', $arr['uri']));
		$arr['verb']          = trim(defaults($arr, 'verb', ''));
		$arr['object-type']   = trim(defaults($arr, 'object-type', ''));
		$arr['object']        = trim(defaults($arr, 'object', ''));
		$arr['target-type']   = trim(defaults($arr, 'target-type', ''));
		$arr['target']        = trim(defaults($arr, 'target', ''));
		$arr['plink']         = trim(defaults($arr, 'plink', ''));
		$arr['allow_cid']     = trim(defaults($arr, 'allow_cid', ''));
		$arr['allow_gid']     = trim(defaults($arr, 'allow_gid', ''));
		$arr['deny_cid']      = trim(defaults($arr, 'deny_cid', ''));
		$arr['deny_gid']      = trim(defaults($arr, 'deny_gid', ''));
		$arr['private']       = intval(defaults($arr, 'private', 0));
		$arr['bookmark']      = intval(defaults($arr, 'bookmark', 0));
		$arr['body']          = trim(defaults($arr, 'body', ''));
		$arr['tag']           = trim(defaults($arr, 'tag', ''));
		$arr['attach']        = trim(defaults($arr, 'attach', ''));
		$arr['app']           = trim(defaults($arr, 'app', ''));
		$arr['origin']        = intval(defaults($arr, 'origin', 0));
		$arr['postopts']      = trim(defaults($arr, 'postopts', ''));
		$arr['resource-id']   = trim(defaults($arr, 'resource-id', ''));
		$arr['event-id']      = intval(defaults($arr, 'event-id', 0));
		$arr['inform']        = trim(defaults($arr, 'inform', ''));
		$arr['file']          = trim(defaults($arr, 'file', ''));

		// When there is no content then we don't post it
		if ($arr['body'].$arr['title'] == '') {
			return 0;
		}

		// Items cannot be stored before they happen ...
		if ($arr['created'] > DateTimeFormat::utcNow()) {
			$arr['created'] = DateTimeFormat::utcNow();
		}

		// We haven't invented time travel by now.
		if ($arr['edited'] > DateTimeFormat::utcNow()) {
			$arr['edited'] = DateTimeFormat::utcNow();
		}

		if (($arr['author-link'] == "") && ($arr['owner-link'] == "")) {
			logger("Both author-link and owner-link are empty. Called by: " . System::callstack(), LOGGER_DEBUG);
		}

		if ($arr['plink'] == "") {
			$arr['plink'] = System::baseUrl() . '/display/' . urlencode($arr['guid']);
		}

		if ($arr['network'] == NETWORK_PHANTOM) {
			$r = q("SELECT `network` FROM `contact` WHERE `network` IN ('%s', '%s', '%s') AND `nurl` = '%s' AND `uid` = %d LIMIT 1",
				dbesc(NETWORK_DFRN), dbesc(NETWORK_DIASPORA), dbesc(NETWORK_OSTATUS),
				dbesc(normalise_link($arr['author-link'])),
				intval($arr['uid'])
			);

			if (!DBM::is_result($r)) {
				$r = q("SELECT `network` FROM `gcontact` WHERE `network` IN ('%s', '%s', '%s') AND `nurl` = '%s' LIMIT 1",
					dbesc(NETWORK_DFRN), dbesc(NETWORK_DIASPORA), dbesc(NETWORK_OSTATUS),
					dbesc(normalise_link($arr['author-link']))
				);
			}

			if (!DBM::is_result($r)) {
				$r = q("SELECT `network` FROM `contact` WHERE `id` = %d AND `uid` = %d LIMIT 1",
					intval($arr['contact-id']),
					intval($arr['uid'])
				);
			}

			if (DBM::is_result($r)) {
				$arr['network'] = $r[0]["network"];
			}

			// Fallback to friendica (why is it empty in some cases?)
			if ($arr['network'] == "") {
				$arr['network'] = NETWORK_DFRN;
			}

			logger("Set network to " . $arr["network"] . " for " . $arr["uri"], LOGGER_DEBUG);
		}

		// The contact-id should be set before "self::insert" was called - but there seems to be some issues
		if ($arr["contact-id"] == 0) {
			/*
			 * First we are looking for a suitable contact that matches with the author of the post
			 * This is done only for comments (See below explanation at "gcontact-id")
			 */
			if ($arr['parent-uri'] != $arr['uri']) {
				$arr["contact-id"] = Contact::getIdForURL($arr['author-link'], $uid);
			}

			// If not present then maybe the owner was found
			if ($arr["contact-id"] == 0) {
				$arr["contact-id"] = Contact::getIdForURL($arr['owner-link'], $uid);
			}

			// Still missing? Then use the "self" contact of the current user
			if ($arr["contact-id"] == 0) {
				$r = q("SELECT `id` FROM `contact` WHERE `self` AND `uid` = %d", intval($uid));

				if (DBM::is_result($r)) {
					$arr["contact-id"] = $r[0]["id"];
				}
			}

			logger("Contact-id was missing for post ".$arr["guid"]." from user id ".$uid." - now set to ".$arr["contact-id"], LOGGER_DEBUG);
		}

		if (!x($arr, "gcontact-id")) {
			/*
			 * The gcontact should mostly behave like the contact. But is is supposed to be global for the system.
			 * This means that wall posts, repeated posts, etc. should have the gcontact id of the owner.
			 * On comments the author is the better choice.
			 */
			if ($arr['parent-uri'] === $arr['uri']) {
				$arr["gcontact-id"] = GContact::getId(["url" => $arr['owner-link'], "network" => $arr['network'],
									 "photo" => $arr['owner-avatar'], "name" => $arr['owner-name']]);
			} else {
				$arr["gcontact-id"] = GContact::getId(["url" => $arr['author-link'], "network" => $arr['network'],
									 "photo" => $arr['author-avatar'], "name" => $arr['author-name']]);
			}
		}

		if ($arr["author-id"] == 0) {
			$arr["author-id"] = Contact::getIdForURL($arr["author-link"], 0);
		}

		if (Contact::isBlocked($arr["author-id"])) {
			logger('Contact '.$arr["author-id"].' is blocked, item '.$arr["uri"].' will not be stored');
			return 0;
		}

		if ($arr["owner-id"] == 0) {
			$arr["owner-id"] = Contact::getIdForURL($arr["owner-link"], 0);
		}

		if (Contact::isBlocked($arr["owner-id"])) {
			logger('Contact '.$arr["owner-id"].' is blocked, item '.$arr["uri"].' will not be stored');
			return 0;
		}

		if ($arr['guid'] != "") {
			// Checking if there is already an item with the same guid
			logger('checking for an item for user '.$arr['uid'].' on network '.$arr['network'].' with the guid '.$arr['guid'], LOGGER_DEBUG);
			$r = q("SELECT `guid` FROM `item` WHERE `guid` = '%s' AND `network` = '%s' AND `uid` = '%d' LIMIT 1",
				dbesc($arr['guid']), dbesc($arr['network']), intval($arr['uid']));

			if (DBM::is_result($r)) {
				logger('found item with guid '.$arr['guid'].' for user '.$arr['uid'].' on network '.$arr['network'], LOGGER_DEBUG);
				return 0;
			}
		}

		// Check for hashtags in the body and repair or add hashtag links
		self::setHashtags($arr);

		$arr['thr-parent'] = $arr['parent-uri'];

		if ($arr['parent-uri'] === $arr['uri']) {
			$parent_id = 0;
			$parent_deleted = 0;
			$allow_cid = $arr['allow_cid'];
			$allow_gid = $arr['allow_gid'];
			$deny_cid  = $arr['deny_cid'];
			$deny_gid  = $arr['deny_gid'];
			$notify_type = 'wall-new';
		} else {

			// find the parent and snarf the item id and ACLs
			// and anything else we need to inherit

			$r = q("SELECT * FROM `item` WHERE `uri` = '%s' AND `uid` = %d ORDER BY `id` ASC LIMIT 1",
				dbesc($arr['parent-uri']),
				intval($arr['uid'])
			);

			if (DBM::is_result($r)) {

				// is the new message multi-level threaded?
				// even though we don't support it now, preserve the info
				// and re-attach to the conversation parent.

				if ($r[0]['uri'] != $r[0]['parent-uri']) {
					$arr['parent-uri'] = $r[0]['parent-uri'];
					$z = q("SELECT * FROM `item` WHERE `uri` = '%s' AND `parent-uri` = '%s' AND `uid` = %d
						ORDER BY `id` ASC LIMIT 1",
						dbesc($r[0]['parent-uri']),
						dbesc($r[0]['parent-uri']),
						intval($arr['uid'])
					);

					if (DBM::is_result($z)) {
						$r = $z;
					}
				}

				$parent_id      = $r[0]['id'];
				$parent_deleted = $r[0]['deleted'];
				$allow_cid      = $r[0]['allow_cid'];
				$allow_gid      = $r[0]['allow_gid'];
				$deny_cid       = $r[0]['deny_cid'];
				$deny_gid       = $r[0]['deny_gid'];
				$arr['wall']    = $r[0]['wall'];
				$notify_type    = 'comment-new';

				/*
				 * If the parent is private, force privacy for the entire conversation
				 * This differs from the above settings as it subtly allows comments from
				 * email correspondents to be private even if the overall thread is not.
				 */
				if ($r[0]['private']) {
					$arr['private'] = $r[0]['private'];
				}

				/*
				 * Edge case. We host a public forum that was originally posted to privately.
				 * The original author commented, but as this is a comment, the permissions
				 * weren't fixed up so it will still show the comment as private unless we fix it here.
				 */
				if ((intval($r[0]['forum_mode']) == 1) && $r[0]['private']) {
					$arr['private'] = 0;
				}

				// If its a post from myself then tag the thread as "mention"
				logger("Checking if parent ".$parent_id." has to be tagged as mention for user ".$arr['uid'], LOGGER_DEBUG);
				$u = q("SELECT `nickname` FROM `user` WHERE `uid` = %d", intval($arr['uid']));
				if (DBM::is_result($u)) {
					$self = normalise_link(System::baseUrl() . '/profile/' . $u[0]['nickname']);
					logger("'myself' is ".$self." for parent ".$parent_id." checking against ".$arr['author-link']." and ".$arr['owner-link'], LOGGER_DEBUG);
					if ((normalise_link($arr['author-link']) == $self) || (normalise_link($arr['owner-link']) == $self)) {
						dba::update('thread', ['mention' => true], ['iid' => $parent_id]);
						logger("tagged thread ".$parent_id." as mention for user ".$self, LOGGER_DEBUG);
					}
				}
			} else {
				/*
				 * Allow one to see reply tweets from status.net even when
				 * we don't have or can't see the original post.
				 */
				if ($force_parent) {
					logger('$force_parent=true, reply converted to top-level post.');
					$parent_id = 0;
					$arr['parent-uri'] = $arr['uri'];
					$arr['gravity'] = 0;
				} else {
					logger('item parent '.$arr['parent-uri'].' for '.$arr['uid'].' was not found - ignoring item');
					return 0;
				}

				$parent_deleted = 0;
			}
		}

		$r = q("SELECT `id` FROM `item` WHERE `uri` = '%s' AND `network` IN ('%s', '%s') AND `uid` = %d LIMIT 1",
			dbesc($arr['uri']),
			dbesc($arr['network']),
			dbesc(NETWORK_DFRN),
			intval($arr['uid'])
		);
		if (DBM::is_result($r)) {
			logger('duplicated item with the same uri found. '.print_r($arr,true));
			return 0;
		}

		// On Friendica and Diaspora the GUID is unique
		if (in_array($arr['network'], [NETWORK_DFRN, NETWORK_DIASPORA])) {
			$r = q("SELECT `id` FROM `item` WHERE `guid` = '%s' AND `uid` = %d LIMIT 1",
				dbesc($arr['guid']),
				intval($arr['uid'])
			);
			if (DBM::is_result($r)) {
				logger('duplicated item with the same guid found. '.print_r($arr,true));
				return 0;
			}
		} else {
			// Check for an existing post with the same content. There seems to be a problem with OStatus.
			$r = q("SELECT `id` FROM `item` WHERE `body` = '%s' AND `network` = '%s' AND `created` = '%s' AND `contact-id` = %d AND `uid` = %d LIMIT 1",
				dbesc($arr['body']),
				dbesc($arr['network']),
				dbesc($arr['created']),
				intval($arr['contact-id']),
				intval($arr['uid'])
			);
			if (DBM::is_result($r)) {
				logger('duplicated item with the same body found. '.print_r($arr,true));
				return 0;
			}
		}

		// Is this item available in the global items (with uid=0)?
		if ($arr["uid"] == 0) {
			$arr["global"] = true;

			// Set the global flag on all items if this was a global item entry
			dba::update('item', ['global' => true], ['uri' => $arr["uri"]]);
		} else {
			$isglobal = q("SELECT `global` FROM `item` WHERE `uid` = 0 AND `uri` = '%s'", dbesc($arr["uri"]));

			$arr["global"] = (DBM::is_result($isglobal) && count($isglobal) > 0);
		}

		// ACL settings
		if (strlen($allow_cid) || strlen($allow_gid) || strlen($deny_cid) || strlen($deny_gid)) {
			$private = 1;
		} else {
			$private = $arr['private'];
		}

		$arr["allow_cid"] = $allow_cid;
		$arr["allow_gid"] = $allow_gid;
		$arr["deny_cid"] = $deny_cid;
		$arr["deny_gid"] = $deny_gid;
		$arr["private"] = $private;
		$arr["deleted"] = $parent_deleted;

		// Fill the cache field
		put_item_in_cache($arr);

		if ($notify) {
			Addon::callHooks('post_local', $arr);
		} else {
			Addon::callHooks('post_remote', $arr);
		}

		// This array field is used to trigger some automatic reactions
		// It is mainly used in the "post_local" hook.
		unset($arr['api_source']);

		if (x($arr, 'cancel')) {
			logger('post cancelled by addon.');
			return 0;
		}

		/*
		 * Check for already added items.
		 * There is a timing issue here that sometimes creates double postings.
		 * An unique index would help - but the limitations of MySQL (maximum size of index values) prevent this.
		 */
		if ($arr["uid"] == 0) {
			$r = q("SELECT `id` FROM `item` WHERE `uri` = '%s' AND `uid` = 0 LIMIT 1", dbesc(trim($arr['uri'])));
			if (DBM::is_result($r)) {
				logger('Global item already stored. URI: '.$arr['uri'].' on network '.$arr['network'], LOGGER_DEBUG);
				return 0;
			}
		}

		logger('' . print_r($arr,true), LOGGER_DATA);

		dba::transaction();
		$r = dba::insert('item', $arr);

		// When the item was successfully stored we fetch the ID of the item.
		if (DBM::is_result($r)) {
			$current_post = dba::lastInsertId();
		} else {
			// This can happen - for example - if there are locking timeouts.
			dba::rollback();

			// Store the data into a spool file so that we can try again later.

			// At first we restore the Diaspora signature that we removed above.
			if (isset($encoded_signature)) {
				$arr['dsprsig'] = $encoded_signature;
			}

			// Now we store the data in the spool directory
			// We use "microtime" to keep the arrival order and "mt_rand" to avoid duplicates
			$file = 'item-'.round(microtime(true) * 10000).'-'.mt_rand().'.msg';

			$spoolpath = get_spoolpath();
			if ($spoolpath != "") {
				$spool = $spoolpath.'/'.$file;
				file_put_contents($spool, json_encode($arr));
				logger("Item wasn't stored - Item was spooled into file ".$file, LOGGER_DEBUG);
			}
			return 0;
		}

		if ($current_post == 0) {
			// This is one of these error messages that never should occur.
			logger("couldn't find created item - we better quit now.");
			dba::rollback();
			return 0;
		}

		// How much entries have we created?
		// We wouldn't need this query when we could use an unique index - but MySQL has length problems with them.
		$r = q("SELECT COUNT(*) AS `entries` FROM `item` WHERE `uri` = '%s' AND `uid` = %d AND `network` = '%s'",
			dbesc($arr['uri']),
			intval($arr['uid']),
			dbesc($arr['network'])
		);

		if (!DBM::is_result($r)) {
			// This shouldn't happen, since COUNT always works when the database connection is there.
			logger("We couldn't count the stored entries. Very strange ...");
			dba::rollback();
			return 0;
		}

		if ($r[0]["entries"] > 1) {
			// There are duplicates. We delete our just created entry.
			logger('Duplicated post occurred. uri = ' . $arr['uri'] . ' uid = ' . $arr['uid']);

			// Yes, we could do a rollback here - but we are having many users with MyISAM.
			dba::delete('item', ['id' => $current_post]);
			dba::commit();
			return 0;
		} elseif ($r[0]["entries"] == 0) {
			// This really should never happen since we quit earlier if there were problems.
			logger("Something is terribly wrong. We haven't found our created entry.");
			dba::rollback();
			return 0;
		}

		logger('created item '.$current_post);
		self::updateContact($arr);

		if (!$parent_id || ($arr['parent-uri'] === $arr['uri'])) {
			$parent_id = $current_post;
		}

		// Set parent id
		dba::update('item', ['parent' => $parent_id], ['id' => $current_post]);

		$arr['id'] = $current_post;
		$arr['parent'] = $parent_id;

		// update the commented timestamp on the parent
		// Only update "commented" if it is really a comment
		if (($arr['verb'] == ACTIVITY_POST) || !Config::get("system", "like_no_comment")) {
			dba::update('item', ['commented' => DateTimeFormat::utcNow(), 'changed' => DateTimeFormat::utcNow()], ['id' => $parent_id]);
		} else {
			dba::update('item', ['changed' => DateTimeFormat::utcNow()], ['id' => $parent_id]);
		}

		if ($dsprsig) {
			/*
			 * Friendica servers lower than 3.4.3-2 had double encoded the signature ...
			 * We can check for this condition when we decode and encode the stuff again.
			 */
			if (base64_encode(base64_decode(base64_decode($dsprsig->signature))) == base64_decode($dsprsig->signature)) {
				$dsprsig->signature = base64_decode($dsprsig->signature);
				logger("Repaired double encoded signature from handle ".$dsprsig->signer, LOGGER_DEBUG);
			}

			dba::insert('sign', ['iid' => $current_post, 'signed_text' => $dsprsig->signed_text,
						'signature' => $dsprsig->signature, 'signer' => $dsprsig->signer]);
		}

		$deleted = self::tagDeliver($arr['uid'], $current_post);

		/*
		 * current post can be deleted if is for a community page and no mention are
		 * in it.
		 */
		if (!$deleted && !$dontcache) {
			$r = q('SELECT * FROM `item` WHERE `id` = %d', intval($current_post));
			if (DBM::is_result($r) && (count($r) == 1)) {
				if ($notify) {
					Addon::callHooks('post_local_end', $r[0]);
				} else {
					Addon::callHooks('post_remote_end', $r[0]);
				}
			} else {
				logger('new item not found in DB, id ' . $current_post);
			}
		}

		if ($arr['parent-uri'] === $arr['uri']) {
			self::addThread($current_post);
		} else {
			self::updateThread($parent_id);
		}

		dba::commit();

		/*
		 * Due to deadlock issues with the "term" table we are doing these steps after the commit.
		 * This is not perfect - but a workable solution until we found the reason for the problem.
		 */
		Term::insertFromTagFieldByItemId($current_post);
		Term::insertFromFileFieldByItemId($current_post);

		if ($arr['parent-uri'] === $arr['uri']) {
			self::addShadow($current_post);
		} else {
			self::addShadowPost($current_post);
		}

		check_user_notification($current_post);

		if ($notify) {
			Worker::add(['priority' => PRIORITY_HIGH, 'dont_fork' => true], "Notifier", $notify_type, $current_post);
		}

		return $current_post;
	}

	/**
	 * @brief Add a shadow entry for a given item id that is a thread starter
	 *
	 * We store every public item entry additionally with the user id "0".
	 * This is used for the community page and for the search.
	 * It is planned that in the future we will store public item entries only once.
	 *
	 * @param integer $itemid Item ID that should be added
	 */
	public static function addShadow($itemid)
	{
		$fields = ['uid', 'wall', 'private', 'moderated', 'visible', 'contact-id', 'deleted', 'network', 'author-id', 'owner-id'];
		$condition = ["`id` = ? AND (`parent` = ? OR `parent` = 0)", $itemid, $itemid];
		$item = dba::selectFirst('item', $fields, $condition);

		if (!DBM::is_result($item)) {
			return;
		}

		// is it already a copy?
		if (($itemid == 0) || ($item['uid'] == 0)) {
			return;
		}

		// Is it a visible public post?
		if (!$item["visible"] || $item["deleted"] || $item["moderated"] || $item["private"]) {
			return;
		}

		// is it an entry from a connector? Only add an entry for natively connected networks
		if (!in_array($item["network"], [NETWORK_DFRN, NETWORK_DIASPORA, NETWORK_OSTATUS, ""])) {
			return;
		}

		// Is the public contact configured as hidden?
		if (Contact::isHidden($item["owner-id"]) || Contact::isHidden($item["author-id"])) {
			return;
		}

		// Only do these checks if the post isn't a wall post
		if (!$item["wall"]) {
			// Check, if hide-friends is activated - then don't do a shadow entry
			if (dba::exists('profile', ['is-default' => true, 'uid' => $item['uid'], 'hide-friends' => true])) {
				return;
			}

			// Check if the contact is hidden or blocked
			if (!dba::exists('contact', ['hidden' => false, 'blocked' => false, 'id' => $item['contact-id']])) {
				return;
			}
		}

		// Only add a shadow, if the profile isn't hidden
		if (dba::exists('user', ['uid' => $item['uid'], 'hidewall' => true])) {
			return;
		}

		$item = dba::selectFirst('item', [], ['id' => $itemid]);

		if (DBM::is_result($item) && ($item["allow_cid"] == '')  && ($item["allow_gid"] == '') &&
			($item["deny_cid"] == '') && ($item["deny_gid"] == '')) {

			if (!dba::exists('item', ['uri' => $item['uri'], 'uid' => 0])) {
				// Preparing public shadow (removing user specific data)
				unset($item['id']);
				$item['uid'] = 0;
				$item['origin'] = 0;
				$item['wall'] = 0;
				$item['contact-id'] = Contact::getIdForURL($item['author-link'], 0);

				if (in_array($item['type'], ["net-comment", "wall-comment"])) {
					$item['type'] = 'remote-comment';
				} elseif ($item['type'] == 'wall') {
					$item['type'] = 'remote';
				}

				$public_shadow = self::insert($item, false, false, true);

				logger("Stored public shadow for thread ".$itemid." under id ".$public_shadow, LOGGER_DEBUG);
			}
		}
	}

	/**
	 * @brief Add a shadow entry for a given item id that is a comment
	 *
	 * This function does the same like the function above - but for comments
	 *
	 * @param integer $itemid Item ID that should be added
	 */
	public static function addShadowPost($itemid)
	{
		$item = dba::selectFirst('item', [], ['id' => $itemid]);
		if (!DBM::is_result($item)) {
			return;
		}

		// Is it a toplevel post?
		if ($item['id'] == $item['parent']) {
			self::addShadow($itemid);
			return;
		}

		// Is this a shadow entry?
		if ($item['uid'] == 0)
			return;

		// Is there a shadow parent?
		if (!dba::exists('item', ['uri' => $item['parent-uri'], 'uid' => 0])) {
			return;
		}

		// Is there already a shadow entry?
		if (dba::exists('item', ['uri' => $item['uri'], 'uid' => 0])) {
			return;
		}

		// Preparing public shadow (removing user specific data)
		unset($item['id']);
		$item['uid'] = 0;
		$item['origin'] = 0;
		$item['wall'] = 0;
		$item['contact-id'] = Contact::getIdForURL($item['author-link'], 0);

		if (in_array($item['type'], ["net-comment", "wall-comment"])) {
			$item['type'] = 'remote-comment';
		} elseif ($item['type'] == 'wall') {
			$item['type'] = 'remote';
		}

		$public_shadow = self::insert($item, false, false, true);

		logger("Stored public shadow for comment ".$item['uri']." under id ".$public_shadow, LOGGER_DEBUG);
	}

	 /**
	 * Adds a "lang" specification in a "postopts" element of given $arr,
	 * if possible and not already present.
	 * Expects "body" element to exist in $arr.
	 */
	private static function addLanguageInPostopts(&$arr)
	{
		if (x($arr, 'postopts')) {
			if (strstr($arr['postopts'], 'lang=')) {
				// do not override
				return;
			}
			$postopts = $arr['postopts'];
		} else {
			$postopts = "";
		}

		$naked_body = preg_replace('/\[(.+?)\]/','', $arr['body']);
		$l = new Text_LanguageDetect();
		$lng = $l->detect($naked_body, 3);

		if (sizeof($lng) > 0) {
			if ($postopts != "") {
				$postopts .= '&'; // arbitrary separator, to be reviewed
			}

			$postopts .= 'lang=';
			$sep = "";

			foreach ($lng as $language => $score) {
				$postopts .= $sep . $language . ";" . $score;
				$sep = ':';
			}
			$arr['postopts'] = $postopts;
		}
	}

	/**
	 * @brief Creates an unique guid out of a given uri
	 *
	 * @param string $uri uri of an item entry
	 * @param string $host (Optional) hostname for the GUID prefix
	 * @return string unique guid
	 */
	public static function guidFromUri($uri, $host = "")
	{
		// Our regular guid routine is using this kind of prefix as well
		// We have to avoid that different routines could accidentally create the same value
		$parsed = parse_url($uri);

		// When the hostname isn't given, we take it from the uri
		if ($host == "") {
			// Is it in the format data@host.tld?
			if ((count($parsed) == 1) && strstr($uri, '@')) {
				$mailparts = explode('@', $uri);
				$host = array_pop($mailparts);
			} else {
				$host = $parsed["host"];
			}
		}

		// We use a hash of the hostname as prefix for the guid
		$guid_prefix = hash("crc32", $host);

		// Remove the scheme to make sure that "https" and "http" doesn't make a difference
		unset($parsed["scheme"]);

		// Glue it together to be able to make a hash from it
		$host_id = implode("/", $parsed);

		// We could use any hash algorithm since it isn't a security issue
		$host_hash = hash("ripemd128", $host_id);

		return $guid_prefix.$host_hash;
	}

	/**
	 * @brief Set "success_update" and "last-item" to the date of the last time we heard from this contact
	 *
	 * This can be used to filter for inactive contacts.
	 * Only do this for public postings to avoid privacy problems, since poco data is public.
	 * Don't set this value if it isn't from the owner (could be an author that we don't know)
	 *
	 * @param array $arr Contains the just posted item record
	 */
	private static function updateContact($arr)
	{
		// Unarchive the author
		$contact = dba::selectFirst('contact', [], ['id' => $arr["author-link"]]);
		if ($contact['term-date'] > NULL_DATE) {
			 Contact::unmarkForArchival($contact);
		}

		// Unarchive the contact if it is a toplevel posting
		if ($arr["parent-uri"] === $arr["uri"]) {
			$contact = dba::selectFirst('contact', [], ['id' => $arr["contact-id"]]);
			if ($contact['term-date'] > NULL_DATE) {
				 Contact::unmarkForArchival($contact);
			}
		}

		$update = (!$arr['private'] && (($arr["author-link"] === $arr["owner-link"]) || ($arr["parent-uri"] === $arr["uri"])));

		// Is it a forum? Then we don't care about the rules from above
		if (!$update && ($arr["network"] == NETWORK_DFRN) && ($arr["parent-uri"] === $arr["uri"])) {
			$isforum = q("SELECT `forum` FROM `contact` WHERE `id` = %d AND `forum`",
					intval($arr['contact-id']));
			if (DBM::is_result($isforum)) {
				$update = true;
			}
		}

		if ($update) {
			dba::update('contact', ['success_update' => $arr['received'], 'last-item' => $arr['received']],
				['id' => $arr['contact-id']]);
		}
		// Now do the same for the system wide contacts with uid=0
		if (!$arr['private']) {
			dba::update('contact', ['success_update' => $arr['received'], 'last-item' => $arr['received']],
				['id' => $arr['owner-id']]);

			if ($arr['owner-id'] != $arr['author-id']) {
				dba::update('contact', ['success_update' => $arr['received'], 'last-item' => $arr['received']],
					['id' => $arr['author-id']]);
			}
		}
	}

	private static function setHashtags(&$item)
	{

		$tags = get_tags($item["body"]);

		// No hashtags?
		if (!count($tags)) {
			return false;
		}

		// This sorting is important when there are hashtags that are part of other hashtags
		// Otherwise there could be problems with hashtags like #test and #test2
		rsort($tags);

		$URLSearchString = "^\[\]";

		// All hashtags should point to the home server if "local_tags" is activated
		if (Config::get('system', 'local_tags')) {
			$item["body"] = preg_replace("/#\[url\=([$URLSearchString]*)\](.*?)\[\/url\]/ism",
					"#[url=".System::baseUrl()."/search?tag=$2]$2[/url]", $item["body"]);

			$item["tag"] = preg_replace("/#\[url\=([$URLSearchString]*)\](.*?)\[\/url\]/ism",
					"#[url=".System::baseUrl()."/search?tag=$2]$2[/url]", $item["tag"]);
		}

		// mask hashtags inside of url, bookmarks and attachments to avoid urls in urls
		$item["body"] = preg_replace_callback("/\[url\=([$URLSearchString]*)\](.*?)\[\/url\]/ism",
			function ($match) {
				return ("[url=" . str_replace("#", "&num;", $match[1]) . "]" . str_replace("#", "&num;", $match[2]) . "[/url]");
			}, $item["body"]);

		$item["body"] = preg_replace_callback("/\[bookmark\=([$URLSearchString]*)\](.*?)\[\/bookmark\]/ism",
			function ($match) {
				return ("[bookmark=" . str_replace("#", "&num;", $match[1]) . "]" . str_replace("#", "&num;", $match[2]) . "[/bookmark]");
			}, $item["body"]);

		$item["body"] = preg_replace_callback("/\[attachment (.*)\](.*?)\[\/attachment\]/ism",
			function ($match) {
				return ("[attachment " . str_replace("#", "&num;", $match[1]) . "]" . $match[2] . "[/attachment]");
			}, $item["body"]);

		// Repair recursive urls
		$item["body"] = preg_replace("/&num;\[url\=([$URLSearchString]*)\](.*?)\[\/url\]/ism",
				"&num;$2", $item["body"]);

		foreach ($tags as $tag) {
			if ((strpos($tag, '#') !== 0) || strpos($tag, '[url=')) {
				continue;
			}

			$basetag = str_replace('_',' ',substr($tag,1));

			$newtag = '#[url=' . System::baseUrl() . '/search?tag=' . rawurlencode($basetag) . ']' . $basetag . '[/url]';

			$item["body"] = str_replace($tag, $newtag, $item["body"]);

			if (!stristr($item["tag"], "/search?tag=" . $basetag . "]" . $basetag . "[/url]")) {
				if (strlen($item["tag"])) {
					$item["tag"] = ','.$item["tag"];
				}
				$item["tag"] = $newtag.$item["tag"];
			}
		}

		// Convert back the masked hashtags
		$item["body"] = str_replace("&num;", "#", $item["body"]);
	}

	public static function getGuidById($id)
	{
		$r = q("SELECT `guid` FROM `item` WHERE `id` = %d LIMIT 1", intval($id));
		if (DBM::is_result($r)) {
			return $r[0]["guid"];
		} else {
			return "";
		}
	}

	public static function getIdAndNickByGuid($guid, $uid = 0)
	{
		$nick = "";
		$id = 0;

		if ($uid == 0) {
			$uid == local_user();
		}

		// Does the given user have this item?
		if ($uid) {
			$r = q("SELECT `item`.`id`, `user`.`nickname` FROM `item` INNER JOIN `user` ON `user`.`uid` = `item`.`uid`
				WHERE `item`.`visible` = 1 AND `item`.`deleted` = 0 AND `item`.`moderated` = 0
					AND `item`.`guid` = '%s' AND `item`.`uid` = %d", dbesc($guid), intval($uid));
			if (DBM::is_result($r)) {
				$id = $r[0]["id"];
				$nick = $r[0]["nickname"];
			}
		}

		// Or is it anywhere on the server?
		if ($nick == "") {
			$r = q("SELECT `item`.`id`, `user`.`nickname` FROM `item` INNER JOIN `user` ON `user`.`uid` = `item`.`uid`
				WHERE `item`.`visible` = 1 AND `item`.`deleted` = 0 AND `item`.`moderated` = 0
					AND `item`.`allow_cid` = ''  AND `item`.`allow_gid` = ''
					AND `item`.`deny_cid`  = '' AND `item`.`deny_gid`  = ''
					AND `item`.`private` = 0 AND `item`.`wall` = 1
					AND `item`.`guid` = '%s'", dbesc($guid));
			if (DBM::is_result($r)) {
				$id = $r[0]["id"];
				$nick = $r[0]["nickname"];
			}
		}
		return ["nick" => $nick, "id" => $id];
	}

	/**
	 * look for mention tags and setup a second delivery chain for forum/community posts if appropriate
	 * @param int $uid
	 * @param int $item_id
	 * @return bool true if item was deleted, else false
	 */
	private static function tagDeliver($uid, $item_id)
	{
		$mention = false;

		$u = q("SELECT * FROM `user` WHERE `uid` = %d LIMIT 1",
			intval($uid)
		);
		if (!DBM::is_result($u)) {
			return;
		}

		$community_page = (($u[0]['page-flags'] == PAGE_COMMUNITY) ? true : false);
		$prvgroup = (($u[0]['page-flags'] == PAGE_PRVGROUP) ? true : false);

		$i = q("SELECT * FROM `item` WHERE `id` = %d AND `uid` = %d LIMIT 1",
			intval($item_id),
			intval($uid)
		);
		if (!DBM::is_result($i)) {
			return;
		}

		$item = $i[0];

		$link = normalise_link(System::baseUrl() . '/profile/' . $u[0]['nickname']);

		/*
		 * Diaspora uses their own hardwired link URL in @-tags
		 * instead of the one we supply with webfinger
		 */
		$dlink = normalise_link(System::baseUrl() . '/u/' . $u[0]['nickname']);

		$cnt = preg_match_all('/[\@\!]\[url\=(.*?)\](.*?)\[\/url\]/ism', $item['body'], $matches, PREG_SET_ORDER);
		if ($cnt) {
			foreach ($matches as $mtch) {
				if (link_compare($link, $mtch[1]) || link_compare($dlink, $mtch[1])) {
					$mention = true;
					logger('mention found: ' . $mtch[2]);
				}
			}
		}

		if (!$mention) {
			if (($community_page || $prvgroup) &&
				  !$item['wall'] && !$item['origin'] && ($item['id'] == $item['parent'])) {
				// mmh.. no mention.. community page or private group... no wall.. no origin.. top-post (not a comment)
				// delete it!
				logger("no-mention top-level post to communuty or private group. delete.");
				dba::delete('item', ['id' => $item_id]);
				return true;
			}
			return;
		}

		$arr = ['item' => $item, 'user' => $u[0], 'contact' => $r[0]];

		Addon::callHooks('tagged', $arr);

		if (!$community_page && !$prvgroup) {
			return;
		}

		/*
		 * tgroup delivery - setup a second delivery chain
		 * prevent delivery looping - only proceed
		 * if the message originated elsewhere and is a top-level post
		 */
		if ($item['wall'] || $item['origin'] || ($item['id'] != $item['parent'])) {
			return;
		}

		// now change this copy of the post to a forum head message and deliver to all the tgroup members
		$c = q("SELECT `name`, `url`, `thumb` FROM `contact` WHERE `self` = 1 AND `uid` = %d LIMIT 1",
			intval($u[0]['uid'])
		);
		if (!DBM::is_result($c)) {
			return;
		}

		// also reset all the privacy bits to the forum default permissions

		$private = ($u[0]['allow_cid'] || $u[0]['allow_gid'] || $u[0]['deny_cid'] || $u[0]['deny_gid']) ? 1 : 0;

		$forum_mode = (($prvgroup) ? 2 : 1);

		q("UPDATE `item` SET `wall` = 1, `origin` = 1, `forum_mode` = %d, `owner-name` = '%s', `owner-link` = '%s', `owner-avatar` = '%s',
			`private` = %d, `allow_cid` = '%s', `allow_gid` = '%s', `deny_cid` = '%s', `deny_gid` = '%s'  WHERE `id` = %d",
			intval($forum_mode),
			dbesc($c[0]['name']),
			dbesc($c[0]['url']),
			dbesc($c[0]['thumb']),
			intval($private),
			dbesc($u[0]['allow_cid']),
			dbesc($u[0]['allow_gid']),
			dbesc($u[0]['deny_cid']),
			dbesc($u[0]['deny_gid']),
			intval($item_id)
		);
		self::updateThread($item_id);

		Worker::add(['priority' => PRIORITY_HIGH, 'dont_fork' => true], 'Notifier', 'tgroup', $item_id);

	}

	public static function isRemoteSelf($contact, &$datarray)
	{
		$a = get_app();

		if (!$contact['remote_self']) {
			return false;
		}

		// Prevent the forwarding of posts that are forwarded
		if ($datarray["extid"] == NETWORK_DFRN) {
			return false;
		}

		// Prevent to forward already forwarded posts
		if ($datarray["app"] == $a->get_hostname()) {
			return false;
		}

		// Only forward posts
		if ($datarray["verb"] != ACTIVITY_POST) {
			return false;
		}

		if (($contact['network'] != NETWORK_FEED) && $datarray['private']) {
			return false;
		}

		$datarray2 = $datarray;
		logger('remote-self start - Contact '.$contact['url'].' - '.$contact['remote_self'].' Item '.print_r($datarray, true), LOGGER_DEBUG);
		if ($contact['remote_self'] == 2) {
			$r = q("SELECT `id`,`url`,`name`,`thumb` FROM `contact` WHERE `uid` = %d AND `self`",
				intval($contact['uid']));
			if (DBM::is_result($r)) {
				$datarray['contact-id'] = $r[0]["id"];

				$datarray['owner-name'] = $r[0]["name"];
				$datarray['owner-link'] = $r[0]["url"];
				$datarray['owner-avatar'] = $r[0]["thumb"];

				$datarray['author-name']   = $datarray['owner-name'];
				$datarray['author-link']   = $datarray['owner-link'];
				$datarray['author-avatar'] = $datarray['owner-avatar'];

				unset($datarray['created']);
				unset($datarray['edited']);
			}

			if ($contact['network'] != NETWORK_FEED) {
				$datarray["guid"] = get_guid(32);
				unset($datarray["plink"]);
				$datarray["uri"] = item_new_uri($a->get_hostname(), $contact['uid'], $datarray["guid"]);
				$datarray["parent-uri"] = $datarray["uri"];
				$datarray["extid"] = $contact['network'];
				$urlpart = parse_url($datarray2['author-link']);
				$datarray["app"] = $urlpart["host"];
			} else {
				$datarray['private'] = 0;
			}
		}

		if ($contact['network'] != NETWORK_FEED) {
			// Store the original post
			$r = self::insert($datarray2, false, false);
			logger('remote-self post original item - Contact '.$contact['url'].' return '.$r.' Item '.print_r($datarray2, true), LOGGER_DEBUG);
		} else {
			$datarray["app"] = "Feed";
		}

		// Trigger automatic reactions for addons
		$datarray['api_source'] = true;

		// We have to tell the hooks who we are - this really should be improved
		$_SESSION["authenticated"] = true;
		$_SESSION["uid"] = $contact['uid'];

		return true;
	}

	/**
	 *
	 * @param string $s
	 * @param int    $uid
	 * @param array  $item
	 * @param int    $cid
	 * @return string
	 */
	public static function fixPrivatePhotos($s, $uid, $item = null, $cid = 0)
	{
		if (Config::get('system', 'disable_embedded')) {
			return $s;
		}

		logger('check for photos', LOGGER_DEBUG);
		$site = substr(System::baseUrl(), strpos(System::baseUrl(), '://'));

		$orig_body = $s;
		$new_body = '';

		$img_start = strpos($orig_body, '[img');
		$img_st_close = ($img_start !== false ? strpos(substr($orig_body, $img_start), ']') : false);
		$img_len = ($img_start !== false ? strpos(substr($orig_body, $img_start + $img_st_close + 1), '[/img]') : false);

		while (($img_st_close !== false) && ($img_len !== false)) {
			$img_st_close++; // make it point to AFTER the closing bracket
			$image = substr($orig_body, $img_start + $img_st_close, $img_len);

			logger('found photo ' . $image, LOGGER_DEBUG);

			if (stristr($image, $site . '/photo/')) {
				// Only embed locally hosted photos
				$replace = false;
				$i = basename($image);
				$i = str_replace(['.jpg', '.png', '.gif'], ['', '', ''], $i);
				$x = strpos($i, '-');

				if ($x) {
					$res = substr($i, $x + 1);
					$i = substr($i, 0, $x);
					$r = q("SELECT * FROM `photo` WHERE `resource-id` = '%s' AND `scale` = %d AND `uid` = %d",
						dbesc($i),
						intval($res),
						intval($uid)
					);
					if (DBM::is_result($r)) {
						/*
						 * Check to see if we should replace this photo link with an embedded image
						 * 1. No need to do so if the photo is public
						 * 2. If there's a contact-id provided, see if they're in the access list
						 *    for the photo. If so, embed it.
						 * 3. Otherwise, if we have an item, see if the item permissions match the photo
						 *    permissions, regardless of order but first check to see if they're an exact
						 *    match to save some processing overhead.
						 */
						if (self::hasPermissions($r[0])) {
							if ($cid) {
								$recips = self::enumeratePermissions($r[0]);
								if (in_array($cid, $recips)) {
									$replace = true;
								}
							} elseif ($item) {
								if (self::samePermissions($item, $r[0])) {
									$replace = true;
								}
							}
						}
						if ($replace) {
							$data = $r[0]['data'];
							$type = $r[0]['type'];

							// If a custom width and height were specified, apply before embedding
							if (preg_match("/\[img\=([0-9]*)x([0-9]*)\]/is", substr($orig_body, $img_start, $img_st_close), $match)) {
								logger('scaling photo', LOGGER_DEBUG);

								$width = intval($match[1]);
								$height = intval($match[2]);

								$Image = new Image($data, $type);
								if ($Image->isValid()) {
									$Image->scaleDown(max($width, $height));
									$data = $Image->asString();
									$type = $Image->getType();
								}
							}

							logger('replacing photo', LOGGER_DEBUG);
							$image = 'data:' . $type . ';base64,' . base64_encode($data);
							logger('replaced: ' . $image, LOGGER_DATA);
						}
					}
				}
			}

			$new_body = $new_body . substr($orig_body, 0, $img_start + $img_st_close) . $image . '[/img]';
			$orig_body = substr($orig_body, $img_start + $img_st_close + $img_len + strlen('[/img]'));
			if ($orig_body === false) {
				$orig_body = '';
			}

			$img_start = strpos($orig_body, '[img');
			$img_st_close = ($img_start !== false ? strpos(substr($orig_body, $img_start), ']') : false);
			$img_len = ($img_start !== false ? strpos(substr($orig_body, $img_start + $img_st_close + 1), '[/img]') : false);
		}

		$new_body = $new_body . $orig_body;

		return $new_body;
	}

	private static function hasPermissions($obj)
	{
		return (
			(
				x($obj, 'allow_cid')
			) || (
				x($obj, 'allow_gid')
			) || (
				x($obj, 'deny_cid')
			) || (
				x($obj, 'deny_gid')
			)
		);
	}

	private static function samePermissions($obj1, $obj2)
	{
		// first part is easy. Check that these are exactly the same.
		if (($obj1['allow_cid'] == $obj2['allow_cid'])
			&& ($obj1['allow_gid'] == $obj2['allow_gid'])
			&& ($obj1['deny_cid'] == $obj2['deny_cid'])
			&& ($obj1['deny_gid'] == $obj2['deny_gid'])) {
			return true;
		}

		// This is harder. Parse all the permissions and compare the resulting set.
		$recipients1 = self::enumeratePermissions($obj1);
		$recipients2 = self::enumeratePermissions($obj2);
		sort($recipients1);
		sort($recipients2);

		/// @TODO Comparison of arrays, maybe use array_diff_assoc() here?
		return ($recipients1 == $recipients2);
	}

	// returns an array of contact-ids that are allowed to see this object
	private static function enumeratePermissions($obj)
	{
		$allow_people = expand_acl($obj['allow_cid']);
		$allow_groups = Group::expand(expand_acl($obj['allow_gid']));
		$deny_people  = expand_acl($obj['deny_cid']);
		$deny_groups  = Group::expand(expand_acl($obj['deny_gid']));
		$recipients   = array_unique(array_merge($allow_people, $allow_groups));
		$deny         = array_unique(array_merge($deny_people, $deny_groups));
		$recipients   = array_diff($recipients, $deny);
		return $recipients;
	}

	public static function getFeedTags($item)
	{
		$ret = [];
		$matches = false;
		$cnt = preg_match_all('|\#\[url\=(.*?)\](.*?)\[\/url\]|', $item['tag'], $matches);
		if ($cnt) {
			for ($x = 0; $x < $cnt; $x ++) {
				if ($matches[1][$x]) {
					$ret[$matches[2][$x]] = ['#', $matches[1][$x], $matches[2][$x]];
				}
			}
		}
		$matches = false;
		$cnt = preg_match_all('|\@\[url\=(.*?)\](.*?)\[\/url\]|', $item['tag'], $matches);
		if ($cnt) {
			for ($x = 0; $x < $cnt; $x ++) {
				if ($matches[1][$x]) {
					$ret[] = ['@', $matches[1][$x], $matches[2][$x]];
				}
			}
		}
		return $ret;
	}

	public static function expire($uid, $days, $network = "", $force = false)
	{
		if (!$uid || ($days < 1)) {
			return;
		}

		/*
		 * $expire_network_only = save your own wall posts
		 * and just expire conversations started by others
		 */
		$expire_network_only = PConfig::get($uid,'expire', 'network_only');
		$sql_extra = (intval($expire_network_only) ? " AND wall = 0 " : "");

		if ($network != "") {
			$sql_extra .= sprintf(" AND network = '%s' ", dbesc($network));

			/*
			 * There is an index "uid_network_received" but not "uid_network_created"
			 * This avoids the creation of another index just for one purpose.
			 * And it doesn't really matter wether to look at "received" or "created"
			 */
			$range = "AND `received` < UTC_TIMESTAMP() - INTERVAL %d DAY ";
		} else {
			$range = "AND `created` < UTC_TIMESTAMP() - INTERVAL %d DAY ";
		}

		$r = q("SELECT `file`, `resource-id`, `starred`, `type`, `id` FROM `item`
			WHERE `uid` = %d $range
			AND `id` = `parent`
			$sql_extra
			AND `deleted` = 0",
			intval($uid),
			intval($days)
		);

		if (!DBM::is_result($r)) {
			return;
		}

		$expire_items = PConfig::get($uid, 'expire', 'items', 1);

		// Forcing expiring of items - but not notes and marked items
		if ($force) {
			$expire_items = true;
		}

		$expire_notes = PConfig::get($uid, 'expire', 'notes', 1);
		$expire_starred = PConfig::get($uid, 'expire', 'starred', 1);
		$expire_photos = PConfig::get($uid, 'expire', 'photos', 0);

		logger('User '.$uid.': expire: # items=' . count($r). "; expire items: $expire_items, expire notes: $expire_notes, expire starred: $expire_starred, expire photos: $expire_photos");

		foreach ($r as $item) {

			// don't expire filed items

			if (strpos($item['file'],'[') !== false) {
				continue;
			}

			// Only expire posts, not photos and photo comments

			if ($expire_photos == 0 && strlen($item['resource-id'])) {
				continue;
			} elseif ($expire_starred == 0 && intval($item['starred'])) {
				continue;
			} elseif ($expire_notes == 0 && $item['type'] == 'note') {
				continue;
			} elseif ($expire_items == 0 && $item['type'] != 'note') {
				continue;
			}

			self::delete($item['id'], PRIORITY_LOW);
		}
	}

	/// @TODO: This query seems to be really slow
	public static function firstPostDate($uid, $wall = false)
	{
		$r = q("SELECT `id`, `created` FROM `item`
			WHERE `uid` = %d AND `wall` = %d AND `deleted` = 0 AND `visible` = 1 AND `moderated` = 0
			AND `id` = `parent`
			ORDER BY `created` ASC LIMIT 1",
			intval($uid),
			intval($wall ? 1 : 0)
		);
		if (DBM::is_result($r)) {
			return substr(DateTimeFormat::local($r[0]['created']),0,10);
		}
		return false;
	}

	/**
	 * @brief add/remove activity to an item
	 *
	 * Toggle activities as like,dislike,attend of an item
	 *
	 * @param string $item_id
	 * @param string $verb
	 * 		Activity verb. One of
	 * 			like, unlike, dislike, undislike, attendyes, unattendyes,
	 * 			attendno, unattendno, attendmaybe, unattendmaybe
	 * @hook 'post_local_end'
	 * 		array $arr
	 * 			'post_id' => ID of posted item
	 */
	public static function performLike($item_id, $verb)
	{
		if (!local_user() && !remote_user()) {
			return false;
		}

		switch ($verb) {
			case 'like':
			case 'unlike':
				$bodyverb = L10n::t('%1$s likes %2$s\'s %3$s');
				$activity = ACTIVITY_LIKE;
				break;
			case 'dislike':
			case 'undislike':
				$bodyverb = L10n::t('%1$s doesn\'t like %2$s\'s %3$s');
				$activity = ACTIVITY_DISLIKE;
				break;
			case 'attendyes':
			case 'unattendyes':
				$bodyverb = L10n::t('%1$s is attending %2$s\'s %3$s');
				$activity = ACTIVITY_ATTEND;
				break;
			case 'attendno':
			case 'unattendno':
				$bodyverb = L10n::t('%1$s is not attending %2$s\'s %3$s');
				$activity = ACTIVITY_ATTENDNO;
				break;
			case 'attendmaybe':
			case 'unattendmaybe':
				$bodyverb = L10n::t('%1$s may attend %2$s\'s %3$s');
				$activity = ACTIVITY_ATTENDMAYBE;
				break;
			default:
				logger('like: unknown verb ' . $verb . ' for item ' . $item_id);
				return false;
		}

		// Enable activity toggling instead of on/off
		$event_verb_flag = $activity === ACTIVITY_ATTEND || $activity === ACTIVITY_ATTENDNO || $activity === ACTIVITY_ATTENDMAYBE;

		logger('like: verb ' . $verb . ' item ' . $item_id);

		$item = dba::selectFirst('item', [], ['`id` = ? OR `uri` = ?', $item_id, $item_id]);
		if (!DBM::is_result($item)) {
			logger('like: unknown item ' . $item_id);
			return false;
		}

		$uid = $item['uid'];
		if (($uid == 0) && local_user()) {
			$uid = local_user();
		}

		if (!can_write_wall($uid)) {
			logger('like: unable to write on wall ' . $uid);
			return false;
		}

		// Retrieves the local post owner
		$owner_self_contact = dba::selectFirst('contact', [], ['uid' => $uid, 'self' => true]);
		if (!DBM::is_result($owner_self_contact)) {
			logger('like: unknown owner ' . $uid);
			return false;
		}

		// Retrieve the current logged in user's public contact
		$author_id = public_contact();

		$author_contact = dba::selectFirst('contact', [], ['id' => $author_id]);
		if (!DBM::is_result($author_contact)) {
			logger('like: unknown author ' . $author_id);
			return false;
		}

		// Contact-id is the uid-dependant author contact
		if (local_user() == $uid) {
			$item_contact_id = $owner_self_contact['id'];
			$item_contact = $owner_self_contact;
		} else {
			$item_contact_id = Contact::getIdForURL($author_contact['url'], $uid);
			$item_contact = dba::selectFirst('contact', [], ['id' => $item_contact_id]);
			if (!DBM::is_result($item_contact)) {
				logger('like: unknown item contact ' . $item_contact_id);
				return false;
			}
		}

		// Look for an existing verb row
		// event participation are essentially radio toggles. If you make a subsequent choice,
		// we need to eradicate your first choice.
		if ($event_verb_flag) {
			$verbs = "'" . dbesc(ACTIVITY_ATTEND) . "', '" . dbesc(ACTIVITY_ATTENDNO) . "', '" . dbesc(ACTIVITY_ATTENDMAYBE) . "'";
		} else {
			$verbs = "'".dbesc($activity)."'";
		}

		$existing_like = q("SELECT `id`, `guid`, `verb` FROM `item`
			WHERE `verb` IN ($verbs)
			AND `deleted` = 0
			AND `author-id` = %d
			AND `uid` = %d
			AND (`parent` = '%s' OR `parent-uri` = '%s' OR `thr-parent` = '%s')
			LIMIT 1",
			intval($author_contact['id']),
			intval($item['uid']),
			dbesc($item_id), dbesc($item_id), dbesc($item['uri'])
		);

		// If it exists, mark it as deleted
		if (DBM::is_result($existing_like)) {
			$like_item = $existing_like[0];

			// Already voted, undo it
			$fields = ['deleted' => true, 'unseen' => true, 'changed' => DateTimeFormat::utcNow()];
			dba::update('item', $fields, ['id' => $like_item['id']]);

			// Clean up the Diaspora signatures for this like
			// Go ahead and do it even if Diaspora support is disabled. We still want to clean up
			// if it had been enabled in the past
			dba::delete('sign', ['iid' => $like_item['id']]);

			$like_item_id = $like_item['id'];
			Worker::add(PRIORITY_HIGH, "Notifier", "like", $like_item_id);

			if (!$event_verb_flag || $like_item['verb'] == $activity) {
				return true;
			}
		}

		// Verb is "un-something", just trying to delete existing entries
		if (strpos($verb, 'un') === 0) {
			return true;
		}

		// Else or if event verb different from existing row, create a new item row
		$post_type = (($item['resource-id']) ? L10n::t('photo') : L10n::t('status'));
		if ($item['object-type'] === ACTIVITY_OBJ_EVENT) {
			$post_type = L10n::t('event');
		}
		$objtype = $item['resource-id'] ? ACTIVITY_OBJ_IMAGE : ACTIVITY_OBJ_NOTE ;
		$link = xmlify('<link rel="alternate" type="text/html" href="' . System::baseUrl() . '/display/' . $owner_self_contact['nick'] . '/' . $item['id'] . '" />' . "\n") ;
		$body = $item['body'];

		$obj = <<< EOT

		<object>
			<type>$objtype</type>
			<local>1</local>
			<id>{$item['uri']}</id>
			<link>$link</link>
			<title></title>
			<content>$body</content>
		</object>
EOT;

		$ulink = '[url=' . $author_contact['url'] . ']' . $author_contact['name'] . '[/url]';
		$alink = '[url=' . $item['author-link'] . ']' . $item['author-name'] . '[/url]';
		$plink = '[url=' . System::baseUrl() . '/display/' . $owner_self_contact['nick'] . '/' . $item['id'] . ']' . $post_type . '[/url]';

		$new_item = [
			'guid'          => get_guid(32),
			'uri'           => item_new_uri(self::getApp()->get_hostname(), $item['uid']),
			'uid'           => $item['uid'],
			'contact-id'    => $item_contact_id,
			'type'          => 'activity',
			'wall'          => $item['wall'],
			'origin'        => 1,
			'gravity'       => GRAVITY_LIKE,
			'parent'        => $item['id'],
			'parent-uri'    => $item['uri'],
			'thr-parent'    => $item['uri'],
			'owner-id'      => $item['owner-id'],
			'owner-name'    => $item['owner-name'],
			'owner-link'    => $item['owner-link'],
			'owner-avatar'  => $item['owner-avatar'],
			'author-id'     => $author_contact['id'],
			'author-name'   => $author_contact['name'],
			'author-link'   => $author_contact['url'],
			'author-avatar' => $author_contact['thumb'],
			'body'          => sprintf($bodyverb, $ulink, $alink, $plink),
			'verb'          => $activity,
			'object-type'   => $objtype,
			'object'        => $obj,
			'allow_cid'     => $item['allow_cid'],
			'allow_gid'     => $item['allow_gid'],
			'deny_cid'      => $item['deny_cid'],
			'deny_gid'      => $item['deny_gid'],
			'visible'       => 1,
			'unseen'        => 1,
		];

		$new_item_id = Item::insert($new_item);

		// @todo: Explain this block
		if (! $item['visible']) {
			q("UPDATE `item` SET `visible` = 1 WHERE `id` = %d",
				intval($item['id'])
			);
		}

		// Save the author information for the like in case we need to relay to Diaspora
		Diaspora::storeLikeSignature($item_contact, $new_item_id);

		$new_item['id'] = $new_item_id;

		Addon::callHooks('post_local_end', $new_item);

		Worker::add(PRIORITY_HIGH, "Notifier", "like", $new_item_id);

		return true;
	}

	private static function addThread($itemid, $onlyshadow = false)
	{
		$items = q("SELECT `uid`, `created`, `edited`, `commented`, `received`, `changed`, `wall`, `private`, `pubmail`,
				`moderated`, `visible`, `spam`, `starred`, `bookmark`, `contact-id`, `gcontact-id`,
				`deleted`, `origin`, `forum_mode`, `mention`, `network`, `author-id`, `owner-id`
			FROM `item` WHERE `id` = %d AND (`parent` = %d OR `parent` = 0) LIMIT 1", intval($itemid), intval($itemid));
	
		if (!$items) {
			return;
		}
	
		$item = $items[0];
		$item['iid'] = $itemid;
	
		if (!$onlyshadow) {
			$result = dba::insert('thread', $item);
	
			logger("Add thread for item ".$itemid." - ".print_r($result, true), LOGGER_DEBUG);
		}
	}

	public static function updateThreadFromUri($itemuri, $uid)
	{
		$messages = dba::select('item', ['id'], ['uri' => $itemuri, 'uid' => $uid]);
	
		if (DBM::is_result($messages)) {
			foreach ($messages as $message) {
				self::updateThread($message["id"]);
			}
		}
	}

	public static function updateThread($itemid, $setmention = false)
	{
		$items = q("SELECT `uid`, `guid`, `title`, `body`, `created`, `edited`, `commented`, `received`, `changed`, `wall`, `private`, `pubmail`, `moderated`, `visible`, `spam`, `starred`, `bookmark`, `contact-id`, `gcontact-id`,
				`deleted`, `origin`, `forum_mode`, `network`, `rendered-html`, `rendered-hash` FROM `item` WHERE `id` = %d AND (`parent` = %d OR `parent` = 0) LIMIT 1", intval($itemid), intval($itemid));
	
		if (!DBM::is_result($items)) {
			return;
		}
	
		$item = $items[0];
	
		if ($setmention) {
			$item["mention"] = 1;
		}
	
		$sql = "";
	
		foreach ($item as $field => $data)
			if (!in_array($field, ["guid", "title", "body", "rendered-html", "rendered-hash"])) {
				if ($sql != "") {
					$sql .= ", ";
				}
	
				$sql .= "`".$field."` = '".dbesc($data)."'";
			}
	
		$result = q("UPDATE `thread` SET ".$sql." WHERE `iid` = %d", intval($itemid));
	
		logger("Update thread for item ".$itemid." - guid ".$item["guid"]." - ".print_r($result, true)." ".print_r($item, true), LOGGER_DEBUG);
	
		// Updating a shadow item entry
		$items = dba::selectFirst('item', ['id'], ['guid' => $item['guid'], 'uid' => 0]);
	
		if (!DBM::is_result($items)) {
			return;
		}
	
		$result = dba::update(
			'item',
			['title' => $item['title'], 'body' => $item['body'], 'rendered-html' => $item['rendered-html'], 'rendered-hash' => $item['rendered-hash']],
			['id' => $items['id']]
		);

		logger("Updating public shadow for post ".$items["id"]." - guid ".$item["guid"]." Result: ".print_r($result, true), LOGGER_DEBUG);
	}
	
	public static function deleteThreadFromUri($itemuri, $uid)
	{
		$messages = dba::select('item', ['id'], ['uri' => $itemuri, 'uid' => $uid]);
	
		if (DBM::is_result($messages)) {
			foreach ($messages as $message) {
				self::deleteThread($message["id"], $itemuri);
			}
		}
	}
	
	public static function deleteThread($itemid, $itemuri = "")
	{
		$item = dba::select('thread', ['uid'], ['iid' => $itemid]);
	
		if (!DBM::is_result($item)) {
			logger('No thread found for id '.$itemid, LOGGER_DEBUG);
			return;
		}
	
		// Using dba::delete at this time could delete the associated item entries
		$result = dba::e("DELETE FROM `thread` WHERE `iid` = ?", $itemid);
	
		logger("deleteThread: Deleted thread for item ".$itemid." - ".print_r($result, true), LOGGER_DEBUG);
	
		if ($itemuri != "") {
			$r = q("SELECT `id` FROM `item` WHERE `uri` = '%s' AND NOT `deleted` AND NOT (`uid` IN (%d, 0))",
					dbesc($itemuri),
					intval($item["uid"])
				);
			if (!DBM::is_result($r)) {
				dba::delete('item', ['uri' => $itemuri, 'uid' => 0]);
				logger("deleteThread: Deleted shadow for item ".$itemuri, LOGGER_DEBUG);
			}
		}
	}
}
