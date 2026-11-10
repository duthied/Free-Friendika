<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Network\HTTPException;

use Friendica\Network\HTTPException;

class TooManyRequestsException extends HTTPException
{
	protected $code        = 429;
	protected $httpdesc    = 'Too Many Requests';
	protected $explanation = 'The user has sent too many requests in a given amount of time.';
}
