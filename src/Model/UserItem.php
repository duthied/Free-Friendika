<?php

/**
 * @file src/Model/UserItem.php
 */

namespace Friendica\Model;

use Friendica\Database\DBA;

class UserItem
{
	/**
	 * Checks an item for notifications and sets the "notification-type" field
	 *
	 * @param array $item The message array that is checked for notifications
	 * @param int   $uid  User ID
	 */
	public static function setNotification($item, $uid)
	{
	}

	private static function checkShared($item, $uid)
	{
		if ($item['gravity'] != GRAVITY_PARENT) {
			return false;
		}

		// Send a notification for every new post?
		// Either the contact had posted something directly
		if (DBA::exists('contact', ['id' => $item['contact-id'], 'notify_new_posts' => true])) {
			return true;
		}

		// Or the contact is a mentioned forum
		$tags = DBA::select('term', ['url'], ['otype' => TERM_OBJ_POST, 'oid' => $itemid, 'type' => TERM_MENTION, 'uid' => $uid]);
		while ($tag = DBA::fetch($tags)) {
			$condition = ['nurl' => Strings::normaliseLink($tag["url"]), 'uid' => $uid, 'notify_new_posts' => true, 'contact-type' => Contact::TYPE_COMMUNITY];
			if (DBA::exists('contact', $condition)) {
				return true;
			}
		}

		return false;
	}

	// Is the user mentioned in this post?
	private static function checkTagged($item, $uid)
	{
		if ($item["mention"]) {
			return true;
		}

		foreach ($profiles AS $profile) {
			if (strpos($item["tag"], "=".$profile."]") || strpos($item["body"], "=".$profile."]"))
				return true;
		}

		if ($defaulttype == NOTIFY_TAGSELF) {
			return true;
		}

		return false;
	}

	private static function checkCommented($item, $uid)
	{
		// Is it a post that the user had started?
		$fields = ['ignored', 'mention'];
		$thread = Item::selectFirstThreadForUser($uid, $fields, ['iid' => $item["parent"], 'deleted' => false]);

		if ($thread['mention'] && !$thread['ignored']) {
			return true;
		}

		// And now we check for participation of one of our contacts in the thread
		$condition = ['parent' => $item["parent"], 'author-id' => $contacts, 'deleted' => false];

		if (!$thread['ignored'] && Item::exists($condition)) {
			return true;
		}

		return false;
	}
}
