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
use Friendica\Model\Tag as TagModel;
use Friendica\Network\HTTPException;
use Friendica\Repository\ProfileField;
use Psr\Log\LoggerInterface;

class Tag extends BaseFactory
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
	 * @param int $uriId Uri-ID of the item
	 * @return array
	 * @throws HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public function createFromUriId(int $uriId)
	{
		$hashtags = [];
		$tags = TagModel::getByURIId($uriId, [TagModel::HASHTAG]);
		foreach ($tags as $tag) {
			$hashtag = new \Friendica\Object\Api\Mastodon\Tag($this->baseUrl, $tag);
			$hashtags[] = $hashtag->toArray();
		}
		return $hashtags;
	}
}
