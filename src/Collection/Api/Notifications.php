<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Collection\Api;

use Friendica\BaseCollection;
use Friendica\Object\Api\Friendica\Notification;

class Notifications extends BaseCollection
{
	/**
	 * @return Notification
	 */
	#[\ReturnTypeWillChange]
	public function current()
	{
		return parent::current();
	}
}
