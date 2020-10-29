<?php
/**
 * @copyright Copyright (C) 2020, Friendica
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

namespace Friendica\Model\Post;

use Friendica\Core\Logger;
use Friendica\Core\System;
use Friendica\Database\DBA;
use Friendica\Util\Images;

/**
 * Class Media
 *
 * This Model class handles media interactions.
 * This tables stores medias (images, videos, audio files) related to posts.
 */
class Media
{
	const UNKNOWN = 0;
	const IMAGE   = 1;
	const VIDEO   = 2;
	const AUDIO   = 3;
	const TORRENT = 16;

	/**
	 * Insert a post-media record
	 *
	 * @param array $media
	 * @return void
	 */
	public static function insert(array $media)
	{
		if (empty($media['url']) || empty($media['uri-id'])) {
			return;
		}

		if (DBA::exists('post-media', ['uri-id' => $media['uri-id'], 'url' => $media['url']])) {
			Logger::info('Media already exists', ['uri-id' => $media['uri-id'], 'url' => $media['url'], 'callstack' => System::callstack()]);
			return;
		}

		$fields = ['type', 'mimetype', 'height', 'width', 'size', 'preview', 'preview-height', 'preview-width', 'description'];
		foreach ($fields as $field) {
			if (empty($media[$field])) {
				unset($media[$field]);
			}
		}

		if ($media['type'] == self::IMAGE) {
			$imagedata = Images::getInfoFromURLCached($media['url']);
			if (!empty($imagedata)) {
				$media['mimetype'] = $imagedata['mime'];
				$media['size'] = $imagedata['size'];
				$media['width'] = $imagedata[0];
				$media['height'] = $imagedata[1];
			}
			if (!empty($media['preview'])) {
				$imagedata = Images::getInfoFromURLCached($media['preview']);
				if (!empty($imagedata)) {
					$media['preview-width'] = $imagedata[0];
					$media['preview-height'] = $imagedata[1];
				}
			}
		}

		$result = DBA::insert('post-media', $media, true);
		Logger::info('Stored media', ['result' => $result, 'media' => $media, 'callstack' => System::callstack()]);
	}

	/**
	 * Tests for path patterns that are usef for picture links in Friendica
	 *
	 * @param string $page    Link to the image page
	 * @param string $preview Preview picture
	 * @return boolean
	 */
	private static function isPictureLink(string $page, string $preview)
	{
		return preg_match('#/photos/.*/image/#ism', $page) && preg_match('#/photo/.*-1\.#ism', $preview);
	}

	/**
	 * Add media links and remove them from the body
	 *
	 * @param integer $uriid
	 * @param string $body
	 * @return string Body without media links
	 */
	public static function insertFromBody(int $uriid, string $body)
	{
		// Simplify image codes
		$body = preg_replace("/\[img\=([0-9]*)x([0-9]*)\](.*?)\[\/img\]/ism", '[img]$3[/img]', $body);

		$attachments = [];
		if (preg_match_all("#\[url=([^\]]+?)\]\s*\[img=([^\[\]]*)\]([^\[\]]*)\[\/img\]\s*\[/url\]#ism", $body, $pictures, PREG_SET_ORDER)) {
			foreach ($pictures as $picture) {
				if (!self::isPictureLink($picture[1], $picture[2])) {
					continue;
				}
				$body = str_replace($picture[0], '', $body);
				$image = str_replace('-1.', '-0.', $picture[2]);
				$attachments[] = ['uri-id' => $uriid, 'type' => self::IMAGE, 'url' => $image,
					'preview' => $picture[2], 'description' => $picture[3]];
			}
		}

		if (preg_match_all("/\[img=([^\[\]]*)\]([^\[\]]*)\[\/img\]/Usi", $body, $pictures, PREG_SET_ORDER)) {
			foreach ($pictures as $picture) {
				$body = str_replace($picture[0], '', $body);
				$attachments[] = ['uri-id' => $uriid, 'type' => self::IMAGE, 'url' => $picture[1], 'description' => $picture[2]];
			}
		}

		if (preg_match_all("#\[url=([^\]]+?)\]\s*\[img\]([^\[]+?)\[/img\]\s*\[/url\]#ism", $body, $pictures, PREG_SET_ORDER)) {
			foreach ($pictures as $picture) {
				if (!self::isPictureLink($picture[1], $picture[2])) {
					continue;
				}
				$body = str_replace($picture[0], '', $body);
				$image = str_replace('-1.', '-0.', $picture[2]);
				$attachments[] = ['uri-id' => $uriid, 'type' => self::IMAGE, 'url' => $image,
					'preview' => $picture[2], 'description' => null];
			}
		}

		if (preg_match_all("/\[img\]([^\[\]]*)\[\/img\]/ism", $body, $pictures, PREG_SET_ORDER)) {
			foreach ($pictures as $picture) {
				$body = str_replace($picture[0], '', $body);
				$attachments[] = ['uri-id' => $uriid, 'type' => self::IMAGE, 'url' => $picture[1]];
			}
		}

		/// @todo audio + video
		if (preg_match_all("/\[audio\]([^\[\]]*)\[\/audio\]/ism", $body, $audios, PREG_SET_ORDER)) {
			foreach ($audios as $audio) {
				$body = str_replace($audio[0], '', $body);
				$attachments[] = ['uri-id' => $uriid, 'type' => self::AUDIO, 'url' => $audio[1]];
			}
		}

		if (preg_match_all("/\[video\]([^\[\]]*)\[\/video\]/ism", $body, $videos, PREG_SET_ORDER)) {
			foreach ($videos as $video) {
				$body = str_replace($video[0], '', $body);
				$attachments[] = ['uri-id' => $uriid, 'type' => self::VIDEO, 'url' => $video[1]];
			}
		}

		foreach ($attachments as $attachment) {
			self::insert($attachment);
		}

		return trim($body);
	}
}
