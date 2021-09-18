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
 */
class Notification extends BaseModel
{
	/**
	 * Fetch the notification type for the given notification
	 *
	 * @param array $notification
	 * @return string
	 */
	public static function getType(array $notification): string
	{
		if (($notification['vid'] == Verb::getID(Activity::FOLLOW)) && ($notification['type'] == Post\UserNotification::TYPE_NONE)) {
			$contact = Contact::getById($notification['actor-id'], ['pending']);
			$type = $contact['pending'] ? 'follow_request' : 'follow';
		} elseif (($notification['vid'] == Verb::getID(Activity::ANNOUNCE)) &&
			in_array($notification['type'], [Post\UserNotification::TYPE_DIRECT_COMMENT, Post\UserNotification::TYPE_DIRECT_THREAD_COMMENT])) {
			$type = 'reblog';
		} elseif (in_array($notification['vid'], [Verb::getID(Activity::LIKE), Verb::getID(Activity::DISLIKE)]) &&
			in_array($notification['type'], [Post\UserNotification::TYPE_DIRECT_COMMENT, Post\UserNotification::TYPE_DIRECT_THREAD_COMMENT])) {
			$type = 'favourite';
		} elseif ($notification['type'] == Post\UserNotification::TYPE_SHARED) {
			$type = 'status';
		} elseif (in_array($notification['type'], [
			Post\UserNotification::TYPE_EXPLICIT_TAGGED,
            Post\UserNotification::TYPE_IMPLICIT_TAGGED,
			Post\UserNotification::TYPE_DIRECT_COMMENT,
			Post\UserNotification::TYPE_DIRECT_THREAD_COMMENT,
			Post\UserNotification::TYPE_THREAD_COMMENT
		])) {
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

		$user = User::getById($notification['uid']);
		if (empty($user)) {
			Logger::info('User not found', ['application' => $notification['uid']]);
			return $message;
		}

		$l10n = DI::l10n()->withLang($user['language']);

		$causer = $contact = Contact::getById($notification['actor-id'], ['id', 'name', 'url', 'pending']);
		if (empty($contact)) {
			Logger::info('Contact not found', ['contact' => $notification['actor-id']]);
			return $message;
		}

		if ($notification['type'] == Post\UserNotification::TYPE_NONE) {
			if ($contact['pending']) {
				$msg = $l10n->t('%1$s wants to follow you');
			} else {
				$msg = $l10n->t('%1$s had started following you');
			}
			$title = $contact['name'];
			$link = DI::baseUrl() . '/contact/' . $contact['id'];
		} else {
			if (empty($notification['target-uri-id'])) {
				return $message;
			}

			$like     = Verb::getID(Activity::LIKE);
			$dislike  = Verb::getID(Activity::DISLIKE);
			$announce = Verb::getID(Activity::ANNOUNCE);
			$post     = Verb::getID(Activity::POST);

			if (in_array($notification['type'], [Post\UserNotification::TYPE_THREAD_COMMENT, Post\UserNotification::TYPE_COMMENT_PARTICIPATION, Post\UserNotification::TYPE_ACTIVITY_PARTICIPATION, Post\UserNotification::TYPE_EXPLICIT_TAGGED])) {
				$item = Post::selectFirst([], ['uri-id' => $notification['parent-uri-id'], 'uid' => [0, $notification['uid']]], ['order' => ['uid' => true]]);
				if (empty($item)) {
					Logger::info('Parent post not found', ['uri-id' => $notification['parent-uri-id']]);
					return $message;
				}
			} else {
				$item = Post::selectFirst([], ['uri-id' => $notification['target-uri-id'], 'uid' => [0, $notification['uid']]], ['order' => ['uid' => true]]);
				if (empty($item)) {
					Logger::info('Post not found', ['uri-id' => $notification['target-uri-id']]);
					return $message;
				}

				if ($notification['vid'] == $post) {
					$item = Post::selectFirst([], ['uri-id' => $item['thr-parent-id'], 'uid' => [0, $notification['uid']]], ['order' => ['uid' => true]]);
					if (empty($item)) {
						Logger::info('Thread parent post not found', ['uri-id' => $item['thr-parent-id']]);
						return $message;
					}
				}
			}

			if ($item['owner-id'] != $item['author-id']) {
				$cid = $item['owner-id'];
			}
			if (!empty($item['causer-id']) && ($item['causer-id'] != $item['author-id'])) {
				$cid = $item['causer-id'];
			}

			if (($notification['type'] == Post\UserNotification::TYPE_SHARED) && !empty($cid)) {
				$causer = Contact::getById($cid, ['id', 'name', 'url']);
				if (empty($contact)) {
					Logger::info('Causer not found', ['causer' => $cid]);
					return $message;
				}
			} elseif (in_array($notification['type'], [Post\UserNotification::TYPE_COMMENT_PARTICIPATION, Post\UserNotification::TYPE_ACTIVITY_PARTICIPATION])) {
				$contact = Contact::getById($item['author-id'], ['id', 'name', 'url']);
				if (empty($contact)) {
					Logger::info('Author not found', ['author' => $item['author-id']]);
					return $message;
				}
			}

			$link = DI::baseUrl() . '/display/' . urlencode($item['guid']);

			$content = Plaintext::getPost($item, 70);
			if (!empty($content['text'])) {
				$title = '"' . trim(str_replace("\n", " ", $content['text'])) . '"';
			} else {
				$title = '';
			}

			switch ($notification['vid']) {
				case $like:
					switch ($notification['type']) {
						case Post\UserNotification::TYPE_DIRECT_COMMENT:
							$msg = $l10n->t('%1$s liked your comment %2$s');
							break;
						case Post\UserNotification::TYPE_DIRECT_THREAD_COMMENT:
							$msg = $l10n->t('%1$s liked your post %2$s');
							break;
						}
					break;
				case $dislike:
					switch ($notification['type']) {
						case Post\UserNotification::TYPE_DIRECT_COMMENT:
							$msg = $l10n->t('%1$s disliked your comment %2$s');
							break;
						case Post\UserNotification::TYPE_DIRECT_THREAD_COMMENT:
							$msg = $l10n->t('%1$s disliked your post %2$s');
							break;
					}
					break;
				case $announce:
					switch ($notification['type']) {
						case Post\UserNotification::TYPE_DIRECT_COMMENT:
							$msg = $l10n->t('%1$s shared your comment %2$s');
							break;
						case Post\UserNotification::TYPE_DIRECT_THREAD_COMMENT:
							$msg = $l10n->t('%1$s shared your post %2$s');
							break;
						}
					break;
				case $post:
					switch ($notification['type']) {
						case Post\UserNotification::TYPE_EXPLICIT_TAGGED:
							$msg = $l10n->t('%1$s tagged you on %2$s');
							break;

						case Post\UserNotification::TYPE_IMPLICIT_TAGGED:
							$msg = $l10n->t('%1$s replied to you on %2$s');
							break;

						case Post\UserNotification::TYPE_THREAD_COMMENT:
							$msg = $l10n->t('%1$s commented in your thread %2$s');
							break;

						case Post\UserNotification::TYPE_DIRECT_COMMENT:
							$msg = $l10n->t('%1$s commented on your comment %2$s');
							break;

						case Post\UserNotification::TYPE_COMMENT_PARTICIPATION:
						case Post\UserNotification::TYPE_ACTIVITY_PARTICIPATION:
							if (($causer['id'] == $contact['id']) && ($title != '')) {
								$msg = $l10n->t('%1$s commented in their thread %2$s');
							} elseif ($causer['id'] == $contact['id']) {
								$msg = $l10n->t('%1$s commented in their thread');
							} elseif ($title != '') {
								$msg = $l10n->t('%1$s commented in the thread %2$s from %3$s');
							} else {
								$msg = $l10n->t('%1$s commented in the thread from %3$s');
							}
							break;

						case Post\UserNotification::TYPE_DIRECT_THREAD_COMMENT:
							$msg = $l10n->t('%1$s commented on your thread %2$s');
							break;

						case Post\UserNotification::TYPE_SHARED:
							if (($causer['id'] != $contact['id']) && ($title != '')) {
								$msg = $l10n->t('%1$s shared the post %2$s from %3$s');
							} elseif ($causer['id'] != $contact['id']) {
								$msg = $l10n->t('%1$s shared a post from %3$s');
							} elseif ($title != '') {
								$msg = $l10n->t('%1$s shared the post %2$s');
							} else {
								$msg = $l10n->t('%1$s shared a post');
							}
							break;
					}
					break;
			}
		}

		if (!empty($msg)) {
			// Name of the notification's causer
			$message['causer'] = $causer['name'];
			// Format for the "ping" mechanism
			$message['notification'] = sprintf($msg, '{0}', $title, $contact['name']);
			// Plain text for the web push api
			$message['plain']        = sprintf($msg, $causer['name'], $title, $contact['name']);
			// Rich text for other purposes
			$message['rich']         = sprintf($msg,
				'[url=' . $causer['url'] . ']' . $causer['name'] . '[/url]',
				'[url=' . $link . ']' . $title . '[/url]',
				'[url=' . $contact['url'] . ']' . $contact['name'] . '[/url]');
		}

		return $message;
	}
}
