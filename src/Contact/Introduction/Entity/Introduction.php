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

namespace Friendica\Contact\Introduction\Entity;

use Friendica\BaseEntity;

/**
 * @property-read int $uid
 * @property-read int $sid
 * @property-read int|null $fid
 * @property-read int|null $cid
 * @property-read bool $knowyou
 * @property-read bool $duplex
 * @property-read string $note
 * @property-read string $hash
 * @property-read \DateTime $datetime
 * @property-read bool $ignore
 * @property-read int|null $id
 */
class Introduction extends BaseEntity
{
	/** @var int */
	protected $uid;
	/** @var int */
	protected $sid;
	/** @var int|null */
	protected $fid;
	/** @var int|null */
	protected $cid;
	/** @var bool */
	protected $knowyou;
	/** @var bool */
	protected $duplex;
	/** @var string */
	protected $note;
	/** @var string */
	protected $hash;
	/** @var \DateTime */
	protected $datetime;
	/** @var bool */
	protected $ignore;
	/** @var int|null */
	protected $id;

	/**
	 * @param int       $uid
	 * @param int       $sid
	 * @param int|null  $fid
	 * @param int|null  $cid
	 * @param bool      $knowyou
	 * @param bool      $duplex
	 * @param string    $note
	 * @param string    $hash
	 * @param \DateTime $datetime
	 * @param bool      $ignore
	 * @param int|null  $id
	 */
	public function __construct(int $uid, int $sid, ?int $fid, ?int $cid, bool $knowyou, bool $duplex, string $note, string $hash, \DateTime $datetime, bool $ignore, ?int $id)
	{
		$this->uid     = $uid;
		$this->sid     = $sid;
		$this->fid     = $fid;
		$this->cid     = $cid;
		$this->knowyou = $knowyou;
		$this->duplex  = $duplex;
		$this->note    = $note;
		$this->hash    = $hash;
		$this->ignore  = $ignore;
		$this->id      = $id;
	}

	/**
	 * Ignore the current Introduction
	 */
	public function ignore()
	{
		$this->ignore = true;
	}
}
