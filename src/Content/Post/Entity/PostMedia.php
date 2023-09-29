<?php
/**
 * @copyright Copyright (C) 2010-2023, the Friendica project
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

namespace Friendica\Content\Post\Entity;

use Friendica\BaseEntity;
use Friendica\Network\Entity\MimeType;
use Friendica\Util\Proxy;
use Psr\Http\Message\UriInterface;


/**
 * @property-read int $id
 * @property-read int $uriId
 * @property-read ?int $activityUriId
 * @property-read UriInterface $url
 * @property-read int $type
 * @property-read MimeType $mimetype
 * @property-read ?int $width
 * @property-read ?int $height
 * @property-read ?int $size
 * @property-read ?UriInterface $preview
 * @property-read ?int $previewWidth
 * @property-read ?int $previewHeight
 * @property-read ?string $description
 * @property-read ?string $name
 * @property-read ?UriInterface $authorUrl
 * @property-read ?string $authorName
 * @property-read ?UriInterface $authorImage
 * @property-read ?UriInterface $publisherUrl
 * @property-read ?string $publisherName
 * @property-read ?UriInterface $publisherImage
 * @property-read ?string $blurhash
 */
class PostMedia extends BaseEntity
{
	const TYPE_UNKNOWN     = 0;
	const TYPE_IMAGE       = 1;
	const TYPE_VIDEO       = 2;
	const TYPE_AUDIO       = 3;
	const TYPE_TEXT        = 4;
	const TYPE_APPLICATION = 5;
	const TYPE_TORRENT     = 16;
	const TYPE_HTML        = 17;
	const TYPE_XML         = 18;
	const TYPE_PLAIN       = 19;
	const TYPE_ACTIVITY    = 20;
	const TYPE_ACCOUNT     = 21;
	const TYPE_DOCUMENT    = 128;

	/** @var int */
	protected $id;
	/** @var int */
	protected $uriId;
	/** @var UriInterface */
	protected $url;
	/** @var int One of TYPE_* */
	protected $type;
	/** @var MimeType */
	protected $mimetype;
	/** @var ?int */
	protected $activityUriId;
	/** @var ?int In pixels */
	protected $width;
	/** @var ?int In pixels */
	protected $height;
	/** @var ?int In bytes */
	protected $size;
	/** @var ?UriInterface Preview URL */
	protected $preview;
	/** @var ?int In pixels */
	protected $previewWidth;
	/** @var ?int In pixels */
	protected $previewHeight;
	/** @var ?string Alternative text like for images */
	protected $description;
	/** @var ?string */
	protected $name;
	/** @var ?UriInterface */
	protected $authorUrl;
	/** @var ?string */
	protected $authorName;
	/** @var ?UriInterface Image URL */
	protected $authorImage;
	/** @var ?UriInterface */
	protected $publisherUrl;
	/** @var ?string */
	protected $publisherName;
	/** @var ?UriInterface Image URL */
	protected $publisherImage;
	/** @var ?string Blurhash string representation for images
	 * @see https://github.com/woltapp/blurhash
	 * @see https://blurha.sh/
	 */
	protected $blurhash;

	public function __construct(
		int $uriId,
		UriInterface $url,
		int $type,
		MimeType $mimetype,
		?int $activityUriId,
		?int $width = null,
		?int $height = null,
		?int $size = null,
		?UriInterface $preview = null,
		?int $previewWidth = null,
		?int $previewHeight = null,
		?string $description = null,
		?string $name = null,
		?UriInterface $authorUrl = null,
		?string $authorName = null,
		?UriInterface $authorImage = null,
		?UriInterface $publisherUrl = null,
		?string $publisherName = null,
		?UriInterface $publisherImage = null,
		?string $blurhash = null,
		int $id = null
	)
	{
		$this->uriId          = $uriId;
		$this->url            = $url;
		$this->type           = $type;
		$this->mimetype       = $mimetype;
		$this->activityUriId  = $activityUriId;
		$this->width          = $width;
		$this->height         = $height;
		$this->size           = $size;
		$this->preview        = $preview;
		$this->previewWidth   = $previewWidth;
		$this->previewHeight  = $previewHeight;
		$this->description    = $description;
		$this->name           = $name;
		$this->authorUrl      = $authorUrl;
		$this->authorName     = $authorName;
		$this->authorImage    = $authorImage;
		$this->publisherUrl   = $publisherUrl;
		$this->publisherName  = $publisherName;
		$this->publisherImage = $publisherImage;
		$this->blurhash       = $blurhash;
		$this->id             = $id;
	}


	/**
	 * Get media link for given media id
	 *
	 * @param string  $size One of the Proxy::SIZE_* constants
	 * @return string media link
	 */
	public function getPhotoPath(string $size = ''): string
	{
		$url = '/photo/media/';
		switch ($size) {
			case Proxy::SIZE_MICRO:
				$url .= Proxy::PIXEL_MICRO . '/';
				break;
			case Proxy::SIZE_THUMB:
				$url .= Proxy::PIXEL_THUMB . '/';
				break;
			case Proxy::SIZE_SMALL:
				$url .= Proxy::PIXEL_SMALL . '/';
				break;
			case Proxy::SIZE_MEDIUM:
				$url .= Proxy::PIXEL_MEDIUM . '/';
				break;
			case Proxy::SIZE_LARGE:
				$url .= Proxy::PIXEL_LARGE . '/';
				break;
		}
		return $url . $this->id;
	}

	/**
	 * Get preview path for given media id relative to the base URL
	 *
	 * @param string  $size One of the Proxy::SIZE_* constants
	 * @return string preview link
	 */
	public function getPreviewPath(string $size = ''): string
	{
		$url = '/photo/preview/';
		switch ($size) {
			case Proxy::SIZE_MICRO:
				$url .= Proxy::PIXEL_MICRO . '/';
				break;
			case Proxy::SIZE_THUMB:
				$url .= Proxy::PIXEL_THUMB . '/';
				break;
			case Proxy::SIZE_SMALL:
				$url .= Proxy::PIXEL_SMALL . '/';
				break;
			case Proxy::SIZE_MEDIUM:
				$url .= Proxy::PIXEL_MEDIUM . '/';
				break;
			case Proxy::SIZE_LARGE:
				$url .= Proxy::PIXEL_LARGE . '/';
				break;
		}
		return $url . $this->id;
	}
}
