<?php
/**
 * @copyright Copyright (C) 2010-2021, the Friendica project
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

namespace Friendica\Model;

use Friendica\Content\Text\BBCode;
use Friendica\Content\Text\HTML;
use Friendica\Core\Hook;
use Friendica\Core\Logger;
use Friendica\Core\Protocol;
use Friendica\Core\Renderer;
use Friendica\Core\Session;
use Friendica\Core\System;
use Friendica\Model\Tag;
use Friendica\Core\Worker;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Post;
use Friendica\Protocol\Activity;
use Friendica\Protocol\ActivityPub;
use Friendica\Protocol\Diaspora;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Map;
use Friendica\Util\Network;
use Friendica\Util\Strings;
use Friendica\Worker\Delivery;
use LanguageDetection\Language;

class Item
{
	// Posting types, inspired by https://www.w3.org/TR/activitystreams-vocabulary/#object-types
	const PT_ARTICLE = 0;
	const PT_NOTE = 1;
	const PT_PAGE = 2;
	const PT_IMAGE = 16;
	const PT_AUDIO = 17;
	const PT_VIDEO = 18;
	const PT_DOCUMENT = 19;
	const PT_EVENT = 32;
	const PT_PERSONAL_NOTE = 128;

	// Posting reasons (Why had a post been stored for a user?)
	const PR_NONE = 0;
	const PR_TAG = 64;
	const PR_TO = 65;
	const PR_CC = 66;
	const PR_BTO = 67;
	const PR_BCC = 68;
	const PR_FOLLOWER = 69;
	const PR_ANNOUNCEMENT = 70;
	const PR_COMMENT = 71;
	const PR_STORED = 72;
	const PR_GLOBAL = 73;
	const PR_RELAY = 74;
	const PR_FETCHED = 75;

	// Field list that is used to display the items
	const DISPLAY_FIELDLIST = [
		'uid', 'id', 'parent', 'guid', 'network', 'gravity',
		'uri-id', 'uri', 'thr-parent-id', 'thr-parent', 'parent-uri-id', 'parent-uri',
		'commented', 'created', 'edited', 'received', 'verb', 'object-type', 'postopts', 'plink',
		'wall', 'private', 'starred', 'origin', 'parent-origin', 'title', 'body', 'language',
		'content-warning', 'location', 'coord', 'app', 'rendered-hash', 'rendered-html', 'object',
		'allow_cid', 'allow_gid', 'deny_cid', 'deny_gid', 'mention',
		'author-id', 'author-link', 'author-name', 'author-avatar', 'author-network',
		'owner-id', 'owner-link', 'owner-name', 'owner-avatar', 'owner-network', 'owner-contact-type',
		'causer-id', 'causer-link', 'causer-name', 'causer-avatar', 'causer-contact-type', 'causer-network',
		'contact-id', 'contact-uid', 'contact-link', 'contact-name', 'contact-avatar',
		'writable', 'self', 'cid', 'alias',
		'event-created', 'event-edited', 'event-start', 'event-finish',
		'event-summary', 'event-desc', 'event-location', 'event-type',
		'event-nofinish', 'event-adjust', 'event-ignore', 'event-id',
		'delivery_queue_count', 'delivery_queue_done', 'delivery_queue_failed'
	];

	// Field list that is used to deliver items via the protocols
	const DELIVER_FIELDLIST = ['uid', 'id', 'parent', 'uri-id', 'uri', 'thr-parent', 'parent-uri', 'guid',
			'parent-guid', 'received', 'created', 'edited', 'verb', 'object-type', 'object', 'target',
			'private', 'title', 'body', 'raw-body', 'location', 'coord', 'app',
			'inform', 'deleted', 'extid', 'post-type', 'post-reason', 'gravity',
			'allow_cid', 'allow_gid', 'deny_cid', 'deny_gid',
			'author-id', 'author-link', 'author-name', 'author-avatar', 'owner-id', 'owner-link', 'contact-uid',
			'signed_text', 'network', 'wall', 'contact-id', 'plink', 'forum_mode', 'origin',
			'thr-parent-id', 'parent-uri-id', 'postopts', 'pubmail',
			'event-created', 'event-edited', 'event-start', 'event-finish',
			'event-summary', 'event-desc', 'event-location', 'event-type',
			'event-nofinish', 'event-adjust', 'event-ignore', 'event-id'];

	// All fields in the item table
	const ITEM_FIELDLIST = ['id', 'uid', 'parent', 'uri', 'parent-uri', 'thr-parent',
			'guid', 'uri-id', 'parent-uri-id', 'thr-parent-id', 'vid',
			'contact-id', 'wall', 'gravity', 'extid', 'psid',
			'created', 'edited', 'commented', 'received', 'changed', 'verb',
			'postopts', 'plink', 'resource-id', 'event-id', 'inform',
			'allow_cid', 'allow_gid', 'deny_cid', 'deny_gid', 'post-type', 'post-reason',
			'private', 'pubmail', 'visible', 'starred',
			'unseen', 'deleted', 'origin', 'forum_mode', 'mention', 'global', 'network',
			'title', 'content-warning', 'body', 'location', 'coord', 'app',
			'rendered-hash', 'rendered-html', 'object-type', 'object', 'target-type', 'target',
			'author-id', 'author-link', 'author-name', 'author-avatar', 'author-network',
			'owner-id', 'owner-link', 'owner-name', 'owner-avatar', 'causer-id'];

	// List of all verbs that don't need additional content data.
	// Never reorder or remove entries from this list. Just add new ones at the end, if needed.
	const ACTIVITIES = [
		Activity::LIKE, Activity::DISLIKE,
		Activity::ATTEND, Activity::ATTENDNO, Activity::ATTENDMAYBE,
		Activity::FOLLOW,
		Activity::ANNOUNCE];

	const PUBLIC = 0;
	const PRIVATE = 1;
	const UNLISTED = 2;

	/**
	 * Update existing item entries
	 *
	 * @param array $fields    The fields that are to be changed
	 * @param array $condition The condition for finding the item entries
	 *
	 * In the future we may have to change permissions as well.
	 * Then we had to add the user id as third parameter.
	 *
	 * A return value of "0" doesn't mean an error - but that 0 rows had been changed.
	 *
	 * @return integer|boolean number of affected rows - or "false" if there was an error
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function update(array $fields, array $condition)
	{
		if (empty($condition) || empty($fields)) {
			return false;
		}

		if (isset($fields['extid'])) {
			$fields['external-id'] = ItemURI::getIdByURI($fields['extid']);
		}

		if (!empty($fields['verb'])) {
			$fields['vid'] = Verb::getID($fields['verb']);
		}

		$rows = Post::update($fields, $condition);
		if (is_bool($rows)) {
			return $rows;
		}

		// We only need to call the line by line update for specific fields
		if (empty($fields['body']) && empty($fields['file']) &&
			empty($fields['attach']) && empty($fields['edited'])) {
			return $rows;
		}

		Logger::info('Updating per single row method', ['fields' => $fields, 'condition' => $condition]);

		$items = Post::select(['id', 'origin', 'uri-id', 'uid'], $condition);

		$notify_items = [];

		while ($item = DBA::fetch($items)) {
			if (!empty($fields['body'])) {
				$content_fields = ['raw-body' => trim($fields['raw-body'] ?? $fields['body'])];
	
				// Remove all media attachments from the body and store them in the post-media table
				$content_fields['raw-body'] = Post\Media::insertFromBody($item['uri-id'], $content_fields['raw-body']);
				$content_fields['raw-body'] = self::setHashtags($content_fields['raw-body']);
			}

			if (!empty($fields['file'])) {
				Post\Category::storeTextByURIId($item['uri-id'], $item['uid'], $fields['file']);
			}

			if (!empty($fields['attach'])) {
				Post\Media::insertFromAttachment($item['uri-id'], $fields['attach']);
			}

			// We only need to notfiy others when it is an original entry from us.
			// Only call the notifier when the item has some content relevant change.
			if ($item['origin'] && in_array('edited', array_keys($fields))) {
				$notify_items[] = $item['id'];
			}
		}

		DBA::close($items);

		foreach ($notify_items as $notify_item) {
			$post = Post::selectFirst(['uri-id', 'uid'], ['id' => $notify_item]);
			Worker::add(PRIORITY_HIGH, "Notifier", Delivery::POST, (int)$post['uri-id'], (int)$post['uid']);
		}

		return $rows;
	}

	/**
	 * Delete an item and notify others about it - if it was ours
	 *
	 * @param array   $condition The condition for finding the item entries
	 * @param integer $priority  Priority for the notification
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function markForDeletion($condition, $priority = PRIORITY_HIGH)
	{
		$items = Post::select(['id'], $condition);
		while ($item = Post::fetch($items)) {
			self::markForDeletionById($item['id'], $priority);
		}
		DBA::close($items);
	}

	/**
	 * Delete an item for an user and notify others about it - if it was ours
	 *
	 * @param array   $condition The condition for finding the item entries
	 * @param integer $uid       User who wants to delete this item
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function deleteForUser($condition, $uid)
	{
		if ($uid == 0) {
			return;
		}

		$items = Post::select(['id', 'uid', 'uri-id'], $condition);
		while ($item = Post::fetch($items)) {
			if (in_array($item['uid'], [$uid, 0])) {
				Post\User::update($item['uri-id'], $uid, ['hidden' => true], true);
				Post\ThreadUser::update($item['uri-id'], $uid, ['hidden' => true], true);
			}

			if ($item['uid'] == $uid) {
				self::markForDeletionById($item['id'], PRIORITY_HIGH);
			} elseif ($item['uid'] != 0) {
				Logger::notice('Wrong ownership. Not deleting item', ['id' => $item['id']]);
			}
		}
		DBA::close($items);
	}

	/**
	 * Mark an item for deletion, delete related data and notify others about it - if it was ours
	 *
	 * @param integer $item_id
	 * @param integer $priority Priority for the notification
	 *
	 * @return boolean success
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function markForDeletionById($item_id, $priority = PRIORITY_HIGH)
	{
		Logger::info('Mark item for deletion by id', ['id' => $item_id, 'callstack' => System::callstack()]);
		// locate item to be deleted
		$fields = ['id', 'uri', 'uri-id', 'uid', 'parent', 'parent-uri-id', 'origin',
			'deleted', 'resource-id', 'event-id',
			'verb', 'object-type', 'object', 'target', 'contact-id', 'psid', 'gravity'];
		$item = Post::selectFirst($fields, ['id' => $item_id]);
		if (!DBA::isResult($item)) {
			Logger::info('Item not found.', ['id' => $item_id]);
			return false;
		}

		if ($item['deleted']) {
			Logger::info('Item has already been marked for deletion.', ['id' => $item_id]);
			return false;
		}

		$parent = Post::selectFirst(['origin'], ['id' => $item['parent']]);
		if (!DBA::isResult($parent)) {
			$parent = ['origin' => false];
		}

		// clean up categories and tags so they don't end up as orphans
		Post\Category::deleteByURIId($item['uri-id'], $item['uid']);

		/*
		 * If item is a link to a photo resource, nuke all the associated photos
		 * (visitors will not have photo resources)
		 * This only applies to photos uploaded from the photos page. Photos inserted into a post do not
		 * generate a resource-id and therefore aren't intimately linked to the item.
		 */
		/// @TODO: this should first check if photo is used elsewhere
		if (strlen($item['resource-id'])) {
			Photo::delete(['resource-id' => $item['resource-id'], 'uid' => $item['uid']]);
		}

		// If item is a link to an event, delete the event.
		if (intval($item['event-id'])) {
			Event::delete($item['event-id']);
		}

		// If item has attachments, drop them
		$attachments = Post\Media::getByURIId($item['uri-id'], [Post\Media::DOCUMENT]);
		foreach($attachments as $attachment) {
			if (preg_match("|attach/(\d+)|", $attachment['url'], $matches)) {
				Attach::delete(['id' => $matches[1], 'uid' => $item['uid']]);
			}
		}

		// Set the item to "deleted"
		$item_fields = ['deleted' => true, 'edited' => DateTimeFormat::utcNow(), 'changed' => DateTimeFormat::utcNow()];
		Post::update($item_fields, ['id' => $item['id']]);

		Post\Category::storeTextByURIId($item['uri-id'], $item['uid'], '');

		if (!Post::exists(["`uri-id` = ? AND `uid` != 0 AND NOT `deleted`", $item['uri-id']])) {
			self::markForDeletion(['uri-id' => $item['uri-id'], 'uid' => 0, 'deleted' => false], $priority);
		}

		Post\DeliveryData::delete($item['uri-id']);

		// If it's the parent of a comment thread, kill all the kids
		if ($item['gravity'] == GRAVITY_PARENT) {
			self::markForDeletion(['parent' => $item['parent'], 'deleted' => false], $priority);
		}

		// Is it our comment and/or our thread?
		if (($item['origin'] || $parent['origin']) && ($item['uid'] != 0)) {
			// When we delete the original post we will delete all existing copies on the server as well
			self::markForDeletion(['uri-id' => $item['uri-id'], 'deleted' => false], $priority);

			// send the notification upstream/downstream
			if ($priority) {
				Worker::add(['priority' => $priority, 'dont_fork' => true], "Notifier", Delivery::DELETION, (int)$item['uri-id'], (int)$item['uid']);
			}
		} elseif ($item['uid'] != 0) {
			Post\User::update($item['uri-id'], $item['uid'], ['hidden' => true]);
			Post\ThreadUser::update($item['uri-id'], $item['uid'], ['hidden' => true]);
		}

		Logger::info('Item has been marked for deletion.', ['id' => $item_id]);

		return true;
	}

	private static function guid($item, $notify)
	{
		if (!empty($item['guid'])) {
			return Strings::escapeTags(trim($item['guid']));
		}

		if ($notify) {
			// We have to avoid duplicates. So we create the GUID in form of a hash of the plink or uri.
			// We add the hash of our own host because our host is the original creator of the post.
			$prefix_host = DI::baseUrl()->getHostname();
		} else {
			$prefix_host = '';

			// We are only storing the post so we create a GUID from the original hostname.
			if (!empty($item['author-link'])) {
				$parsed = parse_url($item['author-link']);
				if (!empty($parsed['host'])) {
					$prefix_host = $parsed['host'];
				}
			}

			if (empty($prefix_host) && !empty($item['plink'])) {
				$parsed = parse_url($item['plink']);
				if (!empty($parsed['host'])) {
					$prefix_host = $parsed['host'];
				}
			}

			if (empty($prefix_host) && !empty($item['uri'])) {
				$parsed = parse_url($item['uri']);
				if (!empty($parsed['host'])) {
					$prefix_host = $parsed['host'];
				}
			}

			// Is it in the format data@host.tld? - Used for mail contacts
			if (empty($prefix_host) && !empty($item['author-link']) && strstr($item['author-link'], '@')) {
				$mailparts = explode('@', $item['author-link']);
				$prefix_host = array_pop($mailparts);
			}
		}

		if (!empty($item['plink'])) {
			$guid = self::guidFromUri($item['plink'], $prefix_host);
		} elseif (!empty($item['uri'])) {
			$guid = self::guidFromUri($item['uri'], $prefix_host);
		} else {
			$guid = System::createUUID(hash('crc32', $prefix_host));
		}

		return $guid;
	}

	private static function contactId($item)
	{
		if (!empty($item['contact-id']) && DBA::exists('contact', ['self' => true, 'id' => $item['contact-id']])) {
			return $item['contact-id'];
		} elseif (($item['gravity'] == GRAVITY_PARENT) && !empty($item['uid']) && !empty($item['contact-id']) && Contact::isSharing($item['contact-id'], $item['uid'])) {
			return $item['contact-id'];
		} elseif (!empty($item['uid']) && !Contact::isSharing($item['author-id'], $item['uid'])) {
			return $item['author-id'];
		} elseif (!empty($item['contact-id'])) {
			return $item['contact-id'];
		} else {
			$contact_id = Contact::getIdForURL($item['author-link'], $item['uid']);
			if (!empty($contact_id)) {
				return $contact_id;
			}
		}
		return $item['author-id'];
	}

	/**
	 * Write an item array into a spool file to be inserted later.
	 * This command is called whenever there are issues storing an item.
	 *
	 * @param array $item The item fields that are to be inserted
	 * @throws \Exception
	 */
	private static function spool($orig_item)
	{
		// Now we store the data in the spool directory
		// We use "microtime" to keep the arrival order and "mt_rand" to avoid duplicates
		$file = 'item-' . round(microtime(true) * 10000) . '-' . mt_rand() . '.msg';

		$spoolpath = get_spoolpath();
		if ($spoolpath != "") {
			$spool = $spoolpath . '/' . $file;

			file_put_contents($spool, json_encode($orig_item));
			Logger::warning("Item wasn't stored - Item was spooled into file", ['file' => $file]);
		}
	}

	/**
	 * Check if the item array is a duplicate
	 *
	 * @param array $item
	 * @return boolean is it a duplicate?
	 */
	private static function isDuplicate(array $item)
	{
		// Checking if there is already an item with the same guid
		$condition = ['guid' => $item['guid'], 'network' => $item['network'], 'uid' => $item['uid']];
		if (Post::exists($condition)) {
			Logger::notice('Found already existing item', [
				'guid' => $item['guid'],
				'uid' => $item['uid'],
				'network' => $item['network']
			]);
			return true;
		}

		$condition = ['uri-id' => $item['uri-id'], 'uid' => $item['uid'],
			'network' => [$item['network'], Protocol::DFRN]];
		if (Post::exists($condition)) {
			Logger::notice('duplicated item with the same uri found.', $item);
			return true;
		}

		// On Friendica and Diaspora the GUID is unique
		if (in_array($item['network'], [Protocol::DFRN, Protocol::DIASPORA])) {
			$condition = ['guid' => $item['guid'], 'uid' => $item['uid']];
			if (Post::exists($condition)) {
				Logger::notice('duplicated item with the same guid found.', $item);
				return true;
			}
		} elseif ($item['network'] == Protocol::OSTATUS) {
			// Check for an existing post with the same content. There seems to be a problem with OStatus.
			$condition = ["`body` = ? AND `network` = ? AND `created` = ? AND `contact-id` = ? AND `uid` = ?",
					$item['body'], $item['network'], $item['created'], $item['contact-id'], $item['uid']];
			if (Post::exists($condition)) {
				Logger::notice('duplicated item with the same body found.', $item);
				return true;
			}
		}

		/*
		 * Check for already added items.
		 * There is a timing issue here that sometimes creates double postings.
		 * An unique index would help - but the limitations of MySQL (maximum size of index values) prevent this.
		 */
		if (($item['uid'] == 0) && Post::exists(['uri-id' => $item['uri-id'], 'uid' => 0])) {
			Logger::notice('Global item already stored.', ['uri-id' => $item['uri-id'], 'network' => $item['network']]);
			return true;
		}

		return false;
	}

	/**
	 * Check if the item array is valid
	 *
	 * @param array $item
	 * @return boolean item is valid
	 */
	public static function isValid(array $item)
	{
		// When there is no content then we don't post it
		if ($item['body'] . $item['title'] == '') {
			Logger::notice('No body, no title.');
			return false;
		}

		if (!empty($item['uid'])) {
			$owner = User::getOwnerDataById($item['uid'], false);
			if (!$owner) {
				Logger::notice('Missing item user owner data', ['uid' => $item['uid']]);
				return false;
			}

			if ($owner['account_expired'] || $owner['account_removed']) {
				Logger::notice('Item user has been deleted/expired/removed', ['uid' => $item['uid'], 'deleted' => $owner['deleted'], 'account_expired' => $owner['account_expired'], 'account_removed' => $owner['account_removed']]);
				return false;
			}
		}

		if (!empty($item['author-id']) && Contact::isBlocked($item['author-id'])) {
			Logger::notice('Author is blocked node-wide', ['author-link' => $item['author-link'], 'item-uri' => $item['uri']]);
			return false;
		}

		if (!empty($item['author-link']) && Network::isUrlBlocked($item['author-link'])) {
			Logger::notice('Author server is blocked', ['author-link' => $item['author-link'], 'item-uri' => $item['uri']]);
			return false;
		}

		if (!empty($item['owner-id']) && Contact::isBlocked($item['owner-id'])) {
			Logger::notice('Owner is blocked node-wide', ['owner-link' => $item['owner-link'], 'item-uri' => $item['uri']]);
			return false;
		}

		if (!empty($item['owner-link']) && Network::isUrlBlocked($item['owner-link'])) {
			Logger::notice('Owner server is blocked', ['owner-link' => $item['owner-link'], 'item-uri' => $item['uri']]);
			return false;
		}

		if (!empty($item['uid']) && !self::isAllowedByUser($item, $item['uid'])) {
			return false;
		}

		if ($item['verb'] == Activity::FOLLOW) {
			if (!$item['origin'] && ($item['author-id'] == Contact::getPublicIdByUserId($item['uid']))) {
				// Our own follow request can be relayed to us. We don't store it to avoid notification chaos.
				Logger::info("Follow: Don't store not origin follow request", ['parent-uri' => $item['parent-uri']]);
				return false;
			}

			$condition = ['verb' => Activity::FOLLOW, 'uid' => $item['uid'],
				'parent-uri' => $item['parent-uri'], 'author-id' => $item['author-id']];
			if (Post::exists($condition)) {
				// It happens that we receive multiple follow requests by the same author - we only store one.
				Logger::info('Follow: Found existing follow request from author', ['author-id' => $item['author-id'], 'parent-uri' => $item['parent-uri']]);
				return false;
			}
		}

		return true;
	}

	/**
	 * Check if the item array is too old
	 *
	 * @param array $item
	 * @return boolean item is too old
	 */
	public static function isTooOld(array $item)
	{
		// check for create date and expire time
		$expire_interval = DI::config()->get('system', 'dbclean-expire-days', 0);

		$user = DBA::selectFirst('user', ['expire'], ['uid' => $item['uid']]);
		if (DBA::isResult($user) && ($user['expire'] > 0) && (($user['expire'] < $expire_interval) || ($expire_interval == 0))) {
			$expire_interval = $user['expire'];
		}

		if (($expire_interval > 0) && !empty($item['created'])) {
			$expire_date = time() - ($expire_interval * 86400);
			$created_date = strtotime($item['created']);
			if ($created_date < $expire_date) {
				Logger::notice('Item created before expiration interval.', [
					'created' => date('c', $created_date),
					'expired' => date('c', $expire_date),
					'$item' => $item
				]);
				return true;
			}
		}

		return false;
	}

	/**
	 * Return the id of the given item array if it has been stored before
	 *
	 * @param array $item
	 * @return integer item id
	 */
	private static function getDuplicateID(array $item)
	{
		if (empty($item['network']) || in_array($item['network'], Protocol::FEDERATED)) {
			$condition = ["`uri-id` = ? AND `uid` = ? AND `network` IN (?, ?, ?, ?)",
				$item['uri-id'], $item['uid'],
				Protocol::ACTIVITYPUB, Protocol::DIASPORA, Protocol::DFRN, Protocol::OSTATUS];
			$existing = Post::selectFirst(['id', 'network'], $condition);
			if (DBA::isResult($existing)) {
				// We only log the entries with a different user id than 0. Otherwise we would have too many false positives
				if ($item['uid'] != 0) {
					Logger::notice('Item already existed for user', [
						'uri-id' => $item['uri-id'],
						'uid' => $item['uid'],
						'network' => $item['network'],
						'existing_id' => $existing["id"],
						'existing_network' => $existing["network"]
					]);
				}

				return $existing["id"];
			}
		}
		return 0;
	}

	/**
	 * Fetch top-level parent data for the given item array
	 *
	 * @param array $item
	 * @return array item array with parent data
	 * @throws \Exception
	 */
	private static function getTopLevelParent(array $item)
	{
		$fields = ['uid', 'uri', 'parent-uri', 'id', 'deleted',
			'uri-id', 'parent-uri-id',
			'allow_cid', 'allow_gid', 'deny_cid', 'deny_gid',
			'wall', 'private', 'forum_mode', 'origin', 'author-id'];
		$condition = ['uri-id' => $item['thr-parent-id'], 'uid' => $item['uid']];
		$params = ['order' => ['id' => false]];
		$parent = Post::selectFirst($fields, $condition, $params);

		if (!DBA::isResult($parent)) {
			Logger::notice('item parent was not found - ignoring item', ['thr-parent-id' => $item['thr-parent-id'], 'uid' => $item['uid']]);
			return [];
		}

		if ($parent['uri-id'] == $parent['parent-uri-id']) {
			return $parent;
		}

		$condition = ['uri-id' => $parent['parent-uri-id'],
			'parent-uri-id' => $parent['parent-uri-id'],
			'uid' => $parent['uid']];
		$params = ['order' => ['id' => false]];
		$toplevel_parent = Post::selectFirst($fields, $condition, $params);
		if (!DBA::isResult($toplevel_parent)) {
			Logger::notice('item top level parent was not found - ignoring item', ['parent-uri-id' => $parent['parent-uri-id'], 'uid' => $parent['uid']]);
			return [];
		}

		return $toplevel_parent;
	}

	/**
	 * Get the gravity for the given item array
	 *
	 * @param array $item
	 * @return integer gravity
	 */
	private static function getGravity(array $item)
	{
		$activity = DI::activity();

		if (isset($item['gravity'])) {
			return intval($item['gravity']);
		} elseif ($item['parent-uri-id'] === $item['uri-id']) {
			return GRAVITY_PARENT;
		} elseif ($activity->match($item['verb'], Activity::POST)) {
			return GRAVITY_COMMENT;
		} elseif ($activity->match($item['verb'], Activity::FOLLOW)) {
			return GRAVITY_ACTIVITY;
		} elseif ($activity->match($item['verb'], Activity::ANNOUNCE)) {
			return GRAVITY_ACTIVITY;
		}
		Logger::info('Unknown gravity for verb', ['verb' => $item['verb']]);
		return GRAVITY_UNKNOWN;   // Should not happen
	}

	public static function insert($item, $notify = false, $dontcache = false)
	{
		$orig_item = $item;

		$priority = PRIORITY_HIGH;

		// If it is a posting where users should get notifications, then define it as wall posting
		if ($notify) {
			$item['wall'] = 1;
			$item['origin'] = 1;
			$item['network'] = Protocol::DFRN;
			$item['protocol'] = Conversation::PARCEL_DIRECT;
			$item['direction'] = Conversation::PUSH;

			if (in_array($notify, PRIORITIES)) {
				$priority = $notify;
			}
		} else {
			$item['network'] = trim(($item['network'] ?? '') ?: Protocol::PHANTOM);
		}

		$uid = intval($item['uid']);

		$item['guid'] = self::guid($item, $notify);
		$item['uri'] = substr(trim($item['uri'] ?? '') ?: self::newURI($item['uid'], $item['guid']), 0, 255);

		// Store URI data
		$item['uri-id'] = ItemURI::insert(['uri' => $item['uri'], 'guid' => $item['guid']]);

		// Backward compatibility: parent-uri used to be the direct parent uri.
		// If it is provided without a thr-parent, it probably is the old behavior.
		$item['thr-parent'] = trim($item['thr-parent'] ?? $item['parent-uri'] ?? $item['uri']);
		$item['parent-uri'] = $item['thr-parent'];

		$item['thr-parent-id'] = $item['parent-uri-id'] = ItemURI::getIdByURI($item['thr-parent']);

		// Store conversation data
		$item = Conversation::insert($item);

		/*
		 * Do we already have this item?
		 * We have to check several networks since Friendica posts could be repeated
		 * via OStatus (maybe Diasporsa as well)
		 */
		$duplicate = self::getDuplicateID($item);
		if ($duplicate) {
			return $duplicate;
		}

		// Additional duplicate checks
		/// @todo Check why the first duplication check returns the item number and the second a 0
		if (self::isDuplicate($item)) {
			return 0;
		}

		if (!isset($item['post-type'])) {
			$item['post-type'] = empty($item['title']) ? self::PT_NOTE : self::PT_ARTICLE;
		}

		$item['wall']          = intval($item['wall'] ?? 0);
		$item['extid']         = trim($item['extid'] ?? '');
		$item['author-name']   = trim($item['author-name'] ?? '');
		$item['author-link']   = trim($item['author-link'] ?? '');
		$item['author-avatar'] = trim($item['author-avatar'] ?? '');
		$item['owner-name']    = trim($item['owner-name'] ?? '');
		$item['owner-link']    = trim($item['owner-link'] ?? '');
		$item['owner-avatar']  = trim($item['owner-avatar'] ?? '');
		$item['received']      = (isset($item['received'])  ? DateTimeFormat::utc($item['received'])  : DateTimeFormat::utcNow());
		$item['created']       = (isset($item['created'])   ? DateTimeFormat::utc($item['created'])   : $item['received']);
		$item['edited']        = (isset($item['edited'])    ? DateTimeFormat::utc($item['edited'])    : $item['created']);
		$item['changed']       = (isset($item['changed'])   ? DateTimeFormat::utc($item['changed'])   : $item['created']);
		$item['commented']     = (isset($item['commented']) ? DateTimeFormat::utc($item['commented']) : $item['created']);
		$item['title']         = substr(trim($item['title'] ?? ''), 0, 255);
		$item['location']      = trim($item['location'] ?? '');
		$item['coord']         = trim($item['coord'] ?? '');
		$item['visible']       = (isset($item['visible']) ? intval($item['visible']) : 1);
		$item['deleted']       = 0;
		$item['verb']          = trim($item['verb'] ?? '');
		$item['object-type']   = trim($item['object-type'] ?? '');
		$item['object']        = trim($item['object'] ?? '');
		$item['target-type']   = trim($item['target-type'] ?? '');
		$item['target']        = trim($item['target'] ?? '');
		$item['plink']         = substr(trim($item['plink'] ?? ''), 0, 255);
		$item['allow_cid']     = trim($item['allow_cid'] ?? '');
		$item['allow_gid']     = trim($item['allow_gid'] ?? '');
		$item['deny_cid']      = trim($item['deny_cid'] ?? '');
		$item['deny_gid']      = trim($item['deny_gid'] ?? '');
		$item['private']       = intval($item['private'] ?? self::PUBLIC);
		$item['body']          = trim($item['body'] ?? '');
		$item['raw-body']      = trim($item['raw-body'] ?? $item['body']);
		$item['app']           = trim($item['app'] ?? '');
		$item['origin']        = intval($item['origin'] ?? 0);
		$item['postopts']      = trim($item['postopts'] ?? '');
		$item['resource-id']   = trim($item['resource-id'] ?? '');
		$item['event-id']      = intval($item['event-id'] ?? 0);
		$item['inform']        = trim($item['inform'] ?? '');
		$item['file']          = trim($item['file'] ?? '');

		// Items cannot be stored before they happen ...
		if ($item['created'] > DateTimeFormat::utcNow()) {
			$item['created'] = DateTimeFormat::utcNow();
		}

		// We haven't invented time travel by now.
		if ($item['edited'] > DateTimeFormat::utcNow()) {
			$item['edited'] = DateTimeFormat::utcNow();
		}

		$item['plink'] = ($item['plink'] ?? '') ?: DI::baseUrl() . '/display/' . urlencode($item['guid']);

		$item['gravity'] = self::getGravity($item);

		$item['language'] = self::getLanguage($item);

		$default = ['url' => $item['author-link'], 'name' => $item['author-name'],
			'photo' => $item['author-avatar'], 'network' => $item['network']];
		$item['author-id'] = ($item['author-id'] ?? 0) ?: Contact::getIdForURL($item['author-link'], 0, null, $default);

		$default = ['url' => $item['owner-link'], 'name' => $item['owner-name'],
			'photo' => $item['owner-avatar'], 'network' => $item['network']];
		$item['owner-id'] = ($item['owner-id'] ?? 0) ?: Contact::getIdForURL($item['owner-link'], 0, null, $default);

		$actor = ($item['gravity'] == GRAVITY_PARENT) ? $item['owner-id'] : $item['author-id'];
		if (!$item['origin'] && ($item['uid'] != 0) && Contact::isSharing($actor, $item['uid'])) {
			$item['post-reason'] = self::PR_FOLLOWER;
		}

		// Ensure that there is an avatar cache
		Contact::checkAvatarCache($item['author-id']);
		Contact::checkAvatarCache($item['owner-id']);

		// The contact-id should be set before "self::insert" was called - but there seems to be issues sometimes
		$item["contact-id"] = self::contactId($item);

		if (!empty($item['direction']) && in_array($item['direction'], [Conversation::PUSH, Conversation::RELAY]) &&
			self::isTooOld($item)) {
			Logger::info('Item is too old', ['item' => $item]);
			return 0;
		}

		if (!self::isValid($item)) {
			return 0;
		}

		if ($item['gravity'] !== GRAVITY_PARENT) {
			$toplevel_parent = self::getTopLevelParent($item);
			if (empty($toplevel_parent)) {
				return 0;
			}

			// If the thread originated from this node, we check the permission against the thread starter
			$condition = ['uri-id' => $toplevel_parent['uri-id'], 'wall' => true];
			$localTopLevelParent = Post::selectFirst(['uid'], $condition);
			if (!empty($localTopLevelParent['uid']) && !self::isAllowedByUser($item, $localTopLevelParent['uid'])) {
				return 0;
			}

			$parent_id             = $toplevel_parent['id'];
			$item['parent-uri']    = $toplevel_parent['uri'];
			$item['parent-uri-id'] = $toplevel_parent['uri-id'];
			$item['deleted']       = $toplevel_parent['deleted'];
			$item['allow_cid']     = $toplevel_parent['allow_cid'];
			$item['allow_gid']     = $toplevel_parent['allow_gid'];
			$item['deny_cid']      = $toplevel_parent['deny_cid'];
			$item['deny_gid']      = $toplevel_parent['deny_gid'];
			$parent_origin         = $toplevel_parent['origin'];

			// Don't federate received participation messages
			if ($item['verb'] != Activity::FOLLOW) {
				$item['wall'] = $toplevel_parent['wall'];
			} else {
				$item['wall'] = false;
				// Participations are technical messages, so they are set to "seen" automatically
				$item['unseen'] = false;
			}

			/*
			 * If the parent is private, force privacy for the entire conversation
			 * This differs from the above settings as it subtly allows comments from
			 * email correspondents to be private even if the overall thread is not.
			 */
			if ($toplevel_parent['private']) {
				$item['private'] = $toplevel_parent['private'];
			}

			/*
			 * Edge case. We host a public forum that was originally posted to privately.
			 * The original author commented, but as this is a comment, the permissions
			 * weren't fixed up so it will still show the comment as private unless we fix it here.
			 */
			if ((intval($toplevel_parent['forum_mode']) == 1) && ($toplevel_parent['private'] != self::PUBLIC)) {
				$item['private'] = self::PUBLIC;
			}

			// If its a post that originated here then tag the thread as "mention"
			if ($item['origin'] && $item['uid']) {
				DBA::update('post-thread-user', ['mention' => true], ['uri-id' => $item['parent-uri-id'], 'uid' => $item['uid']]);
				Logger::info('tagged thread as mention', ['parent' => $parent_id, 'parent-uri-id' => $item['parent-uri-id'], 'uid' => $item['uid']]);
			}

			// Update the contact relations
			Contact\Relation::store($toplevel_parent['author-id'], $item['author-id'], $item['created']);
		} else {
			$parent_id = 0;
			$parent_origin = $item['origin'];
		}

		$item['parent-uri-id'] = ItemURI::getIdByURI($item['parent-uri']);
		$item['thr-parent-id'] = ItemURI::getIdByURI($item['thr-parent']);

		// Is this item available in the global items (with uid=0)?
		if ($item["uid"] == 0) {
			$item["global"] = true;

			// Set the global flag on all items if this was a global item entry
			Post::update(['global' => true], ['uri-id' => $item['uri-id']]);
		} else {
			$item['global'] = Post::exists(['uid' => 0, 'uri-id' => $item['uri-id']]);
		}

		// ACL settings
		if (!empty($item["allow_cid"] . $item["allow_gid"] . $item["deny_cid"] . $item["deny_gid"])) {
			$item["private"] = self::PRIVATE;
		}

		if ($notify) {
			$item['edit'] = false;
			$item['parent'] = $parent_id;
			Hook::callAll('post_local', $item);
		} else {
			Hook::callAll('post_remote', $item);
		}

		if (!empty($item['cancel'])) {
			Logger::log('post cancelled by addon.');
			return 0;
		}

		if (empty($item['vid']) && !empty($item['verb'])) {
			$item['vid'] = Verb::getID($item['verb']);
		}

		// Creates or assigns the permission set
		$item['psid'] = PermissionSet::getIdFromACL(
			$item['uid'],
			$item['allow_cid'],
			$item['allow_gid'],
			$item['deny_cid'],
			$item['deny_gid']
		);

		if (!empty($item['extid'])) {
			$item['external-id'] = ItemURI::getIdByURI($item['extid']);
		}

		if ($item['verb'] == Activity::ANNOUNCE) {
			self::setOwnerforResharedItem($item);
		}

		// Remove all media attachments from the body and store them in the post-media table
		$item['raw-body'] = Post\Media::insertFromBody($item['uri-id'], $item['raw-body']);
		$item['raw-body'] = self::setHashtags($item['raw-body']);

		// Check for hashtags in the body and repair or add hashtag links
		$item['body'] = self::setHashtags($item['body']);

		// Fill the cache field
		self::putInCache($item);

		if (stristr($item['verb'], Activity::POKE)) {
			$notify_type = Delivery::POKE;
		} else {
			$notify_type = Delivery::POST;
		}

		// Filling item related side tables
		if (!empty($item['attach'])) {
			Post\Media::insertFromAttachment($item['uri-id'], $item['attach']);
		}

		if (empty($item['event-id'])) {
			unset($item['event-id']);
		}

		if (empty($item['causer-id'])) {
			unset($item['causer-id']);
		}

		Post::insert($item['uri-id'], $item);

		if ($item['gravity'] == GRAVITY_PARENT) {
			Post\Thread::insert($item['uri-id'], $item);
		}

		if (!in_array($item['verb'], self::ACTIVITIES)) {
			Post\Content::insert($item['uri-id'], $item);
		}

		// Diaspora signature
		if (!empty($item['diaspora_signed_text'])) {
			DBA::replace('diaspora-interaction', ['uri-id' => $item['uri-id'], 'interaction' => $item['diaspora_signed_text']]);
		}

		// Attached file links
		if (!empty($item['file'])) {
			Post\Category::storeTextByURIId($item['uri-id'], $item['uid'], $item['file']);
		}

		// Delivery relevant data
		$delivery_data = Post\DeliveryData::extractFields($item);

		if (!empty($item['origin']) || !empty($item['wall']) || !empty($delivery_data['postopts']) || !empty($delivery_data['inform'])) {
			Post\DeliveryData::insert($item['uri-id'], $delivery_data);
		}

		// Store tags from the body if this hadn't been handled previously in the protocol classes
		if (!Tag::existsForPost($item['uri-id'])) {
			Tag::storeFromBody($item['uri-id'], $item['body']);
		}

		$condition = ['uri-id' => $item['uri-id'], 'uid' => $item['uid']];
		if (Post::exists($condition)) {
			Logger::notice('Item is already inserted - aborting', $condition);
			return 0;
		}

		$post_user_id = Post\User::insert($item['uri-id'], $item['uid'], $item);
		if (!$post_user_id) {
			Logger::notice('Post-User is already inserted - aborting', ['uid' => $item['uid'], 'uri-id' => $item['uri-id']]);
			return 0;
		}

		if ($item['gravity'] == GRAVITY_PARENT) {
			$item['post-user-id'] = $post_user_id;
			Post\ThreadUser::insert($item['uri-id'], $item['uid'], $item);
		}

		Logger::notice('created item', ['post-id' => $post_user_id, 'uid' => $item['uid'], 'network' => $item['network'], 'uri-id' => $item['uri-id'], 'guid' => $item['guid']]);

		$posted_item = Post::selectFirst(self::ITEM_FIELDLIST, ['post-user-id' => $post_user_id]);
		if (!DBA::isResult($posted_item)) {
			// On failure store the data into a spool file so that the "SpoolPost" worker can try again later.
			Logger::warning('Could not store item. it will be spooled', ['id' => $post_user_id]);
			self::spool($orig_item);
			return 0;
		}

		// update the commented timestamp on the parent
		if (DI::config()->get('system', 'like_no_comment')) {
			// Update when it is a comment
			$update_commented = in_array($posted_item['gravity'], [GRAVITY_PARENT, GRAVITY_COMMENT]);
		} else {
			// Update when it isn't a follow or tag verb
			$update_commented = !in_array($posted_item['verb'], [Activity::FOLLOW, Activity::TAG]);
		}

		if ($update_commented) {
			$fields = ['commented' => DateTimeFormat::utcNow(), 'changed' => DateTimeFormat::utcNow()];
		} else {
			$fields = ['changed' => DateTimeFormat::utcNow()];
		}

		Post::update($fields, ['uri-id' => $posted_item['parent-uri-id'], 'uid' => $posted_item['uid']]);

		// In that function we check if this is a forum post. Additionally we delete the item under certain circumstances
		if (self::tagDeliver($posted_item['uid'], $post_user_id)) {
			// Get the user information for the logging
			$user = User::getById($uid);

			Logger::notice('Item had been deleted', ['id' => $post_user_id, 'user' => $uid, 'account-type' => $user['account-type']]);
			return 0;
		}

		if (!$dontcache) {
			if ($notify) {
				Hook::callAll('post_local_end', $posted_item);
			} else {
				Hook::callAll('post_remote_end', $posted_item);
			}		
		}

		if ($posted_item['gravity'] === GRAVITY_PARENT) {
			self::addShadow($post_user_id);
		} else {
			self::addShadowPost($post_user_id);
		}

		self::updateContact($posted_item);

		Post\UserNotification::setNotification($posted_item['uri-id'], $posted_item['uid']);

		check_user_notification($posted_item['uri-id'], $posted_item['uid']);

		// Distribute items to users who subscribed to their tags
		self::distributeByTags($posted_item);

		// Automatically reshare the item if the "remote_self" option is selected
		self::autoReshare($posted_item);

		$transmit = $notify || ($posted_item['visible'] && ($parent_origin || $posted_item['origin']));

		if ($transmit) {
			// Don't relay participation messages
			if (($posted_item['verb'] == Activity::FOLLOW) && 
				(!$posted_item['origin'] || ($posted_item['author-id'] != Contact::getPublicIdByUserId($uid)))) {
				Logger::info('Participation messages will not be relayed', ['item' => $posted_item['id'], 'uri' => $posted_item['uri'], 'verb' => $posted_item['verb']]);
				$transmit = false;
			}
		}

		if ($transmit) {
			Worker::add(['priority' => $priority, 'dont_fork' => true], 'Notifier', $notify_type, (int)$posted_item['uri-id'], (int)$posted_item['uid']);
		}

		return $post_user_id;
	}

	/**
	 * Change the owner of a parent item if it had been shared by a forum
	 *
	 * (public) forum posts in the new format consist of the regular post by the author
	 * followed by an announce message sent from the forum account.
	 * Changing the owner helps in grouping forum posts.
	 *
	 * @param array $item
	 * @return void
	 */
	private static function setOwnerforResharedItem(array $item)
	{
		$parent = Post::selectFirst(['id', 'causer-id', 'owner-id', 'author-id', 'author-link', 'origin', 'post-reason'],
			['uri-id' => $item['thr-parent-id'], 'uid' => $item['uid']]);
		if (!DBA::isResult($parent)) {
			Logger::error('Parent not found', ['uri-id' => $item['thr-parent-id'], 'uid' => $item['uid']]);
			return;
		}

		$author = Contact::selectFirst(['url', 'contact-type', 'network'], ['id' => $item['author-id']]);
		if (!DBA::isResult($author)) {
			Logger::error('Author not found', ['id' => $item['author-id']]);
			return;
		}

		$cid = Contact::getIdForURL($author['url'], $item['uid']);
		if (empty($cid) || !Contact::isSharing($cid, $item['uid'])) {
			Logger::info('The resharer is not a following contact: quit', ['resharer' => $author['url'], 'uid' => $item['uid'], 'cid' => $cid]);
			return;
		}

		if ($author['contact-type'] != Contact::TYPE_COMMUNITY) {
			if ($parent['post-reason'] == self::PR_ANNOUNCEMENT) {
				Logger::info('The parent is already marked as announced: quit', ['causer' => $parent['causer-id'], 'owner' => $parent['owner-id'], 'author' => $parent['author-id'], 'uid' => $item['uid']]);
				return;
			}

			if (Contact::isSharing($parent['owner-id'], $item['uid'])) {
				Logger::info('The resharer is no forum: quit', ['resharer' => $item['author-id'], 'owner' => $parent['owner-id'], 'author' => $parent['author-id'], 'uid' => $item['uid']]);
				return;
			}
			self::update(['post-reason' => self::PR_ANNOUNCEMENT, 'causer-id' => $item['author-id']], ['id' => $parent['id']]);
			Logger::info('Set announcement post-reason', ['uri-id' => $item['uri-id'], 'thr-parent-id' => $item['thr-parent-id'], 'uid' => $item['uid']]);
			return;
		}

		self::update(['owner-id' => $item['author-id'], 'contact-id' => $cid], ['id' => $parent['id']]);
		Logger::info('Change owner of the parent', ['uri-id' => $item['uri-id'], 'thr-parent-id' => $item['thr-parent-id'], 'uid' => $item['uid'], 'owner-id' => $item['author-id'], 'contact-id' => $cid]);
	}

	/**
	 * Distribute the given item to users who subscribed to their tags
	 *
	 * @param array $item     Processed item
	 */
	private static function distributeByTags(array $item)
	{
		if (($item['uid'] != 0) || ($item['gravity'] != GRAVITY_PARENT) || !in_array($item['network'], Protocol::FEDERATED)) {
			return;
		}

		$uids = Tag::getUIDListByURIId($item['uri-id']);
		foreach ($uids as $uid) {
			if (Contact::isSharing($item['author-id'], $uid)) {
				$fields = [];
			} else {
				$fields = ['post-reason' => self::PR_TAG];
			}

			$stored = self::storeForUserByUriId($item['uri-id'], $uid, $fields);
			Logger::info('Stored item for users', ['uri-id' => $item['uri-id'], 'uid' => $uid, 'fields' => $fields, 'stored' => $stored]);
		}
	}

	/**
	 * Distributes public items to the receivers
	 *
	 * @param integer $itemid      Item ID that should be added
	 * @param string  $signed_text Original text (for Diaspora signatures), JSON encoded.
	 * @throws \Exception
	 */
	public static function distribute($itemid, $signed_text = '')
	{
		$condition = ["`id` IN (SELECT `parent` FROM `post-user-view` WHERE `id` = ?)", $itemid];
		$parent = Post::selectFirst(['owner-id'], $condition);
		if (!DBA::isResult($parent)) {
			Logger::warning('Item not found', ['condition' => $condition]);
			return;
		}

		// Only distribute public items from native networks
		$condition = ['id' => $itemid, 'uid' => 0,
			'network' => array_merge(Protocol::FEDERATED ,['']),
			'visible' => true, 'deleted' => false, 'private' => [self::PUBLIC, self::UNLISTED]];
		$item = Post::selectFirst(self::ITEM_FIELDLIST, $condition);
		if (!DBA::isResult($item)) {
			Logger::warning('Item not found', ['condition' => $condition]);
			return;
		}

		$origin = $item['origin'];

		$users = [];

		/// @todo add a field "pcid" in the contact table that referrs to the public contact id.
		$owner = DBA::selectFirst('contact', ['url', 'nurl', 'alias'], ['id' => $parent['owner-id']]);
		if (!DBA::isResult($owner)) {
			return;
		}

		$condition = ['nurl' => $owner['nurl'], 'rel' => [Contact::SHARING, Contact::FRIEND]];
		$contacts = DBA::select('contact', ['uid'], $condition);
		while ($contact = DBA::fetch($contacts)) {
			if ($contact['uid'] == 0) {
				continue;
			}

			$users[$contact['uid']] = $contact['uid'];
		}
		DBA::close($contacts);

		$condition = ['alias' => $owner['url'], 'rel' => [Contact::SHARING, Contact::FRIEND]];
		$contacts = DBA::select('contact', ['uid'], $condition);
		while ($contact = DBA::fetch($contacts)) {
			if ($contact['uid'] == 0) {
				continue;
			}

			$users[$contact['uid']] = $contact['uid'];
		}
		DBA::close($contacts);

		if (!empty($owner['alias'])) {
			$condition = ['nurl' => Strings::normaliseLink($owner['alias']), 'rel' => [Contact::SHARING, Contact::FRIEND]];
			$contacts = DBA::select('contact', ['uid'], $condition);
			while ($contact = DBA::fetch($contacts)) {
				if ($contact['uid'] == 0) {
					continue;
				}

				$users[$contact['uid']] = $contact['uid'];
			}
			DBA::close($contacts);
		}

		$origin_uid = 0;

		if ($item['uri-id'] != $item['parent-uri-id']) {
			$parents = Post::select(['uid', 'origin'], ["`uri-id` = ? AND `uid` != 0", $item['parent-uri-id']]);
			while ($parent = Post::fetch($parents)) {
				$users[$parent['uid']] = $parent['uid'];
				if ($parent['origin'] && !$origin) {
					$origin_uid = $parent['uid'];
				}
			}
			DBA::close($parents);
		}

		foreach ($users as $uid) {
			if ($origin_uid == $uid) {
				$item['diaspora_signed_text'] = $signed_text;
			}
			self::storeForUser($item, $uid);
		}
	}

	/**
	 * Store a public item defined by their URI-ID for the given users
	 *
	 * @param integer $uri_id URI-ID of the given item
	 * @param integer $uid    The user that will receive the item entry
	 * @param array   $fields Additional fields to be stored
	 * @return integer stored item id
	 */
	public static function storeForUserByUriId(int $uri_id, int $uid, array $fields = [])
	{
		$item = Post::selectFirst(self::ITEM_FIELDLIST, ['uri-id' => $uri_id, 'uid' => 0]);
		if (!DBA::isResult($item)) {
			return 0;
		}

		if (($item['private'] == self::PRIVATE) || !in_array($item['network'], Protocol::FEDERATED)) {
			Logger::notice('Item is private or not from a federated network. It will not be stored for the user.', ['uri-id' => $uri_id, 'uid' => $uid, 'private' => $item['private'], 'network' => $item['network']]);
			return 0;
		}

		$item['post-reason'] = self::PR_STORED;

		$item = array_merge($item, $fields);

		$stored = self::storeForUser($item, $uid);
		Logger::info('Public item stored for user', ['uri-id' => $item['uri-id'], 'uid' => $uid, 'stored' => $stored]);
		return $stored;
	}

	/**
	 * Store a public item array for the given users
	 *
	 * @param array   $item   The item entry that will be stored
	 * @param integer $uid    The user that will receive the item entry
	 * @return integer stored item id
	 * @throws \Exception
	 */
	private static function storeForUser(array $item, int $uid)
	{
		if (Post::exists(['uri-id' => $item['uri-id'], 'uid' => $uid])) {
			Logger::info('Item already exists', ['uri-id' => $item['uri-id'], 'uid' => $uid]);
			return 0;
		}

		unset($item['id']);
		unset($item['parent']);
		unset($item['mention']);
		unset($item['starred']);
		unset($item['unseen']);
		unset($item['psid']);

		$item['uid'] = $uid;
		$item['origin'] = 0;
		$item['wall'] = 0;

		if ($item['gravity'] == GRAVITY_PARENT) {
			$contact = Contact::getByURLForUser($item['owner-link'], $uid, false, ['id']);
		} else {
			$contact = Contact::getByURLForUser($item['author-link'], $uid, false, ['id']);
		}

		if (!empty($contact['id'])) {
			$item['contact-id'] = $contact['id'];
		} else {
			// Shouldn't happen at all
			Logger::warning('contact-id could not be fetched', ['uid' => $uid, 'item' => $item]);
			$self = DBA::selectFirst('contact', ['id'], ['self' => true, 'uid' => $uid]);
			if (!DBA::isResult($self)) {
				// Shouldn't happen even less
				Logger::warning('self contact could not be fetched', ['uid' => $uid, 'item' => $item]);
				return 0;
			}
			$item['contact-id'] = $self['id'];
		}

		/// @todo Handling of "event-id"

		$notify = false;
		if ($item['gravity'] == GRAVITY_PARENT) {
			$contact = DBA::selectFirst('contact', [], ['id' => $item['contact-id'], 'self' => false]);
			if (DBA::isResult($contact)) {
				$notify = self::isRemoteSelf($contact, $item);
			}
		}

		$distributed = self::insert($item, $notify, true);

		if (!$distributed) {
			Logger::info("Distributed public item wasn't stored", ['uri-id' => $item['uri-id'], 'user' => $uid]);
		} else {
			Logger::info('Distributed public item was stored', ['uri-id' => $item['uri-id'], 'user' => $uid, 'stored' => $distributed]);
		}
		return $distributed;
	}

	/**
	 * Add a shadow entry for a given item id that is a thread starter
	 *
	 * We store every public item entry additionally with the user id "0".
	 * This is used for the community page and for the search.
	 * It is planned that in the future we will store public item entries only once.
	 *
	 * @param integer $itemid Item ID that should be added
	 * @throws \Exception
	 */
	private static function addShadow($itemid)
	{
		$fields = ['uid', 'private', 'visible', 'deleted', 'network', 'uri-id'];
		$condition = ['id' => $itemid, 'gravity' => GRAVITY_PARENT];
		$item = Post::selectFirst($fields, $condition);

		if (!DBA::isResult($item)) {
			return;
		}

		// is it already a copy?
		if (($itemid == 0) || ($item['uid'] == 0)) {
			return;
		}

		// Is it a visible public post?
		if (!$item["visible"] || $item["deleted"]  || ($item["private"] == self::PRIVATE)) {
			return;
		}

		// is it an entry from a connector? Only add an entry for natively connected networks
		if (!in_array($item["network"], array_merge(Protocol::FEDERATED ,['']))) {
			return;
		}

		if (Post::exists(['uri-id' => $item['uri-id'], 'uid' => 0])) {
			return;
		}

		$item = Post::selectFirst(self::ITEM_FIELDLIST, ['id' => $itemid]);

		if (DBA::isResult($item)) {
			// Preparing public shadow (removing user specific data)
			$item['uid'] = 0;
			unset($item['id']);
			unset($item['parent']);
			unset($item['wall']);
			unset($item['mention']);
			unset($item['origin']);
			unset($item['starred']);
			unset($item['postopts']);
			unset($item['inform']);
			unset($item['post-reason']);
			if ($item['uri-id'] == $item['parent-uri-id']) {
				$item['contact-id'] = $item['owner-id'];
			} else {
				$item['contact-id'] = $item['author-id'];
			}

			$public_shadow = self::insert($item, false, true);

			Logger::info('Stored public shadow', ['thread' => $itemid, 'id' => $public_shadow]);
		}
	}

	/**
	 * Add a shadow entry for a given item id that is a comment
	 *
	 * This function does the same like the function above - but for comments
	 *
	 * @param integer $itemid Item ID that should be added
	 * @throws \Exception
	 */
	private static function addShadowPost($itemid)
	{
		$item = Post::selectFirst(self::ITEM_FIELDLIST, ['id' => $itemid]);
		if (!DBA::isResult($item)) {
			return;
		}

		// Is it a toplevel post?
		if ($item['gravity'] == GRAVITY_PARENT) {
			self::addShadow($itemid);
			return;
		}

		// Is this a shadow entry?
		if ($item['uid'] == 0) {
			return;
		}

		// Is there a shadow parent?
		if (!Post::exists(['uri-id' => $item['parent-uri-id'], 'uid' => 0])) {
			return;
		}

		// Is there already a shadow entry?
		if (Post::exists(['uri-id' => $item['uri-id'], 'uid' => 0])) {
			return;
		}

		// Save "origin" and "parent" state
		$origin = $item['origin'];
		$parent = $item['parent'];

		// Preparing public shadow (removing user specific data)
		$item['uid'] = 0;
		unset($item['id']);
		unset($item['parent']);
		unset($item['wall']);
		unset($item['mention']);
		unset($item['origin']);
		unset($item['starred']);
		unset($item['postopts']);
		unset($item['inform']);
		unset($item['post-reason']);
		$item['contact-id'] = Contact::getIdForURL($item['author-link']);

		$public_shadow = self::insert($item, false, true);

		Logger::info('Stored public shadow', ['uri-id' => $item['uri-id'], 'id' => $public_shadow]);

		// If this was a comment to a Diaspora post we don't get our comment back.
		// This means that we have to distribute the comment by ourselves.
		if ($origin && Post::exists(['id' => $parent, 'network' => Protocol::DIASPORA])) {
			self::distribute($public_shadow);
		}
	}

	/**
	 * Adds a language specification in a "language" element of given $arr.
	 * Expects "body" element to exist in $arr.
	 *
	 * @param array $item
	 * @return string detected language
	 * @throws \Text_LanguageDetect_Exception
	 */
	private static function getLanguage(array $item)
	{
		if (!empty($item['language'])) {
			return $item['language'];
		}

		if (!in_array($item['gravity'], [GRAVITY_PARENT, GRAVITY_COMMENT]) || empty($item['body'])) {
			return '';
		}

		// Convert attachments to links
		$naked_body = BBCode::removeAttachment($item['body']);
		if (empty($naked_body)) {
			return '';
		}

		// Remove links and pictures
		$naked_body = BBCode::removeLinks($naked_body);

		// Convert the title and the body to plain text
		$naked_body = trim($item['title'] . "\n" . BBCode::toPlaintext($naked_body));

		// Remove possibly remaining links
		$naked_body = preg_replace(Strings::autoLinkRegEx(), '', $naked_body);

		if (empty($naked_body)) {
			return '';
		}

		$ld = new Language(DI::l10n()->getAvailableLanguages());
		$languages = $ld->detect($naked_body)->limit(0, 3)->close();
		if (is_array($languages)) {
			return json_encode($languages);
		}

		return '';
	}

	public static function getLanguageMessage(array $item)
	{
		$iso639 = new \Matriphe\ISO639\ISO639;

		$used_languages = '';
		foreach (json_decode($item['language'], true) as $language => $reliability) {
			$used_languages .= $iso639->languageByCode1($language) . ' (' . $language . "): " . number_format($reliability, 5) . '\n';
		}
		$used_languages = DI::l10n()->t('Detected languages in this post:\n%s', $used_languages);
		return $used_languages;
	}

	/**
	 * Creates an unique guid out of a given uri
	 *
	 * @param string $uri uri of an item entry
	 * @param string $host hostname for the GUID prefix
	 * @return string unique guid
	 */
	public static function guidFromUri($uri, $host)
	{
		// Our regular guid routine is using this kind of prefix as well
		// We have to avoid that different routines could accidentally create the same value
		$parsed = parse_url($uri);

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
	 * generate an unique URI
	 *
	 * @param integer $uid  User id
	 * @param string  $guid An existing GUID (Otherwise it will be generated)
	 *
	 * @return string
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function newURI($uid, $guid = "")
	{
		if ($guid == "") {
			$guid = System::createUUID();
		}

		return DI::baseUrl()->get() . '/objects/' . $guid;
	}

	/**
	 * Set "success_update" and "last-item" to the date of the last time we heard from this contact
	 *
	 * This can be used to filter for inactive contacts.
	 * Only do this for public postings to avoid privacy problems, since poco data is public.
	 * Don't set this value if it isn't from the owner (could be an author that we don't know)
	 *
	 * @param array $arr Contains the just posted item record
	 * @throws \Exception
	 */
	private static function updateContact($arr)
	{
		// Unarchive the author
		$contact = DBA::selectFirst('contact', [], ['id' => $arr["author-id"]]);
		if (DBA::isResult($contact)) {
			Contact::unmarkForArchival($contact);
		}

		// Unarchive the contact if it's not our own contact
		$contact = DBA::selectFirst('contact', [], ['id' => $arr["contact-id"], 'self' => false]);
		if (DBA::isResult($contact)) {
			Contact::unmarkForArchival($contact);
		}

		/// @todo On private posts we could obfuscate the date
		$update = ($arr['private'] != self::PRIVATE) || in_array($arr['network'], Protocol::FEDERATED);

		// Is it a forum? Then we don't care about the rules from above
		if (!$update && in_array($arr["network"], [Protocol::ACTIVITYPUB, Protocol::DFRN]) && ($arr["parent-uri-id"] === $arr["uri-id"])) {
			if (DBA::exists('contact', ['id' => $arr['contact-id'], 'forum' => true])) {
				$update = true;
			}
		}

		if ($update) {
			// The "self" contact id is used (for example in the connectors) when the contact is unknown
			// So we have to ensure to only update the last item when it had been our own post,
			// or it had been done by a "regular" contact.
			if (!empty($arr['wall'])) {
				$condition = ['id' => $arr['contact-id']];
			} else { 
				$condition = ['id' => $arr['contact-id'], 'self' => false];
			}
			DBA::update('contact', ['failed' => false, 'success_update' => $arr['received'], 'last-item' => $arr['received']], $condition);
		}
		// Now do the same for the system wide contacts with uid=0
		if ($arr['private'] != self::PRIVATE) {
			DBA::update('contact', ['failed' => false, 'success_update' => $arr['received'], 'last-item' => $arr['received']],
				['id' => $arr['owner-id']]);

			if ($arr['owner-id'] != $arr['author-id']) {
				DBA::update('contact', ['failed' => false, 'success_update' => $arr['received'], 'last-item' => $arr['received']],
					['id' => $arr['author-id']]);
			}
		}
	}

	public static function setHashtags($body)
	{
		$body = BBCode::performWithEscapedTags($body, ['noparse', 'pre', 'code', 'img'], function ($body) {
			$tags = BBCode::getTags($body);

			// No hashtags?
			if (!count($tags)) {
				return $body;
			}

			// This sorting is important when there are hashtags that are part of other hashtags
			// Otherwise there could be problems with hashtags like #test and #test2
			// Because of this we are sorting from the longest to the shortest tag.
			usort($tags, function ($a, $b) {
				return strlen($b) <=> strlen($a);
			});

			$URLSearchString = "^\[\]";

			// All hashtags should point to the home server if "local_tags" is activated
			if (DI::config()->get('system', 'local_tags')) {
				$body = preg_replace("/#\[url\=([$URLSearchString]*)\](.*?)\[\/url\]/ism",
					"#[url=" . DI::baseUrl() . "/search?tag=$2]$2[/url]", $body);
			}

			// mask hashtags inside of url, bookmarks and attachments to avoid urls in urls
			$body = preg_replace_callback("/\[url\=([$URLSearchString]*)\](.*?)\[\/url\]/ism",
				function ($match) {
					return ("[url=" . str_replace("#", "&num;", $match[1]) . "]" . str_replace("#", "&num;", $match[2]) . "[/url]");
				}, $body);

			$body = preg_replace_callback("/\[bookmark\=([$URLSearchString]*)\](.*?)\[\/bookmark\]/ism",
				function ($match) {
					return ("[bookmark=" . str_replace("#", "&num;", $match[1]) . "]" . str_replace("#", "&num;", $match[2]) . "[/bookmark]");
				}, $body);

			$body = preg_replace_callback("/\[attachment (.*)\](.*?)\[\/attachment\]/ism",
				function ($match) {
					return ("[attachment " . str_replace("#", "&num;", $match[1]) . "]" . $match[2] . "[/attachment]");
				}, $body);

			// Repair recursive urls
			$body = preg_replace("/&num;\[url\=([$URLSearchString]*)\](.*?)\[\/url\]/ism",
				"&num;$2", $body);

			foreach ($tags as $tag) {
				if ((strpos($tag, '#') !== 0) || strpos($tag, '[url=') || strlen($tag) < 2 || $tag[1] == '#') {
					continue;
				}

				$basetag = str_replace('_', ' ', substr($tag, 1));
				$newtag = '#[url=' . DI::baseUrl() . '/search?tag=' . $basetag . ']' . $basetag . '[/url]';

				$body = str_replace($tag, $newtag, $body);
			}

			// Convert back the masked hashtags
			$body = str_replace("&num;", "#", $body);

			return $body;
		});

		return $body;
	}

	/**
	 * look for mention tags and setup a second delivery chain for forum/community posts if appropriate
	 *
	 * @param int $uid
	 * @param int $item_id
	 * @return boolean true if item was deleted, else false
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	private static function tagDeliver($uid, $item_id)
	{
		$mention = false;

		$user = DBA::selectFirst('user', [], ['uid' => $uid]);
		if (!DBA::isResult($user)) {
			return false;
		}

		$community_page = (($user['page-flags'] == User::PAGE_FLAGS_COMMUNITY) ? true : false);
		$prvgroup = (($user['page-flags'] == User::PAGE_FLAGS_PRVGROUP) ? true : false);

		$item = Post::selectFirst(self::ITEM_FIELDLIST, ['id' => $item_id]);
		if (!DBA::isResult($item)) {
			return false;
		}

		$link = Strings::normaliseLink(DI::baseUrl() . '/profile/' . $user['nickname']);

		/*
		 * Diaspora uses their own hardwired link URL in @-tags
		 * instead of the one we supply with webfinger
		 */
		$dlink = Strings::normaliseLink(DI::baseUrl() . '/u/' . $user['nickname']);

		$cnt = preg_match_all('/[\@\!]\[url\=(.*?)\](.*?)\[\/url\]/ism', $item['body'], $matches, PREG_SET_ORDER);
		if ($cnt) {
			foreach ($matches as $mtch) {
				if (Strings::compareLink($link, $mtch[1]) || Strings::compareLink($dlink, $mtch[1])) {
					$mention = true;
					Logger::log('mention found: ' . $mtch[2]);
				}
			}
		}

		if (!$mention) {
			$tags = Tag::getByURIId($item['uri-id'], [Tag::MENTION, Tag::EXCLUSIVE_MENTION]);
			foreach ($tags as $tag) {
				if (Strings::compareLink($link, $tag['url']) || Strings::compareLink($dlink, $tag['url'])) {
					$mention = true;
					DI::logger()->info('mention found in tag.', ['url' => $tag['url']]);
				}
			}
		}
		
		if (!$mention) {
			if (($community_page || $prvgroup) &&
				  !$item['wall'] && !$item['origin'] && ($item['gravity'] == GRAVITY_PARENT)) {
				Logger::info('Delete private group/communiy top-level item without mention', ['id' => $item['id'], 'guid'=> $item['guid']]);
				Post\User::delete(['uri-id' => $item['uri-id'], 'uid' => $item['uid']]);
				return true;
			}
			return false;
		}

		$arr = ['item' => $item, 'user' => $user];

		Hook::callAll('tagged', $arr);

		if (!$community_page && !$prvgroup) {
			return false;
		}

		/*
		 * tgroup delivery - setup a second delivery chain
		 * prevent delivery looping - only proceed
		 * if the message originated elsewhere and is a top-level post
		 */
		if ($item['wall'] || $item['origin'] || ($item['id'] != $item['parent'])) {
			return false;
		}

		// now change this copy of the post to a forum head message and deliver to all the tgroup members
		$self = DBA::selectFirst('contact', ['id', 'name', 'url', 'thumb'], ['uid' => $uid, 'self' => true]);
		if (!DBA::isResult($self)) {
			return false;
		}

		$owner_id = Contact::getIdForURL($self['url']);

		// also reset all the privacy bits to the forum default permissions

		$private = ($user['allow_cid'] || $user['allow_gid'] || $user['deny_cid'] || $user['deny_gid']) ? self::PRIVATE : self::PUBLIC;

		$psid = PermissionSet::getIdFromACL(
			$user['uid'],
			$user['allow_cid'],
			$user['allow_gid'],
			$user['deny_cid'],
			$user['deny_gid']
		);

		$forum_mode = ($prvgroup ? 2 : 1);

		$fields = ['wall' => true, 'origin' => true, 'forum_mode' => $forum_mode, 'contact-id' => $self['id'],
			'owner-id' => $owner_id, 'private' => $private, 'psid' => $psid];
		self::update($fields, ['id' => $item['id']]);

		Worker::add(['priority' => PRIORITY_HIGH, 'dont_fork' => true], 'Notifier', Delivery::POST, (int)$item['uri-id'], (int)$item['uid']);

		self::performActivity($item['id'], 'announce', $uid);

		return false;
	}

	/**
	 * Automatically reshare the item if the "remote_self" option is selected
	 *
	 * @param array $item
	 * @return void
	 */
	private static function autoReshare(array $item)
	{
		if ($item['gravity'] != GRAVITY_PARENT) {
			return;
		}

		if (!DBA::exists('contact', ['id' => $item['contact-id'], 'remote_self' => Contact::MIRROR_NATIVE_RESHARE])) {
			return;
		}

		if (!in_array($item['network'], [Protocol::ACTIVITYPUB, Protocol::DFRN])) {
			return;
		}

		Logger::info('Automatically reshare item', ['uid' => $item['uid'], 'id' => $item['id'], 'guid' => $item['guid'], 'uri-id' => $item['uri-id']]);

		self::performActivity($item['id'], 'announce', $item['uid']);
	}

	public static function isRemoteSelf($contact, &$datarray)
	{
		if (!$contact['remote_self']) {
			return false;
		}

		// Prevent the forwarding of posts that are forwarded
		if (!empty($datarray["extid"]) && ($datarray["extid"] == Protocol::DFRN)) {
			Logger::info('Already forwarded');
			return false;
		}

		// Prevent to forward already forwarded posts
		if ($datarray["app"] == DI::baseUrl()->getHostname()) {
			Logger::info('Already forwarded (second test)');
			return false;
		}

		// Only forward posts
		if ($datarray["verb"] != Activity::POST) {
			Logger::info('No post');
			return false;
		}

		if (($contact['network'] != Protocol::FEED) && ($datarray['private'] == self::PRIVATE)) {
			Logger::info('Not public');
			return false;
		}

		$datarray2 = $datarray;
		Logger::info('remote-self start', ['contact' => $contact['url'], 'remote_self'=> $contact['remote_self'], 'item' => $datarray]);
		if ($contact['remote_self'] == Contact::MIRROR_OWN_POST) {
			$self = DBA::selectFirst('contact', ['id', 'name', 'url', 'thumb'],
					['uid' => $contact['uid'], 'self' => true]);
			if (DBA::isResult($self)) {
				$datarray['contact-id'] = $self["id"];

				$datarray['owner-name'] = $self["name"];
				$datarray['owner-link'] = $self["url"];
				$datarray['owner-avatar'] = $self["thumb"];

				$datarray['author-name']   = $datarray['owner-name'];
				$datarray['author-link']   = $datarray['owner-link'];
				$datarray['author-avatar'] = $datarray['owner-avatar'];

				unset($datarray['edited']);

				unset($datarray['network']);
				unset($datarray['owner-id']);
				unset($datarray['author-id']);
			}

			if ($contact['network'] != Protocol::FEED) {
				$old_uri_id = $datarray["uri-id"] ?? 0;
				$datarray["guid"] = System::createUUID();
				unset($datarray["plink"]);
				$datarray["uri"] = self::newURI($contact['uid'], $datarray["guid"]);
				$datarray["uri-id"] = ItemURI::getIdByURI($datarray["uri"]);
				$datarray["extid"] = Protocol::DFRN;
				$urlpart = parse_url($datarray2['author-link']);
				$datarray["app"] = $urlpart["host"];
				if (!empty($old_uri_id)) {
					Post\Media::copy($old_uri_id, $datarray["uri-id"]);
				}

				unset($datarray["parent-uri"]);
				unset($datarray["thr-parent"]);
			} else {
				$datarray['private'] = self::PUBLIC;
			}
		}

		if ($contact['network'] != Protocol::FEED) {
			// Store the original post
			$result = self::insert($datarray2);
			Logger::info('remote-self post original item', ['contact' => $contact['url'], 'result'=> $result, 'item' => $datarray2]);
		} else {
			$datarray["app"] = "Feed";
			$result = true;
		}

		// Trigger automatic reactions for addons
		$datarray['api_source'] = true;

		// We have to tell the hooks who we are - this really should be improved
		$_SESSION['authenticated'] = true;
		$_SESSION['uid'] = $contact['uid'];

		return (bool)$result;
	}

	/**
	 *
	 * @param string $s
	 * @param int    $uid
	 * @param array  $item
	 * @param int    $cid
	 * @return string
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function fixPrivatePhotos($s, $uid, $item = null, $cid = 0)
	{
		if (DI::config()->get('system', 'disable_embedded')) {
			return $s;
		}

		Logger::info('check for photos');
		$site = substr(DI::baseUrl(), strpos(DI::baseUrl(), '://'));

		$orig_body = $s;
		$new_body = '';

		$img_start = strpos($orig_body, '[img');
		$img_st_close = ($img_start !== false ? strpos(substr($orig_body, $img_start), ']') : false);
		$img_len = ($img_start !== false ? strpos(substr($orig_body, $img_start + $img_st_close + 1), '[/img]') : false);

		while (($img_st_close !== false) && ($img_len !== false)) {
			$img_st_close++; // make it point to AFTER the closing bracket
			$image = substr($orig_body, $img_start + $img_st_close, $img_len);

			Logger::info('found photo', ['image' => $image]);

			if (stristr($image, $site . '/photo/')) {
				// Only embed locally hosted photos
				$replace = false;
				$i = basename($image);
				$i = str_replace(['.jpg', '.png', '.gif'], ['', '', ''], $i);
				$x = strpos($i, '-');

				if ($x) {
					$res = substr($i, $x + 1);
					$i = substr($i, 0, $x);
					$photo = Photo::getPhotoForUser($uid, $i, $res);
					if (DBA::isResult($photo)) {
						/*
						 * Check to see if we should replace this photo link with an embedded image
						 * 1. No need to do so if the photo is public
						 * 2. If there's a contact-id provided, see if they're in the access list
						 *    for the photo. If so, embed it.
						 * 3. Otherwise, if we have an item, see if the item permissions match the photo
						 *    permissions, regardless of order but first check to see if they're an exact
						 *    match to save some processing overhead.
						 */
						if (self::hasPermissions($photo)) {
							if ($cid) {
								$recips = self::enumeratePermissions($photo);
								if (in_array($cid, $recips)) {
									$replace = true;
								}
							} elseif ($item) {
								if (self::samePermissions($uid, $item, $photo)) {
									$replace = true;
								}
							}
						}
						if ($replace) {
							$photo_img = Photo::getImageForPhoto($photo);
							// If a custom width and height were specified, apply before embedding
							if (preg_match("/\[img\=([0-9]*)x([0-9]*)\]/is", substr($orig_body, $img_start, $img_st_close), $match)) {
								Logger::info('scaling photo');

								$width = intval($match[1]);
								$height = intval($match[2]);

								$photo_img->scaleDown(max($width, $height));
							}

							$data = $photo_img->asString();
							$type = $photo_img->getType();

							Logger::info('replacing photo');
							$image = 'data:' . $type . ';base64,' . base64_encode($data);
							Logger::debug('replaced', ['image' => $image]);
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
		return !empty($obj['allow_cid']) || !empty($obj['allow_gid']) ||
			!empty($obj['deny_cid']) || !empty($obj['deny_gid']);
	}

	private static function samePermissions($uid, $obj1, $obj2)
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

	/**
	 * Returns an array of contact-ids that are allowed to see this object
	 *
	 * @param array $obj        Item array with at least uid, allow_cid, allow_gid, deny_cid and deny_gid
	 * @param bool  $check_dead Prunes unavailable contacts from the result
	 * @return array
	 * @throws \Exception
	 */
	public static function enumeratePermissions(array $obj, bool $check_dead = false)
	{
		$aclFormater = DI::aclFormatter();

		$allow_people = $aclFormater->expand($obj['allow_cid']);
		$allow_groups = Group::expand($obj['uid'], $aclFormater->expand($obj['allow_gid']), $check_dead);
		$deny_people  = $aclFormater->expand($obj['deny_cid']);
		$deny_groups  = Group::expand($obj['uid'], $aclFormater->expand($obj['deny_gid']), $check_dead);
		$recipients   = array_unique(array_merge($allow_people, $allow_groups));
		$deny         = array_unique(array_merge($deny_people, $deny_groups));
		$recipients   = array_diff($recipients, $deny);
		return $recipients;
	}

	public static function expire(int $uid, int $days, string $network = "", bool $force = false)
	{
		if (!$uid || ($days < 1)) {
			return;
		}

		$condition = ["`uid` = ? AND NOT `deleted` AND `gravity` = ?",
			$uid, GRAVITY_PARENT];

		/*
		 * $expire_network_only = save your own wall posts
		 * and just expire conversations started by others
		 */
		$expire_network_only = DI::pConfig()->get($uid, 'expire', 'network_only', false);

		if ($expire_network_only) {
			$condition[0] .= " AND NOT `wall`";
		}

		if ($network != "") {
			$condition[0] .= " AND `network` = ?";
			$condition[] = $network;
		}

		$condition[0] .= " AND `received` < UTC_TIMESTAMP() - INTERVAL ? DAY";
		$condition[] = $days;

		$items = Post::select(['resource-id', 'starred', 'id', 'post-type', 'uid', 'uri-id'], $condition);

		if (!DBA::isResult($items)) {
			return;
		}

		$expire_items = DI::pConfig()->get($uid, 'expire', 'items', true);

		// Forcing expiring of items - but not notes and marked items
		if ($force) {
			$expire_items = true;
		}

		$expire_notes = DI::pConfig()->get($uid, 'expire', 'notes', true);
		$expire_starred = DI::pConfig()->get($uid, 'expire', 'starred', true);
		$expire_photos = DI::pConfig()->get($uid, 'expire', 'photos', false);

		$expired = 0;

		$priority = DI::config()->get('system', 'expire-notify-priority');

		while ($item = Post::fetch($items)) {
			// don't expire filed items
			if (DBA::exists('post-category', ['uri-id' => $item['uri-id'], 'uid' => $item['uid'], 'type' => Post\Category::FILE])) {
				continue;
			}

			// Only expire posts, not photos and photo comments

			if (!$expire_photos && !empty($item['resource-id'])) {
				continue;
			} elseif (!$expire_starred && intval($item['starred'])) {
				continue;
			} elseif (!$expire_notes && ($item['post-type'] == self::PT_PERSONAL_NOTE)) {
				continue;
			} elseif (!$expire_items && ($item['post-type'] != self::PT_PERSONAL_NOTE)) {
				continue;
			}

			self::markForDeletionById($item['id'], $priority);

			++$expired;
		}
		DBA::close($items);
		Logger::log('User ' . $uid . ": expired $expired items; expire items: $expire_items, expire notes: $expire_notes, expire starred: $expire_starred, expire photos: $expire_photos");
	}

	public static function firstPostDate($uid, $wall = false)
	{
		$user = User::getById($uid, ['register_date']);
		if (empty($user)) {
			return false;
		}

		$condition = ["`uid` = ? AND `wall` = ? AND NOT `deleted` AND `visible` AND `received` >= ?",
			$uid, $wall, $user['register_date']];
		$params = ['order' => ['received' => false]];
		$thread = Post::selectFirstThread(['received'], $condition, $params);
		if (DBA::isResult($thread)) {
			$postdate = substr(DateTimeFormat::local($thread['received']), 0, 10);
			return $postdate;
		}
		return false;
	}

	/**
	 * add/remove activity to an item
	 *
	 * Toggle activities as like,dislike,attend of an item
	 *
	 * @param int $item_id
	 * @param string $verb
	 *            Activity verb. One of
	 *            like, unlike, dislike, undislike, attendyes, unattendyes,
	 *            attendno, unattendno, attendmaybe, unattendmaybe,
	 *            announce, unannouce
	 * @return bool
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 * @hook  'post_local_end'
	 *            array $arr
	 *            'post_id' => ID of posted item
	 */
	public static function performActivity(int $item_id, string $verb, int $uid)
	{
		if (empty($uid)) {
			return false;
		}

		Logger::notice('Start create activity', ['verb' => $verb, 'item' => $item_id, 'user' => $uid]);

		$item = Post::selectFirst(self::ITEM_FIELDLIST, ['id' => $item_id]);
		if (!DBA::isResult($item)) {
			Logger::log('like: unknown item ' . $item_id);
			return false;
		}

		$uri_id = $item['uri-id'];

		if (!in_array($item['uid'], [0, $uid])) {
			return false;
		}

		if (!Post::exists(['uri-id' => $item['parent-uri-id'], 'uid' => $uid])) {
			$stored = self::storeForUserByUriId($item['parent-uri-id'], $uid);
			if (($item['parent-uri-id'] == $item['uri-id']) && !empty($stored)) {
				$item = Post::selectFirst(self::ITEM_FIELDLIST, ['id' => $stored]);
				if (!DBA::isResult($item)) {
					Logger::info('Could not fetch just created item - should not happen', ['stored' => $stored, 'uid' => $uid, 'uri-id' => $uri_id]);
					return false;
				}
			}
		}

		// Retrieves the local post owner
		$owner = User::getOwnerDataById($uid);
		if (empty($owner)) {
			Logger::info('Empty owner for user', ['uid' => $uid]);
			return false;
		}

		// Retrieve the current logged in user's public contact
		$author_id = Contact::getIdForURL($owner['url']);
		if (empty($author_id)) {
			Logger::info('Empty public contact');
			return false;
		}

		$activity = null;
		switch ($verb) {
			case 'like':
			case 'unlike':
				$activity = Activity::LIKE;
				break;
			case 'dislike':
			case 'undislike':
				$activity = Activity::DISLIKE;
				break;
			case 'attendyes':
			case 'unattendyes':
				$activity = Activity::ATTEND;
				break;
			case 'attendno':
			case 'unattendno':
				$activity = Activity::ATTENDNO;
				break;
			case 'attendmaybe':
			case 'unattendmaybe':
				$activity = Activity::ATTENDMAYBE;
				break;
			case 'follow':
			case 'unfollow':
				$activity = Activity::FOLLOW;
				break;
			case 'announce':
			case 'unannounce':
				$activity = Activity::ANNOUNCE;
				break;
			default:
				Logger::notice('unknown verb', ['verb' => $verb, 'item' => $item_id]);
				return false;
		}

		$mode = Strings::startsWith($verb, 'un') ? 'delete' : 'create';

		// Enable activity toggling instead of on/off
		$event_verb_flag = $activity === Activity::ATTEND || $activity === Activity::ATTENDNO || $activity === Activity::ATTENDMAYBE;

		// Look for an existing verb row
		// Event participation activities are mutually exclusive, only one of them can exist at all times.
		if ($event_verb_flag) {
			$verbs = [Activity::ATTEND, Activity::ATTENDNO, Activity::ATTENDMAYBE];

			// Translate to the index based activity index
			$vids = [];
			foreach ($verbs as $verb) {
				$vids[] = Verb::getID($verb);
			}
		} else {
			$vids = Verb::getID($activity);
		}

		$condition = ['vid' => $vids, 'deleted' => false, 'gravity' => GRAVITY_ACTIVITY,
			'author-id' => $author_id, 'uid' => $item['uid'], 'thr-parent-id' => $uri_id];
		$like_item = Post::selectFirst(['id', 'guid', 'verb'], $condition);

		if (DBA::isResult($like_item)) {
			/**
			 * Truth table for existing activities
			 *
			 * |          Inputs            ||      Outputs      |
			 * |----------------------------||-------------------|
			 * |  Mode  | Event | Same verb || Delete? | Return? |
			 * |--------|-------|-----------||---------|---------|
			 * | create |  Yes  |    Yes    ||   No    |   Yes   |
			 * | create |  Yes  |    No     ||   Yes   |   No    |
			 * | create |  No   |    Yes    ||   No    |   Yes   |
			 * | create |  No   |    No     ||        N/A†       |
			 * | delete |  Yes  |    Yes    ||   Yes   |   N/A‡  |
			 * | delete |  Yes  |    No     ||   No    |   N/A‡  |
			 * | delete |  No   |    Yes    ||   Yes   |   N/A‡  |
			 * | delete |  No   |    No     ||        N/A†       |
			 * |--------|-------|-----------||---------|---------|
			 * |   A    |   B   |     C     || A xor C | !B or C |
			 *
			 * † Can't happen: It's impossible to find an existing non-event activity without
			 *                 the same verb because we are only looking for this single verb.
			 *
			 * ‡ The "mode = delete" is returning early whether an existing activity was found or not.
			 */
			if ($mode == 'create' xor $like_item['verb'] == $activity) {
				self::markForDeletionById($like_item['id']);
			}

			if (!$event_verb_flag || $like_item['verb'] == $activity) {
				return true;
			}
		}

		// No need to go further if we aren't creating anything
		if ($mode == 'delete') {
			return true;
		}

		$objtype = $item['resource-id'] ? Activity\ObjectType::IMAGE : Activity\ObjectType::NOTE;

		$new_item = [
			'guid'          => System::createUUID(),
			'uri'           => self::newURI($item['uid']),
			'uid'           => $item['uid'],
			'contact-id'    => $owner['id'],
			'wall'          => $item['wall'],
			'origin'        => 1,
			'network'       => Protocol::DFRN,
			'protocol'      => Conversation::PARCEL_DIRECT,
			'direction'     => Conversation::PUSH,
			'gravity'       => GRAVITY_ACTIVITY,
			'parent'        => $item['id'],
			'thr-parent'    => $item['uri'],
			'owner-id'      => $author_id,
			'author-id'     => $author_id,
			'body'          => $activity,
			'verb'          => $activity,
			'object-type'   => $objtype,
			'allow_cid'     => $item['allow_cid'],
			'allow_gid'     => $item['allow_gid'],
			'deny_cid'      => $item['deny_cid'],
			'deny_gid'      => $item['deny_gid'],
			'visible'       => 1,
			'unseen'        => 1,
		];

		$signed = Diaspora::createLikeSignature($uid, $new_item);
		if (!empty($signed)) {
			$new_item['diaspora_signed_text'] = json_encode($signed);
		}

		$new_item_id = self::insert($new_item);

		// If the parent item isn't visible then set it to visible
		if (!$item['visible']) {
			self::update(['visible' => true], ['id' => $item['id']]);
		}

		$new_item['id'] = $new_item_id;

		Hook::callAll('post_local_end', $new_item);

		return true;
	}

	/**
	 * Fetch the SQL condition for the given user id
	 *
	 * @param integer $owner_id User ID for which the permissions should be fetched
	 * @return array condition
	 */
	public static function getPermissionsConditionArrayByUserId(int $owner_id)
	{
		$local_user = local_user();
		$remote_user = Session::getRemoteContactID($owner_id);

		// default permissions - anonymous user
		$condition = ["`private` != ?", self::PRIVATE];

		if ($local_user && ($local_user == $owner_id)) {
			// Profile owner - everything is visible
			$condition = [];
		} elseif ($remote_user) {
			 // Authenticated visitor - fetch the matching permissionsets
			$set = PermissionSet::get($owner_id, $remote_user);
			if (!empty($set)) {
				$condition = ["(`private` != ? OR (`private` = ? AND `wall`
					AND `psid` IN (" . implode(', ', array_fill(0, count($set), '?')) . ")))",
					self::PRIVATE, self::PRIVATE];
				$condition = array_merge($condition, $set);
			}
		}

		return $condition;
	}

	/**
	 * Get a permission SQL string for the given user
	 * 
	 * @param int $owner_id 
	 * @param string $table 
	 * @return string 
	 */
	public static function getPermissionsSQLByUserId(int $owner_id, string $table = '')
	{
		$local_user = local_user();
		$remote_user = Session::getRemoteContactID($owner_id);

		if (!empty($table)) {
			$table = DBA::quoteIdentifier($table) . '.';
		}

		/*
		 * Construct permissions
		 *
		 * default permissions - anonymous user
		 */
		$sql = sprintf(" AND " . $table . "`private` != %d", self::PRIVATE);

		// Profile owner - everything is visible
		if ($local_user && ($local_user == $owner_id)) {
			$sql = '';
		} elseif ($remote_user) {
			/*
			 * Authenticated visitor. Unless pre-verified,
			 * check that the contact belongs to this $owner_id
			 * and load the groups the visitor belongs to.
			 * If pre-verified, the caller is expected to have already
			 * done this and passed the groups into this function.
			 */
			$set = PermissionSet::get($owner_id, $remote_user);

			if (!empty($set)) {
				$sql_set = sprintf(" OR (" . $table . "`private` = %d AND " . $table . "`wall` AND " . $table . "`psid` IN (", self::PRIVATE) . implode(',', $set) . "))";
			} else {
				$sql_set = '';
			}

			$sql = sprintf(" AND (" . $table . "`private` != %d", self::PRIVATE) . $sql_set . ")";
		}

		return $sql;
	}

	/**
	 * get translated item type
	 *
	 * @param array                $item
	 * @param \Friendica\Core\L10n $l10n
	 * @return string
	 */
	public static function postType(array $item, \Friendica\Core\L10n $l10n)
	{
		if (!empty($item['event-id'])) {
			return $l10n->t('event');
		} elseif (!empty($item['resource-id'])) {
			return $l10n->t('photo');
		} elseif ($item['gravity'] == GRAVITY_ACTIVITY) {
			return $l10n->t('activity');
		} elseif ($item['gravity'] == GRAVITY_COMMENT) {
			return $l10n->t('comment');
		}

		return $l10n->t('post');
	}

	/**
	 * Sets the "rendered-html" field of the provided item
	 *
	 * Body is preserved to avoid side-effects as we modify it just-in-time for spoilers and private image links
	 *
	 * @param array $item
	 *
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @todo Remove reference, simply return "rendered-html" and "rendered-hash"
	 */
	public static function putInCache(&$item)
	{
		// Save original body to prevent addons to modify it
		$body = $item['body'];

		$rendered_hash = $item['rendered-hash'] ?? '';
		$rendered_html = $item['rendered-html'] ?? '';

		if ($rendered_hash == ''
			|| $rendered_html == ''
			|| $rendered_hash != hash('md5', BBCode::VERSION . '::' . $body)
			|| DI::config()->get('system', 'ignore_cache')
		) {
			self::addRedirToImageTags($item);

			$item['rendered-html'] = BBCode::convert($item['body']);
			$item['rendered-hash'] = hash('md5', BBCode::VERSION . '::' . $body);

			$hook_data = ['item' => $item, 'rendered-html' => $item['rendered-html'], 'rendered-hash' => $item['rendered-hash']];
			Hook::callAll('put_item_in_cache', $hook_data);
			$item['rendered-html'] = $hook_data['rendered-html'];
			$item['rendered-hash'] = $hook_data['rendered-hash'];
			unset($hook_data);

			// Update if the generated values differ from the existing ones
			if ((($rendered_hash != $item['rendered-hash']) || ($rendered_html != $item['rendered-html'])) && !empty($item['id'])) {
				self::update(
					[
						'rendered-html' => $item['rendered-html'],
						'rendered-hash' => $item['rendered-hash']
					],
					['id' => $item['id']]
				);
			}
		}

		$item['body'] = $body;
	}

	/**
	 * Find any non-embedded images in private items and add redir links to them
	 *
	 * @param array &$item The field array of an item row
	 */
	private static function addRedirToImageTags(array &$item)
	{
		$app = DI::app();

		$matches = [];
		$cnt = preg_match_all('|\[img\](http[^\[]*?/photo/[a-fA-F0-9]+?(-[0-9]\.[\w]+?)?)\[\/img\]|', $item['body'], $matches, PREG_SET_ORDER);
		if ($cnt) {
			foreach ($matches as $mtch) {
				if (strpos($mtch[1], '/redir') !== false) {
					continue;
				}

				if ((local_user() == $item['uid']) && ($item['private'] == self::PRIVATE) && ($item['contact-id'] != $app->contact['id']) && ($item['network'] == Protocol::DFRN)) {
					$img_url = 'redir/' . $item['contact-id'] . '?url=' . urlencode($mtch[1]);
					$item['body'] = str_replace($mtch[0], '[img]' . $img_url . '[/img]', $item['body']);
				}
			}
		}
	}

	/**
	 * Given an item array, convert the body element from bbcode to html and add smilie icons.
	 * If attach is true, also add icons for item attachments.
	 *
	 * @param array   $item
	 * @param boolean $attach
	 * @param boolean $is_preview
	 * @return string item body html
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 * @hook  prepare_body_init item array before any work
	 * @hook  prepare_body_content_filter ('item'=>item array, 'filter_reasons'=>string array) before first bbcode to html
	 * @hook  prepare_body ('item'=>item array, 'html'=>body string, 'is_preview'=>boolean, 'filter_reasons'=>string array) after first bbcode to html
	 * @hook  prepare_body_final ('item'=>item array, 'html'=>body string) after attach icons and blockquote special case handling (spoiler, author)
	 */
	public static function prepareBody(array &$item, $attach = false, $is_preview = false)
	{
		$a = DI::app();
		Hook::callAll('prepare_body_init', $item);

		// In order to provide theme developers more possibilities, event items
		// are treated differently.
		if ($item['object-type'] === Activity\ObjectType::EVENT && isset($item['event-id'])) {
			$ev = Event::getItemHTML($item);
			return $ev;
		}

		$tags = Tag::populateFromItem($item);

		$item['tags'] = $tags['tags'];
		$item['hashtags'] = $tags['hashtags'];
		$item['mentions'] = $tags['mentions'];

		// Compile eventual content filter reasons
		$filter_reasons = [];
		if (!$is_preview && public_contact() != $item['author-id']) {
			if (!empty($item['content-warning']) && (!local_user() || !DI::pConfig()->get(local_user(), 'system', 'disable_cw', false))) {
				$filter_reasons[] = DI::l10n()->t('Content warning: %s', $item['content-warning']);
			}

			$hook_data = [
				'item' => $item,
				'filter_reasons' => $filter_reasons
			];
			Hook::callAll('prepare_body_content_filter', $hook_data);
			$filter_reasons = $hook_data['filter_reasons'];
			unset($hook_data);
		}

		self::putInCache($item);
		$s = $item["rendered-html"];

		$hook_data = [
			'item' => $item,
			'html' => $s,
			'preview' => $is_preview,
			'filter_reasons' => $filter_reasons
		];
		Hook::callAll('prepare_body', $hook_data);
		$s = $hook_data['html'];
		unset($hook_data);

		if (!$attach) {
			// Replace the blockquotes with quotes that are used in mails.
			$mailquote = '<blockquote type="cite" class="gmail_quote" style="margin:0 0 0 .8ex;border-left:1px #ccc solid;padding-left:1ex;">';
			$s = str_replace(['<blockquote>', '<blockquote class="spoiler">', '<blockquote class="author">'], [$mailquote, $mailquote, $mailquote], $s);
			return $s;
		}

		$s = self::addMediaAttachments($item, $s);

		// Map.
		if (strpos($s, '<div class="map">') !== false && !empty($item['coord'])) {
			$x = Map::byCoordinates(trim($item['coord']));
			if ($x) {
				$s = preg_replace('/\<div class\=\"map\"\>/', '$0' . $x, $s);
			}
		}

		// Replace friendica image url size with theme preference.
		if (!empty($a->theme_info['item_image_size'])) {
			$ps = $a->theme_info['item_image_size'];
			$s = preg_replace('|(<img[^>]+src="[^"]+/photo/[0-9a-f]+)-[0-9]|', "$1-" . $ps, $s);
		}

		$s = HTML::applyContentFilter($s, $filter_reasons);

		$hook_data = ['item' => $item, 'html' => $s];
		Hook::callAll('prepare_body_final', $hook_data);

		return $hook_data['html'];
	}

	/**
	 * Add media attachments to the content
	 *
	 * @param array $item
	 * @param string $content
	 * @return modified content
	 */
	private static function addMediaAttachments(array $item, string $content)
	{
		$leading = '';
		$trailing = '';
		// currently deactivated the request for Post\Media::VIDEO since it creates mutliple videos from Peertube
		foreach (Post\Media::getByURIId($item['uri-id'], [Post\Media::AUDIO, 
			Post\Media::DOCUMENT, Post\Media::TORRENT, Post\Media::UNKNOWN]) as $attachment) {
			if (in_array($attachment['type'], [Post\Media::AUDIO, Post\Media::VIDEO]) && strpos($item['body'], $attachment['url'])) {
				continue;
			}

			$mime = $attachment['mimetype'];

			$author = ['uid' => 0, 'id' => $item['author-id'],
				'network' => $item['author-network'], 'url' => $item['author-link']];
			$the_url = Contact::magicLinkByContact($author, $attachment['url']);

			$filetype = strtolower(substr($mime, 0, strpos($mime, '/')));
			if ($filetype) {
				$filesubtype = strtolower(substr($mime, strpos($mime, '/') + 1));
				$filesubtype = str_replace('.', '-', $filesubtype);
			} else {
				$filetype = 'unkn';
				$filesubtype = 'unkn';
			}

			if (($filetype == 'video')) {
				/// @todo Move the template to /content as well
				$media = Renderer::replaceMacros(Renderer::getMarkupTemplate('video_top.tpl'), [
					'$video' => [
						'id'     => $item['author-id'],
						'src'    => $the_url,
						'mime'   => $mime,
					],
				]);
				if ($item['post-type'] == Item::PT_VIDEO) {
					$leading .= $media;
				} else {
					$trailing .= $media;
				}
			} elseif ($filetype == 'audio') {
				$media = Renderer::replaceMacros(Renderer::getMarkupTemplate('content/audio.tpl'), [
					'$audio' => [
						'id'     => $item['author-id'],
						'src'    => $the_url,
						'mime'   => $mime,
					],
				]);
				if ($item['post-type'] == Item::PT_AUDIO) {
					$leading .= $media;
				} else {
					$trailing .= $media;
				}
			} else {
				$title = Strings::escapeHtml(trim(($attachment['description'] ?? '') ?: $attachment['url']));

				if (!empty($attachment['size'])) {
					$title .= ' ' . $attachment['size'] . ' ' . DI::l10n()->t('bytes');
				}

				/// @todo Use a template
				$icon = '<div class="attachtype icon s22 type-' . $filetype . ' subtype-' . $filesubtype . '"></div>';
				$trailing .= '<a href="' . strip_tags($the_url) . '" title="' . $title . '" class="attachlink" target="_blank" rel="noopener noreferrer" >' . $icon . '</a>';
			}
		}

		if ($leading != '') {
			$content = '<div class="body-attach">' . $leading . '<div class="clear"></div></div>' . $content;
		}

		if ($trailing != '') {
			$content .= '<div class="body-attach">' . $trailing . '<div class="clear"></div></div>';
		}

		return $content;
	}

	/**
	 * get private link for item
	 *
	 * @param array $item
	 * @return boolean|array False if item has not plink, otherwise array('href'=>plink url, 'title'=>translated title)
	 * @throws \Exception
	 */
	public static function getPlink($item)
	{
		if (local_user()) {
			$ret = [
				'href' => "display/" . $item['guid'],
				'orig' => "display/" . $item['guid'],
				'title' => DI::l10n()->t('View on separate page'),
				'orig_title' => DI::l10n()->t('view on separate page'),
			];

			if (!empty($item['plink'])) {
				$ret["href"] = DI::baseUrl()->remove($item['plink']);
				$ret["title"] = DI::l10n()->t('link to source');
			}
		} elseif (!empty($item['plink']) && ($item['private'] != self::PRIVATE)) {
			$ret = [
				'href' => $item['plink'],
				'orig' => $item['plink'],
				'title' => DI::l10n()->t('link to source'),
			];
		} else {
			$ret = [];
		}

		return $ret;
	}

	/**
	 * Is the given item array a post that is sent as starting post to a forum?
	 *
	 * @param array $item
	 * @param array $owner
	 *
	 * @return boolean "true" when it is a forum post
	 */
	public static function isForumPost(array $item, array $owner = [])
	{
		if (empty($owner)) {
			$owner = User::getOwnerDataById($item['uid']);
			if (empty($owner)) {
				return false;
			}
		}

		if (($item['author-id'] == $item['owner-id']) ||
			($owner['id'] == $item['contact-id']) ||
			($item['uri-id'] != $item['parent-uri-id']) ||
			$item['origin']) {
			return false;
		}

		return Contact::isForum($item['contact-id']);
	}

	/**
	 * Search item id for given URI or plink
	 *
	 * @param string $uri
	 * @param integer $uid
	 *
	 * @return integer item id
	 */
	public static function searchByLink($uri, $uid = 0)
	{
		$ssl_uri = str_replace('http://', 'https://', $uri);
		$uris = [$uri, $ssl_uri, Strings::normaliseLink($uri)];

		$item = Post::selectFirst(['id'], ['uri' => $uris, 'uid' => $uid]);
		if (DBA::isResult($item)) {
			return $item['id'];
		}

		$item = Post::selectFirst(['id'], ['plink' => $uris, 'uid' => $uid]);
		if (DBA::isResult($item)) {
			return $item['id'];
		}

		return 0;
	}

	/**
	 * Return the URI for a link to the post 
	 * 
	 * @param string $uri URI or link to post
	 *
	 * @return string URI
	 */
	public static function getURIByLink(string $uri)
	{
		$ssl_uri = str_replace('http://', 'https://', $uri);
		$uris = [$uri, $ssl_uri, Strings::normaliseLink($uri)];

		$item = Post::selectFirst(['uri'], ['uri' => $uris]);
		if (DBA::isResult($item)) {
			return $item['uri'];
		}

		$item = Post::selectFirst(['uri'], ['plink' => $uris]);
		if (DBA::isResult($item)) {
			return $item['uri'];
		}

		return '';
	}

	/**
	 * Fetches item for given URI or plink
	 *
	 * @param string $uri
	 * @param integer $uid
	 *
	 * @return integer item id
	 */
	public static function fetchByLink(string $uri, int $uid = 0)
	{
		Logger::info('Trying to fetch link', ['uid' => $uid, 'uri' => $uri]);
		$item_id = self::searchByLink($uri, $uid);
		if (!empty($item_id)) {
			Logger::info('Link found', ['uid' => $uid, 'uri' => $uri, 'id' => $item_id]);
			return $item_id;
		}

		if ($fetched_uri = ActivityPub\Processor::fetchMissingActivity($uri)) {
			$item_id = self::searchByLink($fetched_uri, $uid);
		} else {
			$item_id = Diaspora::fetchByURL($uri);
		}

		if (!empty($item_id)) {
			Logger::info('Link fetched', ['uid' => $uid, 'uri' => $uri, 'id' => $item_id]);
			return $item_id;
		}

		Logger::info('Link not found', ['uid' => $uid, 'uri' => $uri]);
		return 0;
	}

	/**
	 * Return share data from an item array (if the item is shared item)
	 * We are providing the complete Item array, because at some time in the future
	 * we hopefully will define these values not in the body anymore but in some item fields.
	 * This function is meant to replace all similar functions in the system.
	 *
	 * @param array $item
	 *
	 * @return array with share information
	 */
	public static function getShareArray($item)
	{
		if (!preg_match("/(.*?)\[share(.*?)\]\s?(.*?)\s?\[\/share\]\s?/ism", $item['body'], $matches)) {
			return [];
		}

		$attribute_string = $matches[2];
		$attributes = ['comment' => trim($matches[1]), 'shared' => trim($matches[3])];
		foreach (['author', 'profile', 'avatar', 'guid', 'posted', 'link'] as $field) {
			if (preg_match("/$field=(['\"])(.+?)\\1/ism", $attribute_string, $matches)) {
				$attributes[$field] = trim(html_entity_decode($matches[2] ?? '', ENT_QUOTES, 'UTF-8'));
			}
		}
		return $attributes;
	}

	/**
	 * Fetch item information for shared items from the original items and adds it.
	 *
	 * @param array $item
	 *
	 * @return array item array with data from the original item
	 */
	public static function addShareDataFromOriginal(array $item)
	{
		$shared = self::getShareArray($item);
		if (empty($shared)) {
			return $item;
		}

		// Real reshares always have got a GUID.
		if (empty($shared['guid'])) {
			return $item;
		}

		$uid = $item['uid'] ?? 0;

		// first try to fetch the item via the GUID. This will work for all reshares that had been created on this system
		$shared_item = Post::selectFirst(['title', 'body'], ['guid' => $shared['guid'], 'uid' => [0, $uid]]);
		if (!DBA::isResult($shared_item)) {
			if (empty($shared['link'])) {
				return $item;
			}

			// Otherwhise try to find (and possibly fetch) the item via the link. This should work for Diaspora and ActivityPub posts
			$id = self::fetchByLink($shared['link'] ?? '', $uid);
			if (empty($id)) {
				Logger::info('Original item not found', ['url' => $shared['link'] ?? '', 'callstack' => System::callstack()]);
				return $item;
			}

			$shared_item = Post::selectFirst(['title', 'body'], ['id' => $id]);
			if (!DBA::isResult($shared_item)) {
				return $item;
			}
			Logger::info('Got shared data from url', ['url' => $shared['link'], 'callstack' => System::callstack()]);
		} else {
			Logger::info('Got shared data from guid', ['guid' => $shared['guid'], 'callstack' => System::callstack()]);
		}

		if (!empty($shared_item['title'])) {
			$body = '[h3]' . $shared_item['title'] . "[/h3]\n" . $shared_item['body'];
			unset($shared_item['title']);
		} else {
			$body = $shared_item['body'];
		}

		$item['body'] = preg_replace("/\[share ([^\[\]]*)\].*\[\/share\]/ism", '[share $1]' . $body . '[/share]', $item['body']);
		unset($shared_item['body']);

		return array_merge($item, $shared_item);
	}

	/**
	 * Check a prospective item array against user-level permissions
	 *
	 * @param array $item Expected keys: uri, gravity, and
	 *                    author-link if is author-id is set,
	 *                    owner-link if is owner-id is set,
	 *                    causer-link if is causer-id is set.
	 * @param int   $user_id Local user ID
	 * @return bool
	 * @throws \Exception
	 */
	protected static function isAllowedByUser(array $item, int $user_id)
	{
		if (!empty($item['author-id']) && Contact\User::isBlocked($item['author-id'], $user_id)) {
			Logger::notice('Author is blocked by user', ['author-link' => $item['author-link'], 'uid' => $user_id, 'item-uri' => $item['uri']]);
			return false;
		}

		if (!empty($item['owner-id']) && Contact\User::isBlocked($item['owner-id'], $user_id)) {
			Logger::notice('Owner is blocked by user', ['owner-link' => $item['owner-link'], 'uid' => $user_id, 'item-uri' => $item['uri']]);
			return false;
		}

		// The causer is set during a thread completion, for example because of a reshare. It countains the responsible actor.
		if (!empty($item['causer-id']) && Contact\User::isBlocked($item['causer-id'], $user_id)) {
			Logger::notice('Causer is blocked by user', ['causer-link' => $item['causer-link'] ?? $item['causer-id'], 'uid' => $user_id, 'item-uri' => $item['uri']]);
			return false;
		}

		if (!empty($item['causer-id']) && ($item['gravity'] === GRAVITY_PARENT) && Contact\User::isIgnored($item['causer-id'], $user_id)) {
			Logger::notice('Causer is ignored by user', ['causer-link' => $item['causer-link'] ?? $item['causer-id'], 'uid' => $user_id, 'item-uri' => $item['uri']]);
			return false;
		}

		return true;
	}
}
