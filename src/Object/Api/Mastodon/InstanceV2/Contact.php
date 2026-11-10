<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Object\Api\Mastodon\InstanceV2;

use Friendica\BaseDataTransferObject;
use Friendica\Object\Api\Mastodon\Account;

/**
 * Class Contact
 *
 * @see https://docs.joinmastodon.org/entities/Instance/
 */
class Contact extends BaseDataTransferObject
{
	/** @var string */
	protected $email;
	/** @var Account|null */
	protected $account = null;


	/**
	 * @param string $email
	 * @param Account|null $account
	 */
	public function __construct(string $email, ?Account $account)
	{
		$this->email   = $email;
		$this->account = $account;
	}
}
