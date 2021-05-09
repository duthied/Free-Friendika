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

namespace Friendica\Object\Api\Mastodon;

use Friendica\App\BaseURL;
use Friendica\BaseDataTransferObject;

/**
 * Class Tag
 *
 * @see https://docs.joinmastodon.org/entities/tag
 */
class Tag extends BaseDataTransferObject
{
	/** @var string */
	protected $name;
	/** @var string */
	protected $url = null;

	/**
	 * Creates a hashtag record from an tag-view record.
	 *
	 * @param BaseURL $baseUrl
	 * @param array   $tag     tag-view record
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public function __construct(BaseURL $baseUrl, array $tag)
	{
		$this->name = strtolower($tag['name']);
		$this->url  = $baseUrl . '/search?tag=' . urlencode($this->name);
	}
}
