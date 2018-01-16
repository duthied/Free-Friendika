<?php

/**
 * @file src/Model/Item.php
 */

namespace Friendica\Model;

use Friendica\Core\Worker;
use Friendica\Model\Term;
use Friendica\Model\Contact;
use Friendica\Database\DBM;
use dba;

require_once 'include/tags.php';
require_once 'include/threads.php';

class Item
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

			create_tags_from_item($item['id']);
			Term::createFromItem($item['id']);
			update_thread($item['id']);

			Worker::add(PRIORITY_HIGH, "Notifier", 'edit_post', $item['id']);
		}

		return $rows;
	}

	public static function delete(array $condition, $priority = PRIORITY_HIGH)
	{
/*
		// locate item to be deleted
		$item = dba::selectFirst('item', [], $condition);
		if (!DBM::is_result($item)) {
			return false;
		}

		if ($item['deleted']) {
			return false;
		}

		$owner = $item['uid'];

		$contact_id = 0;

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

		//*
		// * If item is a link to a photo resource, nuke all the associated photos
		// * (visitors will not have photo resources)
		// * This only applies to photos uploaded from the photos page. Photos inserted into a post do not
		// * generate a resource-id and therefore aren't intimately linked to the item.
		// *
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
		// Don't delete it here, since we have to send delete messages
		dba::update('item', ['deleted' => true, 'title' => '', 'body' => '',
					'edited' => datetime_convert(), 'changed' => datetime_convert()],
				['id' => $item['id']]);

		create_tags_from_item($item['id']);
		Term::createFromItem($item['id']);
		delete_thread($item['id'], $item['parent-uri']);

		// Creating list of parents
		$r = q("SELECT `id` FROM `item` WHERE `parent` = %d AND `uid` = %d",
			intval($item['id']),
			intval($item['uid'])
		);

		$parentid = "";

		foreach ($r as $row) {
			if ($parentid != "") {
				$parentid .= ", ";
			}

			$parentid .= $row["id"];
		}

		// Now delete them
		if ($parentid != "") {
			q("DELETE FROM `sign` WHERE `iid` IN (%s)", dbesc($parentid));
		}

		// If it's the parent of a comment thread, kill all the kids
		if ($item['uri'] == $item['parent-uri']) {
			dba::update('item', ['deleted' => true, 'title' => '', 'body' => '',
					'edited' => datetime_convert(), 'changed' => datetime_convert()],
				['parent-uri' => $item['parent-uri'], 'uid' => $item['uid']]);

			create_tags_from_itemuri($item['parent-uri'], $item['uid']);
			Term::createFromItemURI($item['parent-uri'], $item['uid']);
			delete_thread_uri($item['parent-uri'], $item['uid']);
			// ignore the result
		} else {
			// ensure that last-child is set in case the comment that had it just got wiped.
			dba::update('item', ['last-child' => false, 'changed' => datetime_convert()],
					['parent-uri' => $item['parent-uri'], 'uid' => $item['uid']]);

			// who is the last child now?
			$r = q("SELECT `id` FROM `item` WHERE `parent-uri` = '%s' AND `type` != 'activity' AND `deleted` = 0 AND `uid` = %d ORDER BY `edited` DESC LIMIT 1",
				dbesc($item['parent-uri']),
				intval($item['uid'])
			);
			if (DBM::is_result($r)) {
				dba::update('item', ['last-child' => true], ['id' => $r[0]['id']]);
			}
		}

		// send the notification upstream/downstream
		Worker::add(['priority' => $priority, 'dont_fork' => true], "Notifier", "drop", intval($item['id']));

		return true;
*/
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

		if (count($item) && ($item["allow_cid"] == '')  && ($item["allow_gid"] == '') &&
			($item["deny_cid"] == '') && ($item["deny_gid"] == '')) {

			if (!dba::exists('item', ['uri' => $item['uri'], 'uid' => 0])) {
				// Preparing public shadow (removing user specific data)
				require_once("include/items.php");

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

				$public_shadow = item_store($item, false, false, true);

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
		require_once("include/items.php");

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

		$public_shadow = item_store($item, false, false, true);

		logger("Stored public shadow for comment ".$item['uri']." under id ".$public_shadow, LOGGER_DEBUG);
	}
}
