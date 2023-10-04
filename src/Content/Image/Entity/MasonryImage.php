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

namespace Friendica\Content\Image\Entity;

use Friendica\BaseEntity;
use Psr\Http\Message\UriInterface;

/**
 * @property-read int $uriId
 * @property-read UriInterface $url
 * @property-read ?UriInterface $preview
 * @property-read string $description
 * @property-read float $heightRatio
 * @property-read float $widthRatio
 * @see \Friendica\Content\Image::getHorizontalMasonryHtml()
 */
class MasonryImage extends BaseEntity
{
	/** @var int */
	protected $uriId;
	/** @var UriInterface */
	protected $url;
	/** @var ?UriInterface */
	protected $preview;
	/** @var string */
	protected $description;
	/** @var float Ratio of the width of the image relative to the total width of the images on the row */
	protected $widthRatio;
	/** @var float Ratio of the height of the image relative to its width for height allocation */
	protected $heightRatio;

	public function __construct(int $uriId, UriInterface $url, ?UriInterface $preview, string $description, float $widthRatio, float $heightRatio)
	{
		$this->url         = $url;
		$this->uriId       = $uriId;
		$this->preview     = $preview;
		$this->description = $description;
		$this->widthRatio  = $widthRatio;
		$this->heightRatio = $heightRatio;
	}
}
