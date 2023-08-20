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

namespace Friendica\User\Settings\Entity;

use Friendica\Federation\Entity\GServer;

/**
 * @property-read int      $uid
 * @property-read int      $gsid
 * @property-read bool     $ignored
 * @property-read ?GServer $gserver
 */
class UserGServer extends \Friendica\BaseEntity
{
	/** @var int User id */
	protected $uid;
	/** @var int GServer id */
	protected $gsid;
	/** @var bool Whether the user ignored this server */
	protected $ignored;
	/** @var ?GServer */
	protected $gserver;

	public function __construct(int $uid, int $gsid, bool $ignored = false, ?GServer $gserver = null)
	{
		$this->uid     = $uid;
		$this->gsid    = $gsid;
		$this->ignored = $ignored;
		$this->gserver = $gserver;
	}

	/**
	 * Toggle the ignored property.
	 *
	 * Chainable.
	 *
	 * @return $this
	 */
	public function toggleIgnored(): UserGServer
	{
		$this->ignored = !$this->ignored;

		return $this;
	}

	/**
	 * Set the ignored property.
	 *
	 * Chainable.
	 *
	 * @return $this
	 */
	public function ignore(): UserGServer
	{
		$this->ignored = true;

		return $this;
	}

	/**
	 * Unset the ignored property.
	 *
	 * Chainable.
	 *
	 * @return $this
	 */
	public function unignore(): UserGServer
	{
		$this->ignored = false;

		return $this;
	}
}
