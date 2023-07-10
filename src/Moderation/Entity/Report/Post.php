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

namespace Friendica\Moderation\Entity\Report;

/**
 * @property-read int $uriId URI Id of the reported post
 * @property-read int $status One of STATUS_*
 */
final class Post extends \Friendica\BaseEntity
{
	const STATUS_NO_ACTION = 0;
	const STATUS_UNLISTED  = 1;
	const STATUS_DELETED   = 2;

	/** @var int */
	protected $uriId;
	/** @var int|null */
	protected $status;

	public function __construct(int $uriId, int $status = self::STATUS_NO_ACTION)
	{
		$this->uriId  = $uriId;
		$this->status = $status;
	}
}
