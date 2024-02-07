<?php
/**
 * @copyright Copyright (C) 2010-2024, the Friendica project
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

namespace Friendica\Object\Api\Mastodon\Status;

use Friendica\BaseDataTransferObject;
use Friendica\Util\DateTimeFormat;

/**
 * Class FriendicaExtension
 *
 * Additional fields on Mastodon Statuses for storing Friendica specific data
 *
 * @see https://docs.joinmastodon.org/entities/status
 */
class FriendicaExtension extends BaseDataTransferObject
{
	/** @var string */
	protected $title;

	/** @var string|null (Datetime) */
	protected $changed_at;

	/** @var string|null (Datetime) */
	protected $commented_at;

	/** @var string|null (Datetime) */
	protected $received_at;

	/** @var FriendicaDeliveryData|null */
	protected $delivery_data;

	/** @var int */
	protected $dislikes_count;

	/** @var bool */
	protected $disliked = false;

	/**
	 * @var FriendicaVisibility|null
	 */
	protected $visibility;

	/**
	 * Creates a FriendicaExtension object
	 *
	 * @param string                 $title
	 * @param ?string                $changed_at
	 * @param ?string                $commented_at
	 * @param ?string                $received_at
	 * @param int                    $dislikes_count
	 * @param bool                   $disliked
	 * @param ?FriendicaDeliveryData $delivery_data
	 * @param ?FriendicaVisibility   $visibility
	 * @throws \Exception
	 */
	public function __construct(
		string $title,
		?string $changed_at,
		?string $commented_at,
		?string $received_at,
		int $dislikes_count,
		bool $disliked,
		?FriendicaDeliveryData $delivery_data,
		?FriendicaVisibility $visibility
	) {
		$this->title          = $title;
		$this->changed_at     = $changed_at ? DateTimeFormat::utc($changed_at, DateTimeFormat::JSON) : null;
		$this->commented_at   = $commented_at ? DateTimeFormat::utc($commented_at, DateTimeFormat::JSON) : null;
		$this->received_at    = $received_at ? DateTimeFormat::utc($received_at, DateTimeFormat::JSON) : null;
		$this->delivery_data  = $delivery_data;
		$this->dislikes_count = $dislikes_count;
		$this->disliked       = $disliked;
		$this->visibility     = $visibility;
	}

	/**
	 * Returns the current changed_at string or null if not set
	 * @return ?string
	 */
	public function changedAt(): ?string
	{
		return $this->changed_at;
	}

	/**
	 * Returns the current commented_at string or null if not set
	 * @return ?string
	 */
	public function commentedAt(): ?string
	{
		return $this->commented_at;
	}

	/**
	 * Returns the current received_at string or null if not set
	 * @return ?string
	 */
	public function receivedAt(): ?string
	{
		return $this->received_at;
	}
}
