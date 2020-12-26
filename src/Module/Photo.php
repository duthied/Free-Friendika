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

namespace Friendica\Module;

use Friendica\BaseModule;
use Friendica\Core\Logger;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Model\Photo as MPhoto;
use Friendica\Util\Proxy;
use Friendica\Object\Image;

/**
 * Photo Module
 */
class Photo extends BaseModule
{
	/**
	 * Module initializer
	 *
	 * Fetch a photo or an avatar, in optional size, check for permissions and
	 * return the image
	 */
	public static function rawContent(array $parameters = [])
	{
		$totalstamp = microtime(true);
		$a = DI::app();
		// @TODO: Replace with parameter from router
		if ($a->argc <= 1 || $a->argc > 4) {
			throw new \Friendica\Network\HTTPException\BadRequestException();
		}

		if (isset($_SERVER["HTTP_IF_MODIFIED_SINCE"])) {
			header("HTTP/1.1 304 Not Modified");
			header("Last-Modified: " . gmdate("D, d M Y H:i:s", time()) . " GMT");
			if (!empty($_SERVER["HTTP_IF_NONE_MATCH"])) {
				header("Etag: " . $_SERVER["HTTP_IF_NONE_MATCH"]);
			}
			header("Expires: " . gmdate("D, d M Y H:i:s", time() + (31536000)) . " GMT");
			header("Cache-Control: max-age=31536000");
			if (function_exists("header_remove")) {
				header_remove("Last-Modified");
				header_remove("Expires");
				header_remove("Cache-Control");
			}
			exit;
		}

		$customsize = 0;
		$photo = false;
		$scale = null;
		// @TODO: Replace with parameter from router
		$stamp = microtime(true);
		switch($a->argc) {
			case 4:
				$customsize = intval($a->argv[2]);
				$uid = MPhoto::stripExtension($a->argv[3]);
				$photo = self::getAvatar($uid, $a->argv[1]);
				break;
			case 3:
				$uid = MPhoto::stripExtension($a->argv[2]);
				$photo = self::getAvatar($uid, $a->argv[1]);
				break;
			case 2:
				$photoid = MPhoto::stripExtension($a->argv[1]);
				$scale = 0;
				if (substr($photoid, -2, 1) == "-") {
					$scale = intval(substr($photoid, -1, 1));
					$photoid = substr($photoid, 0, -2);
				}
				$photo = MPhoto::getPhoto($photoid, $scale);
				if ($photo === false) {
					throw new \Friendica\Network\HTTPException\NotFoundException(DI::l10n()->t('The Photo with id %s is not available.', $photoid));
				}
				break;
		}
		$fetch = microtime(true) - $stamp;

		if ($photo === false) {
			throw new \Friendica\Network\HTTPException\NotFoundException();
		}

		$cacheable = ($photo["allow_cid"] . $photo["allow_gid"] . $photo["deny_cid"] . $photo["deny_gid"] === "") && (isset($photo["cacheable"]) ? $photo["cacheable"] : true);

		$stamp = microtime(true);
		$imgdata = MPhoto::getImageDataForPhoto($photo);
		$data = microtime(true) - $stamp;

		if (empty($imgdata)) {
			Logger::warning("Invalid photo with id {$photo["id"]}.");
			throw new \Friendica\Network\HTTPException\InternalServerErrorException(DI::l10n()->t('Invalid photo with id %s.', $photo["id"]));
		}

		// if customsize is set and image is not a gif, resize it
		if ($photo['type'] !== "image/gif" && $customsize > 0 && $customsize < 501) {
			$img = new Image($imgdata, $photo['type']);
			$img->scaleToSquare($customsize);
			$imgdata = $img->asString();
		}

		if (function_exists("header_remove")) {
			header_remove("Pragma");
			header_remove("pragma");
		}

		header("Content-type: " . $photo['type']);

		$stamp = microtime(true);
		if (!$cacheable) {
			// it is a private photo that they have no permission to view.
			// tell the browser not to cache it, in case they authenticate
			// and subsequently have permission to see it
			header("Cache-Control: no-store, no-cache, must-revalidate");
		} else {
			$md5 = $photo['hash'] ?: md5($imgdata);
			header("Last-Modified: " . gmdate("D, d M Y H:i:s", time()) . " GMT");
			header("Etag: \"{$md5}\"");
			header("Expires: " . gmdate("D, d M Y H:i:s", time() + (31536000)) . " GMT");
			header("Cache-Control: max-age=31536000");
		}
		$checksum = microtime(true) - $stamp;

		$stamp = microtime(true);
		echo $imgdata;
		$output = microtime(true) - $stamp;

		$total = microtime(true) - $totalstamp;
		$rest = $total - ($fetch + $data + $checksum + $output);

		if (!is_null($scale) && ($scale < 4)) {
			Logger::info('Performance:', ['scale' => $scale, 'resource' => $photo['resource-id'],
				'total' => number_format($total, 3), 'fetch' => number_format($fetch, 3),
				'data' => number_format($data, 3), 'checksum' => number_format($checksum, 3),
				'output' => number_format($output, 3), 'rest' => number_format($rest, 3)]);
		}

		exit();
	}

	private static function getAvatar($uid, $type="avatar")
	{
		switch($type) {
			case "profile":
			case "custom":
				$scale = 4;
				break;
			case "micro":
				$scale = 6;
				break;
			case "avatar":
			default:
				$scale = 5;
		}

		$photo = MPhoto::selectFirst([], ["scale" => $scale, "uid" => $uid, "profile" => 1]);
		if (empty($photo)) {
			$contact = DBA::selectFirst('contact', [], ['uid' => $uid, 'self' => true]) ?: [];

			switch($type) {
				case "profile":
				case "custom":
					$default = Contact::getDefaultAvatar($contact, Proxy::SIZE_SMALL);
					break;
				case "micro":
					$default = Contact::getDefaultAvatar($contact, Proxy::SIZE_MICRO);
					break;
				case "avatar":
				default:
					$default = Contact::getDefaultAvatar($contact, Proxy::SIZE_THUMB);
			}
	
			$photo = MPhoto::createPhotoForSystemResource($default);
		}
		return $photo;
	}
}
