<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Contact\FriendSuggest\Entity;

use Friendica\BaseEntity;

/**
 * Model for interacting with a friend suggestion
 *
 * @property-read int $uid
 * @property-read int $cid
 * @property-read string $name
 * @property-read string $url
 * @property-read string $request
 * @property-read string $photo
 * @property-read string $note
 * @property-read \DateTime $created
 * @property-read int|null $id
 */
class FriendSuggest extends BaseEntity
{
	/** @var int */
	protected $uid;
	/** @var int */
	protected $cid;
	/** @var string */
	protected $name;
	/** @var string */
	protected $url;
	/** @var string */
	protected $request;
	/** @var string */
	protected $photo;
	/** @var string */
	protected $note;
	/** @var \DateTime */
	protected $created;
	/** @var int|null */
	protected $id;

	/**
	 * @param int       $uid
	 * @param int       $cid
	 * @param string    $name
	 * @param string    $url
	 * @param string    $request
	 * @param string    $photo
	 * @param string    $note
	 * @param \DateTime $created
	 * @param int|null  $id
	 */
	public function __construct(int $uid, int $cid, string $name, string $url, string $request, string $photo, string $note, \DateTime $created, ?int $id = null)
	{
		$this->uid     = $uid;
		$this->cid     = $cid;
		$this->name    = $name;
		$this->url     = $url;
		$this->request = $request;
		$this->photo   = $photo;
		$this->note    = $note;
		$this->created = $created;
		$this->id      = $id;
	}
}
