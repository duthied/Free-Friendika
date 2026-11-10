<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Object\Api\Mastodon\InstanceV2;

use Friendica\BaseDataTransferObject;

/**
 * Class UserStats
 *
 * @see https://docs.joinmastodon.org/entities/Instance/
 */
class UserStats extends BaseDataTransferObject
{
	/** @var int */
	protected $active_month = 0;

	/**
	 * @param int $active_month
	 */
	public function __construct(int $active_month)
	{
		$this->active_month = $active_month;
	}
}
