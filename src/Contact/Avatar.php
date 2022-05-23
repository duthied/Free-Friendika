<?php
/**
 * @copyright Copyright (C) 2010-2022, the Friendica project
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

namespace Friendica\Contact;

use Friendica\Core\Logger;
use Friendica\DI;
use Friendica\Model\Item;
use Friendica\Network\HTTPClient\Client\HttpClientAccept;
use Friendica\Network\HTTPClient\Client\HttpClientOptions;
use Friendica\Object\Image;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\HTTPSignature;
use Friendica\Util\Images;
use Friendica\Util\Network;
use Friendica\Util\Proxy;
use Friendica\Util\Strings;

/**
 * functions for handling contact avatar caching
 */
class Avatar
{
	const BASE_PATH = '/avatar/';

	/**
	 * Returns a field array with locally cached avatar pictures
	 *
	 * @param array $contact
	 * @param string $avatar
	 * @return array
	 */
	public static function fetchAvatarContact(array $contact, string $avatar): array
	{
		$fields = ['avatar' => $avatar, 'avatar-date' => DateTimeFormat::utcNow(), 'photo' => '', 'thumb' => '', 'micro' => ''];

		if (!DI::config()->get('system', 'avatar_cache')) {
			self::deleteCache($contact);
			return $fields;
		}

		if (Network::isLocalLink($avatar)) {
			return $fields;
		}

		if ($avatar != $contact['avatar']) {
			self::deleteCache($contact);
			Logger::debug('Avatar file name changed', ['new' => $avatar, 'old' => $contact['avatar']]);
		} elseif (self::isCacheFile($contact['photo']) && self::isCacheFile($contact['thumb']) && self::isCacheFile($contact['micro'])) {
			$fields['photo'] = $contact['photo'];
			$fields['thumb'] = $contact['thumb'];
			$fields['micro'] = $contact['micro'];
			Logger::debug('Using existing cache files', ['uri-id' => $contact['uri-id'], 'fields' => $fields]);
			return $fields;
		}

		$fetchResult = HTTPSignature::fetchRaw($avatar, 0, [HttpClientOptions::ACCEPT_CONTENT => [HttpClientAccept::IMAGE]]);

		$img_str = $fetchResult->getBody();
		if (empty($img_str)) {
			Logger::debug('Avatar is invalid', ['avatar' => $avatar]);
			return $fields;
		}

		$image = new Image($img_str, Images::getMimeTypeByData($img_str));
		if (!$image->isValid()) {
			Logger::debug('Avatar picture is invalid', ['avatar' => $avatar]);
			return $fields;
		}

		$filename = self::getFilename($contact['url']);
		
		$fields['photo'] = self::storeAvatarCache($image, $filename, Proxy::PIXEL_SMALL);
		$fields['thumb'] = self::storeAvatarCache($image, $filename, Proxy::PIXEL_THUMB);
		$fields['micro'] = self::storeAvatarCache($image, $filename, Proxy::PIXEL_MICRO);

		Logger::debug('Storing new avatar cache', ['uri-id' => $contact['uri-id'], 'fields' => $fields]);

		return $fields;
	}

	public static function storeAvatarByImage(array $contact, Image $image): array
	{
		$fields = ['photo' => '', 'thumb' => '', 'micro' => ''];

		if (!DI::config()->get('system', 'avatar_cache')) {
			self::deleteCache($contact);
			return $fields;
		}

		if (Network::isLocalLink($contact['avatar'])) {
			return $fields;
		}

		$filename = self::getFilename($contact['url']);
		
		$fields['photo'] = self::storeAvatarCache($image, $filename, Proxy::PIXEL_SMALL);
		$fields['thumb'] = self::storeAvatarCache($image, $filename, Proxy::PIXEL_THUMB);
		$fields['micro'] = self::storeAvatarCache($image, $filename, Proxy::PIXEL_MICRO);

		return $fields;
	}

	private static function getFilename(string $url)
	{
		$guid = Item::guidFromUri($url, parse_url($url, PHP_URL_HOST));

		return substr($guid, 0, 2) . '/' . substr($guid, 3, 2) . '/' . substr($guid, 5, 3) . '/' .
			substr($guid, 9, 2) .'/' . substr($guid, 11, 2) . '/' . substr($guid, 13, 4). '/' . substr($guid, 18) . '-';
	}

	private static function storeAvatarCache(Image $image, string $filename, int $size): string
	{
		$image->scaleDown($size);
		if (is_null($image) || !$image->isValid()) {
			return '';
		}

		$path = self::BASE_PATH . $filename . $size . '.' . $image->getExt();

		$filepath = DI::basePath() . $path;

		$dirpath = DI::basePath() . self::BASE_PATH;

		DI::profiler()->startRecording('file');

		// Fetch the permission and group ownership of the "avatar" path and apply to all files
		$dir_perm  = fileperms($dirpath) & 0777;
		$file_perm = fileperms($dirpath) & 0666;
		$group     = filegroup($dirpath);

		// Check directory permissions of all parts of the path
		foreach (explode('/', dirname($filename)) as $part) {
			$dirpath .= $part . '/';

			if (!file_exists($dirpath)) {
				if (!mkdir($dirpath, $dir_perm)) {
					Logger::warning('Directory could not be created', ['directory' => $dirpath]);
				}
			} elseif ((($old_perm = fileperms($dirpath) & 0777) != $dir_perm) && !chmod($dirpath, $dir_perm)) {
				Logger::notice('Directory permissions could not be changed', ['directory' => $dirpath, 'old' => $old_perm, 'new' => $dir_perm]);
			}

			if ((($old_group = filegroup($dirpath)) != $group) && !chgrp($dirpath, $group)) {
				Logger::notice('Directory group could not be changed', ['directory' => $dirpath, 'old' => $old_group, 'new' => $group]);
			}
		}

		if (!file_put_contents($filepath, $image->asString())) {
			Logger::warning('File could not be created', ['file' => $filepath]);
		}

		$old_perm  = fileperms($filepath) & 0666;
		$old_group = filegroup($filepath);

		if (($old_perm != $file_perm) && !chmod($filepath, $file_perm)) {
			Logger::notice('File permissions could not be changed', ['file' => $filepath, 'old' => $old_perm, 'new' => $file_perm]);
		}

		if (($old_group != $group) && !chgrp($filepath, $group)) {
			Logger::notice('File group could not be changed', ['file' => $filepath, 'old' => $old_group, 'new' => $group]);
		}

		DI::profiler()->stopRecording();

		if (!file_exists($filepath)) {
			Logger::warning('Avatar cache file could not be stored', ['file' => $filepath]);
			return '';
		}

		return DI::baseUrl() . $path;
	}

	/**
	 * Check if the avatar cache file is locally stored
	 *
	 * @param string $avatar
	 * @return boolean
	 */
	private static function isCacheFile(string $avatar): bool
	{
		return !empty(self::getCacheFile($avatar));
	}

	/**
	 * Fetch the name of locally cached avatar pictures
	 *
	 * @param string $avatar
	 * @return string
	 */
	private static function getCacheFile(string $avatar): string
	{
		if (empty($avatar) || !Network::isLocalLink($avatar)) {
			return '';
		}

		$path = Strings::normaliseLink(DI::baseUrl() . self::BASE_PATH);

		if (Network::getUrlMatch($path, $avatar) != $path) {
			return '';
		}

		$filename = str_replace($path, DI::basePath(). self::BASE_PATH, Strings::normaliseLink($avatar));

		DI::profiler()->startRecording('file');
		$exists = file_exists($filename);
		DI::profiler()->stopRecording();

		if (!$exists) {
			return '';
		}
		return $filename;
	}

	/**
	 * Delete locally cached avatar pictures of a contact
	 *
	 * @param string $avatar
	 * @return void
	 */
	public static function deleteCache(array $contact)
	{
		self::deleteCacheFile($contact['photo']);
		self::deleteCacheFile($contact['thumb']);
		self::deleteCacheFile($contact['micro']);
	}

	/**
	 * Delete a locally cached avatar picture
	 *
	 * @param string $avatar
	 * @return void
	 */
	private static function deleteCacheFile(string $avatar)
	{
		$localFile = self::getCacheFile($avatar);
		if (!empty($localFile)) {
			unlink($localFile);
			Logger::debug('Unlink avatar', ['avatar' => $avatar]);
		}
	}
}
