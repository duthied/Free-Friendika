<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Object\Api\Mastodon;

use Friendica\BaseDataTransferObject;

/**
 * Class Subscription
 *
 * @see https://docs.joinmastodon.org/entities/pushsubscription
 */
class Subscription extends BaseDataTransferObject
{
	/** @var string */
	protected $id;
	/** @var string|null (URL)*/
	protected $endpoint;
	/** @var array */
	protected $alerts;
	/** @var string */
	protected $server_key;

	/**
	 * Creates a subscription record from an item record.
	 *
	 * @param array  $subscription
	 * @param string $vapid
	 */
	public function __construct(array $subscription, string $vapid)
	{
		$this->id       = (string) $subscription['id'];
		$this->endpoint = $subscription['endpoint'];
		$this->alerts   = [
			Notification::TYPE_FOLLOW  => (bool) $subscription[Notification::TYPE_FOLLOW],
			Notification::TYPE_LIKE    => (bool) $subscription[Notification::TYPE_LIKE],
			Notification::TYPE_RESHARE => (bool) $subscription[Notification::TYPE_RESHARE],
			Notification::TYPE_MENTION => (bool) $subscription[Notification::TYPE_MENTION],
			Notification::TYPE_POLL    => (bool) $subscription[Notification::TYPE_POLL],
		];

		$this->server_key = $vapid;
	}
}
