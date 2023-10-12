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

namespace Friendica\Content\Conversation\Entity;

/**
 * @property-read string $code           Channel code
 * @property-read string $label          Channel label
 * @property-read string $description    Channel description
 * @property-read string $accessKey      Access key
 * @property-read string $path           Path
 * @property-read int    $uid            User of the channel
 * @property-read string $includeTags    The tags to include in the channel
 * @property-read string $excludeTags    The tags to exclude in the channel
 * @property-read string $fullTextSearch full text search pattern
 * @property-read int    $mediaType      Media types that are included in the channel
 * @property-read int    $circle         Circle or timeline this channel is based on
 */
class Timeline extends \Friendica\BaseEntity
{
	/** @var string */
	protected $code;
	/** @var string */
	protected $label;
	/** @var string */
	protected $description;
	/** @var string */
	protected $accessKey;
	/** @var string */
	protected $path;
	/** @var int */
	protected $uid;
	/** @var int */
	protected $circle;
	/** @var string */
	protected $includeTags;
	/** @var string */
	protected $excludeTags;
	/** @var string */
	protected $fullTextSearch;
	/** @var int */
	protected $mediaType;

	public function __construct(string $code = null, string $label = null, string $description = null, string $accessKey = null, string $path = null, int $uid = null, string $includeTags = null, string $excludeTags = null, string $fullTextSearch = null, int $mediaType = null, int $circle = null)
	{
		$this->code           = $code;
		$this->label          = $label;
		$this->description    = $description;
		$this->accessKey      = $accessKey;
		$this->path           = $path;
		$this->uid            = $uid;
		$this->includeTags    = $includeTags;
		$this->excludeTags    = $excludeTags;
		$this->fullTextSearch = $fullTextSearch;
		$this->mediaType      = $mediaType;
		$this->circle         = $circle;
	}
}
