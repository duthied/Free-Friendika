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

use Friendica\BaseModel;
use Friendica\Content\Text\BBCode;
use Friendica\Content\Text\Plaintext;
use Friendica\Core\Logger;
use Friendica\Database\Database;
use Friendica\DI;
use Friendica\Network\HTTPException\InternalServerErrorException;
use Friendica\Protocol\Activity;
use Psr\Log\LoggerInterface;

/**
 * Model for an entry in the notify table
 *
 * @property string  hash
 * @property integer type
 * @property string  name   Full name of the contact subject
 * @property string  url    Profile page URL of the contact subject
 * @property string  photo  Profile photo URL of the contact subject
 * @property string  date   YYYY-MM-DD hh:mm:ss local server time
 * @property string  msg
 * @property integer uid  	Owner User Id
 * @property string  link   Notification URL
 * @property integer iid  	Item Id
 * @property integer parent Parent Item Id
 * @property boolean seen   Whether the notification was read or not.
 * @property string  verb   Verb URL (@see http://activitystrea.ms)
 * @property string  otype  Subject type ('item', 'intro' or 'mail')
 *
 * @property-read string name_cache Full name of the contact subject
 * @property-read string msg_cache  Plaintext version of the notification text with a placeholder (`{0}`) for the subject contact's name.
 */
class Notification extends BaseModel
{
	/** @var \Friendica\Repository\Notification */
	private $repo;

	public function __construct(Database $dba, LoggerInterface $logger, \Friendica\Repository\Notification $repo, array $data = [])
	{
		parent::__construct($dba, $logger, $data);

		$this->repo = $repo;

		$this->setNameCache();
		$this->setMsgCache();
	}

	/**
	 * Sets the pre-formatted name (caching)
	 */
	private function setNameCache()
	{
		try {
			$this->name_cache = strip_tags(BBCode::convert($this->source_name));
		} catch (InternalServerErrorException $e) {
		}
	}

	/**
	 * Sets the pre-formatted msg (caching)
	 */
	private function setMsgCache()
	{
		try {
			$this->msg_cache = self::formatMessage($this->name_cache, strip_tags(BBCode::convert($this->msg)));
		} catch (InternalServerErrorException $e) {
		}
	}

	public function __set($name, $value)
	{
		parent::__set($name, $value);

		if ($name == 'msg') {
			$this->setMsgCache();
		}

		if ($name == 'source_name') {
			$this->setNameCache();
		}
	}

	/**
	 * Formats a notification message with the notification author
	 *
	 * Replace the name with {0} but ensure to make that only once. The {0} is used
	 * later and prints the name in bold.
	 *
	 * @param string $name
	 * @param string $message
	 *
	 * @return string Formatted message
	 */
	public static function formatMessage($name, $message)
	{
		if ($name != '') {
			$pos = strpos($message, $name);
		} else {
			$pos = false;
		}

		if ($pos !== false) {
			$message = substr_replace($message, '{0}', $pos, strlen($name));
		}

		return $message;
	}

	/**
	 * Fetch the notification type for the given notification
	 *
	 * @param array $notification
	 * @return string
	 */
	public static function getType(array $notification): string
	{
		if (($notification['vid'] == Verb::getID(Activity::FOLLOW)) && ($notification['type'] == Post\UserNotification::NOTIF_NONE)) {
			$contact = Contact::getById($notification['actor-id'], ['pending']);
			$type = $contact['pending'] ? 'follow_request' : 'follow';
		} elseif (($notification['vid'] == Verb::getID(Activity::ANNOUNCE)) &&
			in_array($notification['type'], [Post\UserNotification::NOTIF_DIRECT_COMMENT, Post\UserNotification::NOTIF_DIRECT_THREAD_COMMENT])) {
			$type = 'reblog';
		} elseif (in_array($notification['vid'], [Verb::getID(Activity::LIKE), Verb::getID(Activity::DISLIKE)]) &&
			in_array($notification['type'], [Post\UserNotification::NOTIF_DIRECT_COMMENT, Post\UserNotification::NOTIF_DIRECT_THREAD_COMMENT])) {
			$type = 'favourite';
		} elseif ($notification['type'] == Post\UserNotification::NOTIF_SHARED) {
			$type = 'status';
		} elseif (in_array($notification['type'], [Post\UserNotification::NOTIF_EXPLICIT_TAGGED,
			Post\UserNotification::NOTIF_IMPLICIT_TAGGED, Post\UserNotification::NOTIF_DIRECT_COMMENT,
			Post\UserNotification::NOTIF_DIRECT_THREAD_COMMENT, Post\UserNotification::NOTIF_THREAD_COMMENT])) {
			$type = 'mention';
		} else {
			return '';
		}

		return $type;
	}

	/**
	 * Create a notification message for the given notification
	 *
	 * @param array $notification
	 * @return array with the elements "causer", "notification", "plain" and "rich"
	 */
	public static function getMessage(array $notification)
	{
		$message = [];

		if ($notification['type'] == Post\UserNotification::NOTIF_NONE) {
			return $message;
		}

		if (empty($notification['target-uri-id'])) {
			return $message;
		}

		$user = User::getById($notification['uid']);
		if (empty($user)) {
			Logger::info('User not found', ['application' => $notification['uid']]);
			return $message;
		}

		$contact = Contact::getById($notification['actor-id']);
		if (empty($contact)) {
			Logger::info('Contact not found', ['contact' => $notification['actor-id']]);
			return $message;
		}

		$like     = Verb::getID(Activity::LIKE);
		$dislike  = Verb::getID(Activity::DISLIKE);
		$announce = Verb::getID(Activity::ANNOUNCE);
		$post     = Verb::getID(Activity::POST);

		if (in_array($notification['type'], [Post\UserNotification::NOTIF_THREAD_COMMENT, Post\UserNotification::NOTIF_COMMENT_PARTICIPATION])) {
			$item = Post::selectFirst([], ['uri-id' => $notification['parent-uri-id'], 'uid' => [0, $notification['uid']]]);
			if (empty($item)) {
				Logger::info('Parent post not found', ['uri-id' => $notification['parent-uri-id']]);
				return $message;
			}
		} else {
			$item = Post::selectFirst([], ['uri-id' => $notification['target-uri-id'], 'uid' => [0, $notification['uid']]]);
			if (empty($item)) {
				Logger::info('Post not found', ['uri-id' => $notification['target-uri-id']]);
				return $message;
			}

			if ($notification['vid'] == $post) {
				$item = Post::selectFirst([], ['uri-id' => $item['thr-parent-id'], 'uid' => [0, $notification['uid']]]);
				if (empty($item)) {
					Logger::info('Thread parent post not found', ['uri-id' => $item['thr-parent-id']]);
					return $message;
				}
			}
		}

		$link = DI::baseUrl() . '/display/' . urlencode($item['guid']);

		$content = Plaintext::getPost($item, 70);
		if (!empty($content['text'])) {
			$title = '"' . trim(str_replace("\n", " ", $content['text'])) . '"';
		} else {
			$title = '';
		}

		$l10n = DI::l10n()->withLang($user['language']);

		switch ($notification['vid']) {
			case $like:
				switch ($notification['type']) {
					case Post\UserNotification::NOTIF_DIRECT_COMMENT:
						$msg = $l10n->t('%1$s liked your comment %2$s');
						break;
					case Post\UserNotification::NOTIF_DIRECT_THREAD_COMMENT:
						$msg = $l10n->t('%1$s liked your post %2$s');
						break;
					}
				break;
			case $dislike:
				switch ($notification['type']) {
					case Post\UserNotification::NOTIF_DIRECT_COMMENT:
						$msg = $l10n->t('%1$s disliked your comment %2$s');
						break;
					case Post\UserNotification::NOTIF_DIRECT_THREAD_COMMENT:
						$msg = $l10n->t('%1$s disliked your post %2$s');
						break;
				}
				break;
			case $announce:
				switch ($notification['type']) {
					case Post\UserNotification::NOTIF_DIRECT_COMMENT:
						$msg = $l10n->t('%1$s shared your comment %2$s');
						break;
					case Post\UserNotification::NOTIF_DIRECT_THREAD_COMMENT:
						$msg = $l10n->t('%1$s shared your post %2$s');
						break;
					}
				break;
			case $post:
				switch ($notification['type']) {
					case Post\UserNotification::NOTIF_EXPLICIT_TAGGED:
						$msg = $l10n->t('%1$s tagged you on %2$s');
						break;

					case Post\UserNotification::NOTIF_IMPLICIT_TAGGED:
						$msg = $l10n->t('%1$s replied to you on %2$s');
						break;

					case Post\UserNotification::NOTIF_THREAD_COMMENT:
						$msg = $l10n->t('%1$s commented in your thread %2$s');
						break;

					case Post\UserNotification::NOTIF_DIRECT_COMMENT:
						$msg = $l10n->t('%1$s commented on your comment %2$s');
						break;

					case Post\UserNotification::NOTIF_COMMENT_PARTICIPATION:
						$msg = $l10n->t('%1$s commented in the thread %2$s');
						break;

					case Post\UserNotification::NOTIF_ACTIVITY_PARTICIPATION:
						// Unhandled
						break;

					case Post\UserNotification::NOTIF_DIRECT_THREAD_COMMENT:
						$msg = $l10n->t('%1$s commented on your thread %2$s');
						break;

					case Post\UserNotification::NOTIF_SHARED:
						if ($title != '') {
							$msg = $l10n->t('%1$s shared the post %2$s');
						} else {
							$msg = $l10n->t('%1$s shared a post');
						}
						break;
				}
				break;
		}

		if (!empty($msg)) {
			// Name of the notification's causer
			$message['causer'] = $contact['name'];
			// Format for the "ping" mechanism
			$message['notification'] = sprintf($msg, '{0}', $title);
			// Plain text for the web push api
			$message['plain']        = sprintf($msg, $contact['name'], $title);
			// Rich text for other purposes 
			$message['rich']         = sprintf($msg,
				'[url=' . $contact['url'] . ']' . $contact['name'] . '[/url]',
				'[url=' . $link . ']' . $title . '[/url]');
		}

		return $message;
	}
}
