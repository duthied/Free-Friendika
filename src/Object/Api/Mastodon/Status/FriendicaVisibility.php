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

namespace Friendica\Object\Api\Mastodon\Status;

use Friendica\BaseDataTransferObject;

/**
 * Class FriendicaVisibility
 *
 * Fields for the user's visibility settings on a post if they own that post
 *
 * @see https://docs.joinmastodon.org/entities/status
 */
class FriendicaVisibility extends BaseDataTransferObject
{
	/** @var string|null */
	protected $allow_cid;
	/** @var string|null */
	protected $deny_cid;
	/** @var string|null */
	protected $allow_gid;
	/** @var string|null */
	protected $deny_gid;

	public function __construct(?string $allow_cid, ?string $deny_cid, ?string $allow_gid, ?string $deny_gid)
	{
		$this->allow_cid = $allow_cid;
		$this->deny_cid  = $deny_cid;
		$this->allow_gid = $allow_gid;
		$this->deny_gid  = $deny_gid;
	}
}
