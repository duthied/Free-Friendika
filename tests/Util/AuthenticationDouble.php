<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\Util;

use Friendica\Security\Authentication;

class AuthenticationDouble extends Authentication
{
	protected function setXAccMgmtStatusHeader(array $user_record)
	{
		// Don't set any header..
	}
}
