<?php

/**
 * @file src/Model/UserItem.php
 */

namespace Friendica\Model;

use Friendica\Core\Hook;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Util\Strings;

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
		if (self::checkShared($item, $uid)) {
			echo "shared\n";
		}

		$profiles = self::getProfileForUser($uid);

		if (self::checkTagged($item, $uid, $profiles)) {
			echo "tagged\n";
		}

		if (self::checkCommented($item, $uid, $profiles)) {
			echo "commented\n";
		}
	}

	private static function getProfileForUser($uid)
	{
		$notification_data = ["uid" => $uid, "profiles" => []];
		Hook::callAll('check_item_notification', $notification_data);

		$profiles = $notification_data["profiles"];

		$fields = ['nickname'];
		$user = DBA::selectFirst('user', $fields, ['uid' => $uid]);
		if (!DBA::isResult($user)) {
			return false;
		}

		$owner = DBA::selectFirst('contact', ['url'], ['self' => true, 'uid' => $uid]);
		if (!DBA::isResult($owner)) {
			return false;
		}

		// This is our regular URL format
		$profiles[] = $owner["url"];

		// Notifications from Diaspora are often with an URL in the Diaspora format
		$profiles[] = DI::baseUrl()."/u/".$user["nickname"];

		$profiles2 = [];

		foreach ($profiles AS $profile) {
			// Check for invalid profile urls. 13 should be the shortest possible profile length:
			// http://a.bc/d
			// Additionally check for invalid urls that would return the normalised value "http:"
			if ((strlen($profile) >= 13) && (Strings::normaliseLink($profile) != "http:")) {
				if (!in_array($profile, $profiles2))
					$profiles2[] = $profile;

				$profile = Strings::normaliseLink($profile);
				if (!in_array($profile, $profiles2))
					$profiles2[] = $profile;

				$profile = str_replace("http://", "https://", $profile);
				if (!in_array($profile, $profiles2))
					$profiles2[] = $profile;
			}
		}

		return $profiles2;
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
	private static function checkTagged($item, $uid, $profiles)
	{
		foreach ($profiles AS $profile) {
			if (strpos($item["tag"], "=".$profile."]") || strpos($item["body"], "=".$profile."]"))
				return true;
		}

		return false;
	}

	// Fetch all contacts for the given profiles
	private static function checkCommented($item, $uid, $profiles)
	{
		// Is it a post that the user had started?
		$fields = ['ignored', 'mention'];
		$thread = Item::selectFirstThreadForUser($uid, $fields, ['iid' => $item["parent"], 'deleted' => false]);
		if ($thread['mention'] && !$thread['ignored']) {
			return true;
		}

		$contacts = [];
		$ret = DBA::select('contact', ['id'], ['uid' => 0, 'nurl' => $profiles]);
		while ($contact = DBA::fetch($ret)) {
			$contacts[] = $contact['id'];
		}
		DBA::close($ret);

		// And now we check for participation of one of our contacts in the thread
		$condition = ['parent' => $item["parent"], 'author-id' => $contacts, 'deleted' => false];

		if (!$thread['ignored'] && Item::exists($condition)) {
			return true;
		}

		return false;
	}
}
