<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

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
