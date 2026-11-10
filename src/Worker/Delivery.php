<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Worker;

use Friendica\Protocol\Delivery as ProtocolDelivery;

class Delivery
{
	public static function execute(string $cmd, int $post_uriid, int $contact_id, int $sender_uid = 0)
	{
		ProtocolDelivery::deliver($cmd, $post_uriid, $contact_id, $sender_uid);
	}
}
