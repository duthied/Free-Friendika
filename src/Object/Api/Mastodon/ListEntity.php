<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Object\Api\Mastodon;

use Friendica\BaseDataTransferObject;

/**
 * Class ListEntity
 *
 * @see https://docs.joinmastodon.org/entities/list/
 */
class ListEntity extends BaseDataTransferObject
{
	/** @var string */
	protected $id;
	/** @var string */
	protected $title;
	/** @var string */
	protected $replies_policy;

	/**
	 * Creates an list record
	 *
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public function __construct(string $id, string $title, string $policy)
	{
		$this->id             = $id;
		$this->title          = $title;
		$this->replies_policy = $policy;
	}
}
