<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Contact\Introduction\Entity;

use Friendica\BaseEntity;

/**
 * @property-read int $uid
 * @property-read int $cid Either a public contact id (DFRN suggestion) or user-specific id (Contact::addRelationship)
 * @property-read int|null $sid
 * @property-read bool $knowyou
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
	protected $cid;
	/** @var int|null */
	protected $sid;
	/** @var bool */
	protected $knowyou;
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
	 * @param int       $cid
	 * @param int|null  $sid
	 * @param bool      $knowyou
	 * @param string    $note
	 * @param string    $hash
	 * @param \DateTime $datetime
	 * @param bool      $ignore
	 * @param int|null  $id
	 */
	public function __construct(int $uid, int $cid, ?int $sid, bool $knowyou, string $note, string $hash, \DateTime $datetime, bool $ignore, ?int $id)
	{
		$this->uid      = $uid;
		$this->cid      = $cid;
		$this->sid      = $sid;
		$this->knowyou  = $knowyou;
		$this->note     = $note;
		$this->hash     = $hash;
		$this->datetime = $datetime;
		$this->ignore   = $ignore;
		$this->id       = $id;
	}

	/**
	 * Ignore the current Introduction
	 */
	public function ignore()
	{
		$this->ignore = true;
	}
}
