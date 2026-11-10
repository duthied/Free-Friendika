<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Object\Api\Mastodon\Status;

/**
 * Class Counts
 *
 * @see https://docs.joinmastodon.org/entities/status
 *
 * @property-read int $replies
 * @property-read int $reblogs
 * @property-read int $favourites
 * @property-read int $dislikes
 */
class Counts
{
	/** @var int */
	protected $replies;
	/** @var int */
	protected $reblogs;
	/** @var int */
	protected $favourites;

	/** @var int */
	protected $dislikes;

	/**
	 * Creates a status count object
	 *
	 * @param int $replies
	 * @param int $reblogs
	 * @param int $favourites
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public function __construct(int $replies, int $reblogs, int $favourites, int $dislikes)
	{
		$this->replies    = $replies;
		$this->reblogs    = $reblogs;
		$this->favourites = $favourites;
		$this->dislikes   = $dislikes;
	}

	public function __get($name)
	{
		return $this->$name;
	}
}
