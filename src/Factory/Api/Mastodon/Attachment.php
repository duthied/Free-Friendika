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

namespace Friendica\Factory\Api\Mastodon;

use Friendica\App\BaseURL;
use Friendica\BaseFactory;
use Friendica\Network\HTTPException;
use Friendica\Model\Post;
use Friendica\Repository\ProfileField;
use Friendica\Util\Proxy;
use Psr\Log\LoggerInterface;

class Attachment extends BaseFactory
{
	/** @var BaseURL */
	protected $baseUrl;
	/** @var ProfileField */
	protected $profileField;
	/** @var Field */
	protected $mstdnField;

	public function __construct(LoggerInterface $logger, BaseURL $baseURL, ProfileField $profileField, Field $mstdnField)
	{
		parent::__construct($logger);

		$this->baseUrl = $baseURL;
		$this->profileField = $profileField;
		$this->mstdnField = $mstdnField;
	}

	/**
	 * @param int $uriId Uri-ID of the attachments
	 * @return array
	 * @throws HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public function createFromUriId(int $uriId)
	{
		$attachments = [];
		foreach (Post\Media::getByURIId($uriId) as $attachment) {

			$filetype = !empty($attachment['mimetype']) ? strtolower(substr($attachment['mimetype'], 0, strpos($attachment['mimetype'], '/'))) : '';

			if (($filetype == 'audio') || ($attachment['type'] == Post\Media::AUDIO)) {
				$type = 'audio';
			} elseif (($filetype == 'video') || ($attachment['type'] == Post\Media::VIDEO)) {
				$type = 'video';
			} elseif ($attachment['mimetype'] == 'image/gif') {
				$type = 'gifv';
			} elseif (($filetype == 'image') || ($attachment['type'] == Post\Media::IMAGE)) {
				$type = 'image';
			} else {
				$type = 'unknown';
			}

			$remote = $attachment['url'];
			if ($type == 'image') {
				if (Proxy::isLocalImage($attachment['url'])) {
					$url = $attachment['url'];
					$preview = $attachment['preview'] ?? $url;
					$remote = '';
				} else {
					$url = Proxy::proxifyUrl($attachment['url']);
					$preview = Proxy::proxifyUrl($attachment['url'], false, Proxy::SIZE_SMALL);
				}
			} else {
				$url = '';
				$preview = '';
			}

			$object = new \Friendica\Object\Api\Mastodon\Attachment($attachment, $type, $url, $preview, $remote);
			$attachments[] = $object->toArray();
		}

		return $attachments;
	}
}
