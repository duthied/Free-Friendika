<?php

/**
 * @file src/Model/UserItem.php
 */

namespace Friendica\Model;

use Friendica\Core\Logger;
use Friendica\Core\Hook;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Util\Strings;

class UserItem
{
	const NOTIF_NONE = 0;
	const NOTIF_EXPLICIT_TAGGED = 1;
	const NOTIF_IMPLICIT_TAGGED = 2;
	const NOTIF_THREAD_COMMENT = 4;
	const NOTIF_DIRECT_COMMENT = 8;
	const NOTIF_COMMENT_PARTICIPATION = 16;
	const NOTIF_ACTIVITY_PARTICIPATION = 32;
	const NOTIF_SHARED = 128;

	/**
	 * Checks an item for notifications and sets the "notification-type" field
	 *
	 * @param array $item The message array that is checked for notifications
	 * @param int   $uid  User ID
	 */
	public static function setNotification($iid, $uid)
	{
		$fields = ['id', 'body', 'origin', 'parent', 'gravity', 'tag', 'contact-id',
			'thr-parent', 'parent-uri', 'mention'];
		$item = Item::selectFirst($fields, ['id' => $iid]);

		// Don't check for own posts
		if ($item['origin'] || empty($uid)) {
			return;
		}

		$fields = ['ignored', 'mention'];
		$thread = Item::selectFirstThreadForUser($uid, $fields, ['iid' => $item['parent'], 'deleted' => false]);
		if ($thread['ignored']) {
			return;
		}

		$notification_type = self::NOTIF_NONE;

		if (self::checkShared($item, $uid)) {
			$notification_type = $notification_type | self::NOTIF_SHARED;
		}

		$profiles = self::getProfileForUser($uid);

		if (self::checkImplicitMention($item, $uid, $profiles)) {
			$notification_type = $notification_type | self::NOTIF_IMPLICIT_TAGGED;
		}

		if (self::checkExplicitMention($item, $uid, $profiles)) {
			$notification_type = $notification_type | self::NOTIF_EXPLICIT_TAGGED;
		}

		$contacts = [];
		$ret = DBA::select('contact', ['id'], ['uid' => 0, 'nurl' => $profiles]);
		while ($contact = DBA::fetch($ret)) {
			$contacts[] = $contact['id'];
		}
		DBA::close($ret);

		if (self::checkCommentedThread($item, $uid, $contacts)) {
			$notification_type = $notification_type | self::NOTIF_THREAD_COMMENT;
		}

		if (self::checkDirectComment($item, $uid, $contacts, $thread)) {
			$notification_type = $notification_type | self::NOTIF_DIRECT_COMMENT;
		}

		if (self::checkCommentedParticipation($item, $uid, $contacts)) {
			$notification_type = $notification_type | self::NOTIF_COMMENT_PARTICIPATION;
		}

		if (self::checkActivityParticipation($item, $uid, $contacts)) {
			$notification_type = $notification_type | self::NOTIF_ACTIVITY_PARTICIPATION;
		}

		if (empty($notification_type)) {
			return;
		}

		Logger::info('Set notification', ['iid' => $item['id'], 'uid' => $uid, 'notification-type' => $notification_type]);

		DBA::update('user-item', ['notification-type' => $notification_type], ['iid' => $item['id'], 'uid' => $uid], true);
	}

	// Fetch all contacts for the given profiles
	private static function getProfileForUser($uid)
	{
		$notification_data = ['uid' => $uid, 'profiles' => []];
		Hook::callAll('check_item_notification', $notification_data);

		$profiles = $notification_data['profiles'];

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
		$profiles[] = $owner['url'];

		// Notifications from Diaspora are often with an URL in the Diaspora format
		$profiles[] = DI::baseUrl().'/u/'.$user['nickname'];

		$profiles2 = [];

		foreach ($profiles AS $profile) {
			// Check for invalid profile urls. 13 should be the shortest possible profile length:
			// http://a.bc/d
			// Additionally check for invalid urls that would return the normalised value "http:"
			if ((strlen($profile) >= 13) && (Strings::normaliseLink($profile) != 'http:')) {
				if (!in_array($profile, $profiles2))
					$profiles2[] = $profile;

				$profile = Strings::normaliseLink($profile);
				if (!in_array($profile, $profiles2))
					$profiles2[] = $profile;

				$profile = str_replace('http://', 'https://', $profile);
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
		$tags = DBA::select('term', ['url'], ['otype' => TERM_OBJ_POST, 'oid' => $item['id'], 'type' => TERM_MENTION, 'uid' => $uid]);
		while ($tag = DBA::fetch($tags)) {
			$condition = ['nurl' => Strings::normaliseLink($tag['url']), 'uid' => $uid, 'notify_new_posts' => true, 'contact-type' => Contact::TYPE_COMMUNITY];
			if (DBA::exists('contact', $condition)) {
				return true;
			}
		}

		return false;
	}

	// Is the user mentioned in this post?
	private static function checkImplicitMention($item, $uid, $profiles)
	{
		foreach ($profiles AS $profile) {
			if (strpos($item['tag'], '='.$profile.']') || strpos($item['body'], '='.$profile.']'))
				return true;
		}

		return false;
	}

	private static function checkExplicitMention($item, $uid, $profiles)
	{
		foreach ($profiles AS $profile) {
			if (strpos($item['tag'], '='.$profile.']') || strpos($item['body'], '='.$profile.']'))
				return !(strpos($item['body'], $profile) === false);
		}

		return false;
	}

	// Is it a post that the user had started?
	private static function checkCommentedThread($item, $uid, $contacts)
	{
		// Additional check for connector posts
		$condition = ['parent' => $item['parent'], 'author-id' => $contacts, 'deleted' => false, 'gravity' => GRAVITY_PARENT];
		return Item::exists($condition);
	}

	private static function checkDirectComment($item, $uid, $contacts)
	{
		// Additional check for connector posts
		$condition = ['uri' => $item['thr-parent'], 'uid' => [0, $uid], 'author-id' => $contacts, 'deleted' => false, 'gravity' => GRAVITY_COMMENT];
		return Item::exists($condition);
	}

	// Check for participation of one of our contacts in the thread
	private static function checkCommentedParticipation($item, $uid, $contacts)
	{
		$condition = ['parent' => $item['parent'], 'author-id' => $contacts, 'deleted' => false, 'gravity' => GRAVITY_COMMENT];
		return Item::exists($condition);
	}

	private static function checkActivityParticipation($item, $uid, $contacts)
	{
		$condition = ['parent' => $item['parent'], 'author-id' => $contacts, 'deleted' => false, 'gravity' => GRAVITY_ACTIVITY];
		return Item::exists($condition);
	}
}
