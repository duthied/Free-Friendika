<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Object\Api\Mastodon\InstanceV2;

use Friendica\BaseDataTransferObject;

/**
 * Class Usage
 *
 * @see https://docs.joinmastodon.org/entities/Instance/
 */
class Usage extends BaseDataTransferObject
{
	/** @var UserStats */
	protected $users;

	/**
	 * @param UserStats $users
	 */
	public function __construct(UserStats $users)
	{
		$this->users = $users;
	}
}
