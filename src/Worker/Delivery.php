<?php
/**
 * @file src/Worker/Delivery.php
 */
namespace Friendica\Worker;

use Friendica\Core\Config;
use Friendica\Core\L10n;
use Friendica\Core\System;
use Friendica\Database\DBM;
use Friendica\Model\Contact;
use Friendica\Model\Item;
use Friendica\Model\Queue;
use Friendica\Model\User;
use Friendica\Protocol\DFRN;
use Friendica\Protocol\Diaspora;
use Friendica\Protocol\Email;
use dba;

require_once 'include/items.php';

/// @todo This is some ugly code that needs to be split into several methods

class Delivery {
	public static function execute($cmd, $item_id, $contact_id) {
		global $a;

		logger('delivery: invoked: '.$cmd.': '.$item_id.' to '.$contact_id, LOGGER_DEBUG);

		$top_level = false;
		$recipients = [];
		$followup = false;

		$recipients[] = $contact_id;

		if ($cmd == DELIVER_MAIL) {
			$target_item = dba::selectFirst('mail', [], ['id' => $item_id]);
			if (!DBM::is_result($message)) {
				return;
			}
			$uid = $target_item['uid'];
			$recipients[] = $target_item['contact-id'];
		} elseif ($cmd == DELIVER_SUGGESTION) {
			$target_item = dba::selectFirst('fsuggest', [], ['id' => $item_id]);
			if (!DBM::is_result($message)) {
				return;
			}
			$uid = $target_item['uid'];
			$recipients[] = $target_item['contact-id'];
		} elseif ($cmd == DELIVER_RELOCATION) {
			$uid = $item_id;
		} else {
			// find ancestors
			$target_item = dba::fetch_first("SELECT `item`.*, `contact`.`uid` AS `cuid`,
								`sign`.`signed_text`,`sign`.`signature`,`sign`.`signer`
							FROM `item`
							INNER JOIN `contact` ON `contact`.`id` = `item`.`contact-id`
							LEFT JOIN `sign` ON `sign`.`iid` = `item`.`id`
							WHERE `item`.`id` = ? AND `visible` AND NOT `moderated`", $item_id);

			if (!DBM::is_result($target_item) || !intval($target_item['parent'])) {
				return;
			}

			$parent_id = intval($target_item['parent']);
			$uid = $target_item['cuid'];
			$updated = $target_item['edited'];

			$items = q("SELECT `item`.*, `sign`.`signed_text`,`sign`.`signature`,`sign`.`signer`
				FROM `item` LEFT JOIN `sign` ON `sign`.`iid` = `item`.`id`
				WHERE `parent` = %d AND visible = 1 AND moderated = 0 ORDER BY `id` ASC",
				intval($parent_id)
			);

			if (!DBM::is_result($items)) {
				return;
			}

			$icontacts = null;
			$contacts_arr = [];
			foreach ($items as $item) {
				if (!in_array($item['contact-id'],$contacts_arr)) {
					$contacts_arr[] = intval($item['contact-id']);
				}
			}
			if (count($contacts_arr)) {
				$str_contacts = implode(',',$contacts_arr);
				$icontacts = q("SELECT * FROM `contact`
					WHERE `id` IN ( $str_contacts ) "
				);
			}
			if (!DBM::is_result($icontacts)) {
				return;
			}

			// avoid race condition with deleting entries

			if ($items[0]['deleted']) {
				foreach ($items as $item) {
					$item['deleted'] = 1;
				}
			}

			// When commenting too fast after delivery, a post wasn't recognized as top level post.
			// The count then showed more than one entry. The additional check should help.
			// The check for the "count" should be superfluous, but I'm not totally sure by now, so we keep it.
			if ((($items[0]['id'] == $item_id) || (count($items) == 1)) && ($items[0]['uri'] === $items[0]['parent-uri'])) {
				logger('delivery: top level post');
				$top_level = true;
			}
		}

		$owner = User::getOwnerDataById($uid);
		if (!$owner) {
			return;
		}

		// We don't treat Forum posts as "wall-to-wall" to be able to post them via Diaspora
		$walltowall = $top_level && ($owner['id'] != $items[0]['contact-id']) & ($owner['account-type'] != ACCOUNT_TYPE_COMMUNITY);

		$public_message = true;

		if (!in_array($cmd, [DELIVER_MAIL, DELIVER_SUGGESTION, DELIVER_RELOCATION])) {
			$parent = $items[0];

			// This is IMPORTANT!!!!

			// We will only send a "notify owner to relay" or followup message if the referenced post
			// originated on our system by virtue of having our hostname somewhere
			// in the URI, AND it was a comment (not top_level) AND the parent originated elsewhere.
			// if $parent['wall'] == 1 we will already have the parent message in our array
			// and we will relay the whole lot.

			$localhost = $a->get_hostname();
			if (strpos($localhost,':')) {
				$localhost = substr($localhost,0,strpos($localhost,':'));
			}
			/**
			 *
			 * Be VERY CAREFUL if you make any changes to the following line. Seemingly innocuous changes
			 * have been known to cause runaway conditions which affected several servers, along with
			 * permissions issues.
			 *
			 */

			if (!$top_level && ($parent['wall'] == 0) && stristr($target_item['uri'], $localhost)) {
				logger('followup '.$target_item["guid"], LOGGER_DEBUG);
				// local followup to remote post
				$followup = true;
			}

			if (strlen($parent['allow_cid'])
				|| strlen($parent['allow_gid'])
				|| strlen($parent['deny_cid'])
				|| strlen($parent['deny_gid'])
				|| $parent["private"]) {
				$public_message = false; // private recipients, not public
			}
		}

		// We don't deliver our items to blocked or pending contacts, and not to ourselves either
		$contact = dba::selectFirst('contact', [],
			['id' => $contact_id, 'blocked' => false, 'pending' => false, 'self' => false]
		);
		if (!DBM::is_result($contact)) {
			return;
		}

		$deliver_status = 0;

		logger("Delivering " . $cmd . " followup=$followup - network ".$contact['network']);

		switch ($contact['network']) {

			case NETWORK_DFRN:
				self::deliverDFRN($cmd, $contact, $icontacts, $owner, $items, $target_item, $public_message, $top_level, $followup);
				break;

			case NETWORK_DIASPORA:
				self::deliverDiaspora($cmd, $contact, $owner, $target_item, $public_message, $top_level, $followup, $walltowall);
				break;

			case NETWORK_OSTATUS:
				// Do not send to otatus if we are not configured to send to public networks
				if ($owner['prvnets']) {
					break;
				}
				if (Config::get('system','ostatus_disabled') || Config::get('system','dfrn_only')) {
					break;
				}

				// There is currently no code here to distribute anything to OStatus.
				// This is done in "notifier.php" (See "url_recipients" and "push_notify")
				break;

			case NETWORK_MAIL:
				self::deliverMail($cmd, $contact, $owner, $target_item);
				break;

			default:
				break;
		}

		return;
	}

	private static function deliverDFRN($cmd, $contact, $icontacts, $owner, $items, $target_item, $public_message, $top_level, $followup)
	{
		logger('notifier: '.$target_item["guid"].' dfrndelivery: '.$contact['name']);

		if ($cmd == DELIVER_MAIL) {
			$item = $target_item;
			$item['body'] = Item::fixPrivatePhotos($item['body'], $owner['uid'], null, $item['contact-id']);
			$atom = DFRN::mail($item, $owner);
		} elseif ($cmd == DELIVER_SUGGESTION) {
			$item = $target_item;
			$atom = DFRN::fsuggest($item, $owner);
			dba::delete('fsuggest', ['id' => $item['id']]);
		} elseif ($cmd == DELIVER_RELOCATION) {
			$atom = DFRN::relocate($owner, $owner['uid']);
		} elseif ($followup) {
			$msgitems = [];
			$msgitems[] = $target_item;
			$atom = DFRN::entries($msgitems, $owner);
		} else {
			$msgitems = [];
			foreach ($items as $item) {
				if (!$item['parent']) {
					return;
				}

				// private emails may be in included in public conversations. Filter them.
				if ($public_message && $item['private']) {
					return;
				}

				$item_contact = self::getItemContact($item, $icontacts);
				if (!$item_contact) {
					return;
				}

				if (!in_array($cmd, [DELIVER_MAIL, DELIVER_SUGGESTION, DELIVER_RELOCATION])) {
					// Only add the parent when we don't delete other items.
					if ($target_item['id'] == $item['id'] || (($item['id'] == $item['parent']) && ($cmd != DELIVER_DELETION))) {
						$item["entry:comment-allow"] = true;
						$item["entry:cid"] = ($top_level ? $contact['id'] : 0);
						$msgitems[] = $item;
					}
				} else {
					$item["entry:comment-allow"] = true;
					$msgitems[] = $item;
				}
			}
			$atom = DFRN::entries($msgitems,$owner);
		}

		logger('notifier entry: '.$contact["url"].' '.$target_item["guid"].' entry: '.$atom, LOGGER_DEBUG);

		logger('notifier: '.$atom, LOGGER_DATA);
		$basepath =  implode('/', array_slice(explode('/',$contact['url']),0,3));

		// perform local delivery if we are on the same site

		if (link_compare($basepath, System::baseUrl())) {
			$condition = ['nurl' => normalise_link($contact['url']), 'self' => true];
			$target_self = dba::selectFirst('contact', ['uid'], $condition);
			if (!DBM::is_result($target_self)) {
				return;
			}
			$target_uid = $target_self['uid'];

			// Check if the user has got this contact
			$cid = Contact::getIdForURL($owner['url'], $target_uid);
			if (!$cid) {
				// Otherwise there should be a public contact
				$cid = Contact::getIdForURL($owner['url']);
				if (!$cid) {
					return;
				}
			}

			// We now have some contact, so we fetch it
			$target_importer = dba::fetch_first("SELECT *, `name` as `senderName`
							FROM `contact`
							WHERE NOT `blocked` AND `id` = ? LIMIT 1",
							$cid);

			// This should never fail
			if (!DBM::is_result($target_importer)) {
				return;
			}

			// Set the user id. This is important if this is a public contact
			$target_importer['importer_uid']  = $target_uid;
			DFRN::import($atom, $target_importer);
			return;
		}

		if ($items[0]['uid'] == 0) {
			$deliver_status = DFRN::transmit($owner, $contact, $atom);
			if ($deliver_status < 200) {
				// Transmit via Diaspora if not possible via Friendica
				self::deliverDiaspora($cmd, $contact, $owner, $target_item, $public_message, $top_level, $followup, false);
				return;
			}
			$deliver_status = DFRN::deliver($owner, $contact, $atom);
		}

		logger('notifier: dfrn_delivery to '.$contact["url"].' with guid '.$target_item["guid"].' returns '.$deliver_status);

		if ($deliver_status < 0) {
			logger('notifier: delivery failed: queuing message');
			Queue::add($contact['id'], NETWORK_DFRN, $atom, false, $target_item['guid']);
		}

		if (($deliver_status >= 200) && ($deliver_status <= 299)) {
			// We successfully delivered a message, the contact is alive
			Contact::unmarkForArchival($contact);
		} else {
			// The message could not be delivered. We mark the contact as "dead"
			Contact::markForArchival($contact);
		}
	}

	private static function deliverDiaspora($cmd, $contact, $owner, $target_item, $public_message, $top_level, $followup, $walltowall)
	{
		if ($public_message) {
			$loc = 'public batch ' . $contact['batch'];
		} else {
			$loc = $contact['name'];
		}

		logger('delivery: diaspora batch deliver: ' . $loc);

		if (Config::get('system', 'dfrn_only') || !Config::get('system', 'diaspora_enabled')) {
			return;
		}
		if ($cmd == DELIVER_MAIL) {
			Diaspora::sendMail($target_item, $owner, $contact);
			return;
		}

		if ($cmd == DELIVER_SUGGESTION) {
			return;
		}
		if (!$contact['pubkey'] && !$public_message) {
			return;
		}
		if (($target_item['deleted']) && (($target_item['uri'] === $target_item['parent-uri']) || $followup)) {
			// top-level retraction
			logger('diaspora retract: ' . $loc);
			Diaspora::sendRetraction($target_item, $owner, $contact, $public_message);
			return;
		} elseif ($cmd == DELIVER_RELOCATION) {
			Diaspora::sendAccountMigration($owner, $contact, $owner['uid']);
			return;
		} elseif ($followup) {
			// send comments and likes to owner to relay
			logger('diaspora followup: ' . $loc);
			Diaspora::sendFollowup($target_item, $owner, $contact, $public_message);
			return;
		} elseif ($target_item['uri'] !== $target_item['parent-uri']) {
			// we are the relay - send comments, likes and relayable_retractions to our conversants
			logger('diaspora relay: ' . $loc);
			Diaspora::sendRelay($target_item, $owner, $contact, $public_message);
			return;
		} elseif ($top_level && !$walltowall) {
			// currently no workable solution for sending walltowall
			logger('diaspora status: ' . $loc);
			Diaspora::sendStatus($target_item, $owner, $contact, $public_message);
			return;
		}

		logger('Unknown mode ' . $cmd . ' for '.$contact['name']);
	}

	private static function deliverMail($cmd, $contact, $owner, $target_item)
	{
		global $a;

		if (Config::get('system','dfrn_only')) {
			return;
		}
		// WARNING: does not currently convert to RFC2047 header encodings, etc.

		$addr = $contact['addr'];
		if (!strlen($addr)) {
			return;
		}

		if (!in_array($cmd, [DELIVER_POST, DELIVER_COMMENT])) {
			return;
		}

		$local_user = dba::selectFirst('user', [], ['uid' => $owner['uid']]);
		if (!DBM::is_result($local_user)) {
			return;
		}

		$reply_to = '';
		$mailacct = dba::selectFirst('mailacct', ['reply_to'], ['uid' => $owner['uid']]);
		if (DBM::is_result($mailacct) && !empty($mailacct['reply_to'])) {
			$reply_to = $mailacct['reply_to'];
		}

		$subject  = ($target_item['title'] ? Email::encodeHeader($target_item['title'], 'UTF-8') : L10n::t("\x28no subject\x29"));

		// only expose our real email address to true friends

		if (($contact['rel'] == CONTACT_IS_FRIEND) && !$contact['blocked']) {
			if ($reply_to) {
				$headers  = 'From: ' . Email::encodeHeader($local_user['username'],'UTF-8') . ' <' . $reply_to.'>' . "\n";
				$headers .= 'Sender: ' . $local_user['email'] . "\n";
			} else {
				$headers  = 'From: ' . Email::encodeHeader($local_user['username'],'UTF-8').' <' . $local_user['email'] . '>' . "\n";
			}
		} else {
			$headers  = 'From: '. Email::encodeHeader($local_user['username'], 'UTF-8') . ' <noreply@' . $a->get_hostname() . '>' . "\n";
		}

		$headers .= 'Message-Id: <' . Email::iri2msgid($target_item['uri']) . '>' . "\n";

		if ($target_item['uri'] !== $target_item['parent-uri']) {
			$headers .= "References: <" . Email::iri2msgid($target_item["parent-uri"]) . ">";

			// If Threading is enabled, write down the correct parent
			if (($target_item["thr-parent"] != "") && ($target_item["thr-parent"] != $target_item["parent-uri"])) {
				$headers .= " <".Email::iri2msgid($target_item["thr-parent"]).">";
			}
			$headers .= "\n";

			if (empty($target_item['title'])) {
				$condition = ['uri' => $target_item['parent-uri'], 'uid' => $owner['uid']];
				$title = dba::selectFirst('item', ['title'], $condition);
				if (DBM::is_result($title) && ($title['title'] != '')) {
					$subject = $title['title'];
				} else {
					$condition = ['parent-uri' => $target_item['parent-uri'], 'uid' => $owner['uid']];
					$title = dba::selectFirst('item', ['title'], $condition);
					if (DBM::is_result($title) && ($title['title'] != '')) {
						$subject = $title['title'];
					}
				}
			}
			if (strncasecmp($subject,  'RE:', 3)) {
				$subject = 'Re: ' . $subject;
			}
		}
		Email::send($addr, $subject, $headers, $target_item);
	}

	private static function getItemContact($item, $contacts)
	{
		if (!count($contacts) || !is_array($item)) {
			return false;
		}
		foreach ($contacts as $contact) {
			if ($contact['id'] == $item['contact-id']) {
				return $contact;
			}
		}
		return false;
	}
}
