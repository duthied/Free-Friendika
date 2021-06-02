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

use Friendica\BaseDataTransferObject;
use Friendica\Util\DateTimeFormat;

/**
 * Class Notification
 *
 * @see https://docs.joinmastodon.org/entities/notification/
 */
class Notification extends BaseDataTransferObject
{
	/** @var string */
	protected $id;
	/** @var string (Enumerable oneOf) */
	protected $type;
	/** @var string (Datetime) */
	protected $created_at;
	/** @var Account */
	protected $account;
	/** @var Status|null */
	protected $status = null;

	/**
	 * Creates a notification record
	 *
	 * @param array   $item
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public function __construct(int $id, string $type, string $created_at, Account $account = null, Status $status = null)
	{
		$this->id         = (string)$id;
		$this->type       = $type;
		$this->created_at = DateTimeFormat::utc($created_at, DateTimeFormat::API);
		$this->account    = $account->toArray();

		if (!empty($status)) {
			$this->status = $status->toArray();
		}
	}

	/**
	 * Returns the current entity as an array
	 *
	 * @return array
	 */
	public function toArray(): array
	{
		$notification = parent::toArray();

		if (!$notification['status']) {
			unset($notification['status']);
		}

		return $notification;
	}
}
