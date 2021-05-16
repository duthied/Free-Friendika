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

namespace Friendica\Module\Api\Mastodon;

use Friendica\Content\Text\BBCode;
use Friendica\Content\Text\Markdown;
use Friendica\Core\System;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Model\Group;
use Friendica\Model\Item;
use Friendica\Model\Post;
use Friendica\Model\User;
use Friendica\Module\BaseApi;
use Friendica\Protocol\Activity;
use Friendica\Util\Images;

/**
 * @see https://docs.joinmastodon.org/methods/statuses/
 */
class Statuses extends BaseApi
{
	public static function post(array $parameters = [])
	{
		self::login(self::SCOPE_WRITE);
		$uid = self::getCurrentUserID();

		$data = self::getJsonPostData();

		$status         = $data['status'] ?? '';
		$media_ids      = $data['media_ids'] ?? [];
		$in_reply_to_id = $data['in_reply_to_id'] ?? 0;
		$sensitive      = $data['sensitive'] ?? false; // @todo Possibly trigger "nsfw" flag?
		$spoiler_text   = $data['spoiler_text'] ?? '';
		$visibility     = $data['visibility'] ?? '';
		$scheduled_at   = $data['scheduled_at'] ?? ''; // Currently unsupported, but maybe in the future
		$language       = $data['language'] ?? '';

		$owner = User::getOwnerDataById($uid);

		// The imput is defined as text. So we can use Markdown for some enhancements
		$body = Markdown::toBBCode($status);

		$body = BBCode::expandTags($body);

		$item = [];
		$item['uid']        = $uid;
		$item['verb']       = Activity::POST;
		$item['contact-id'] = $owner['id'];
		$item['author-id']  = $item['owner-id'] = Contact::getPublicIdByUserId($uid);
		$item['title']      = $spoiler_text;
		$item['body']       = $body;

		if (!empty(self::getCurrentApplication()['name'])) {
			$item['app'] = self::getCurrentApplication()['name'];
		}

		if (empty($item['app'])) {
			$item['app'] = 'API';
		}

		switch ($visibility) {
			case 'public':
				$item['allow_cid'] = '';
				$item['allow_gid'] = '';
				$item['deny_cid']  = '';
				$item['deny_gid']  = '';
				$item['private']   = Item::PUBLIC;
				break;
			case 'unlisted':
				$item['allow_cid'] = '';
				$item['allow_gid'] = '';
				$item['deny_cid']  = '';
				$item['deny_gid']  = '';
				$item['private']   = Item::UNLISTED;
				break;
			case 'private':
				if (!empty($owner['allow_cid'] . $owner['allow_gid'] . $owner['deny_cid'] . $owner['deny_gid'])) {
					$item['allow_cid'] = $owner['allow_cid'];
					$item['allow_gid'] = $owner['allow_gid'];
					$item['deny_cid']  = $owner['deny_cid'];
					$item['deny_gid']  = $owner['deny_gid'];
				} else {
					$item['allow_cid'] = '';
					$item['allow_gid'] = [Group::FOLLOWERS];
					$item['deny_cid']  = '';
					$item['deny_gid']  = '';
				}
				$item['private'] = Item::PRIVATE;
				break;
			case 'direct':
				// Direct messages are currently unsupported
				DI::mstdnError()->InternalError('Direct messages are currently unsupported');
				break;		
			default:
				$item['allow_cid'] = $owner['allow_cid'];
				$item['allow_gid'] = $owner['allow_gid'];
				$item['deny_cid']  = $owner['deny_cid'];
				$item['deny_gid']  = $owner['deny_gid'];

				if (!empty($item['allow_cid'] . $item['allow_gid'] . $item['deny_cid'] . $item['deny_gid'])) {
					$item['private'] = Item::PRIVATE;
				} elseif (DI::pConfig()->get($uid, 'system', 'unlisted')) {
					$item['private'] = Item::UNLISTED;
				} else {
					$item['private'] = Item::PUBLIC;
				}
				break;
		}

		if (!empty($language)) {
			$item['language'] = json_encode([$language => 1]);
		}

		if ($in_reply_to_id) {
			$parent = Post::selectFirst(['uri'], ['uri-id' => $in_reply_to_id, 'uid' => [0, $uid]]);
			$item['thr-parent']  = $parent['uri'];
			$item['gravity']     = GRAVITY_COMMENT;
			$item['object-type'] = Activity\ObjectType::COMMENT;
		} else {
			$item['gravity']     = GRAVITY_PARENT;
			$item['object-type'] = Activity\ObjectType::NOTE;
		}

		if (!empty($media_ids)) {
			$item['object-type'] = Activity\ObjectType::IMAGE;
			$item['post-type']   = Item::PT_IMAGE;
			$item['attachments'] = [];

			foreach ($media_ids as $id) {
				$media = DBA::toArray(DBA::p("SELECT `resource-id`, `scale`, `type`, `desc`, `filename`, `datasize`, `width`, `height` FROM `photo`
						WHERE `resource-id` IN (SELECT `resource-id` FROM `photo` WHERE `id` = ?) AND `photo`.`uid` = ?
						ORDER BY `photo`.`width` DESC LIMIT 2", $id, $uid));
					
				if (empty($media)) {
					continue;
				}

				$ressources[] = $media[0]['resource-id'];
				$phototypes = Images::supportedTypes();
				$ext = $phototypes[$media[0]['type']];
			
				$attachment = ['type' => Post\Media::IMAGE, 'mimetype' => $media[0]['type'],
					'url' => DI::baseUrl() . '/photo/' . $media[0]['resource-id'] . '-' . $media[0]['scale'] . '.' . $ext,
					'size' => $media[0]['datasize'],
					'name' => $media[0]['filename'] ?: $media[0]['resource-id'],
					'description' => $media[0]['desc'] ?? '',
					'width' => $media[0]['width'],
					'height' => $media[0]['height']];
			
				if (count($media) > 1) {
					$attachment['preview'] = DI::baseUrl() . '/photo/' . $media[1]['resource-id'] . '-' . $media[1]['scale'] . '.' . $ext;
					$attachment['preview-width'] = $media[1]['width'];
					$attachment['preview-height'] = $media[1]['height'];
				}
				$item['attachments'][] = $attachment;
			}
		}

		$id = Item::insert($item, true);
		if (!empty($id)) {
			$item = Post::selectFirst(['uri-id'], ['id' => $id]);
			if (!empty($item['uri-id'])) {
				System::jsonExit(DI::mstdnStatus()->createFromUriId($item['uri-id'], $uid));		
			}
		}

		DI::mstdnError()->InternalError();
	}

	public static function delete(array $parameters = [])
	{
		self::login(self::SCOPE_READ);
		$uid = self::getCurrentUserID();

		if (empty($parameters['id'])) {
			DI::mstdnError()->UnprocessableEntity();
		}

		$item = Post::selectFirstForUser($uid, ['id'], ['uri-id' => $parameters['id'], 'uid' => $uid]);
		if (empty($item['id'])) {
			DI::mstdnError()->RecordNotFound();
		}

		if (!Item::markForDeletionById($item['id'])) {
			DI::mstdnError()->RecordNotFound();
		}

		System::jsonExit([]);
	}

	/**
	 * @param array $parameters
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function rawContent(array $parameters = [])
	{
		if (empty($parameters['id'])) {
			DI::mstdnError()->UnprocessableEntity();
		}

		System::jsonExit(DI::mstdnStatus()->createFromUriId($parameters['id'], self::getCurrentUserID()));
	}
}
