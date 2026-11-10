<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Factory\Api\Mastodon;

use Friendica\BaseFactory;
use Friendica\Database\DBA;
use Friendica\Model\Subscription as ModelSubscription;
use Friendica\Object\Api\Mastodon\Subscription as SubscriptionObject;

class Subscription extends BaseFactory
{
	/**
	 * @param int $applicationid Application Id
	 * @param int $uid           Item user
	 */
	public function createForApplicationIdAndUserId(int $applicationid, int $uid): SubscriptionObject
	{
		$subscription = DBA::selectFirst('subscription', [], ['application-id' => $applicationid, 'uid' => $uid]);
		return new SubscriptionObject($subscription, ModelSubscription::getPublicVapidKey());
	}
}
