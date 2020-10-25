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
use Friendica\Content\Text\BBCode;
use Friendica\Object\Api\Mastodon\Status\Counts;
use Friendica\Object\Api\Mastodon\Status\UserAttributes;
use Friendica\Util\DateTimeFormat;

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
	protected $image;

	/**
	 * Creates a status record from an item record.
	 *
	 * @param array   $attachment Attachment record
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public function __construct(array $attachment)
	{
		$this->url         = $attachment['url'] ?? '';
		$this->title       = $attachment['title'] ?? '';
		$this->description = $attachment['description'] ?? '';
		$this->type        = $attachment['type'] ?? '';
		$this->image       = $attachment['image'] ?? '';
	}
}
