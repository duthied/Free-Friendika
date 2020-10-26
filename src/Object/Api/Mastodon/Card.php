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

namespace Friendica\Object\Api\Mastodon;

use Friendica\BaseEntity;

/**
 * Class Card
 *
 * @see https://docs.joinmastodon.org/entities/card
 */
class Card extends BaseEntity
{
	/** @var string */
	protected $url;
	/** @var string */
	protected $title;
	/** @var string */
	protected $description;
	/** @var string */
	protected $type;
	/** @var string */
	protected $provider_name;
	/** @var string */
	protected $provider_url;
	/** @var string */
	protected $image;

	/**
	 * Creates a card record from an attachment array.
	 *
	 * @param array   $attachment Attachment record
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public function __construct(array $attachment)
	{
		$this->url           = $attachment['url'] ?? '';
		$this->title         = $attachment['title'] ?? '';
		$this->description   = $attachment['description'] ?? '';
		$this->type          = $attachment['type'] ?? '';
		$this->image         = $attachment['image'] ?? '';
		$this->provider_name = $attachment['provider_name'] ?? '';
		$this->provider_url  = $attachment['provider_url'] ?? '';
	}

	/**
	 * Returns the current entity as an array
	 *
	 * @return array
	 */
	public function toArray()
	{
		if (empty($this->url)) {
			return null;
		}

		return parent::toArray();
	}
}
