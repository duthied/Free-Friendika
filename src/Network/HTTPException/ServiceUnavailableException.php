<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Network\HTTPException;

use Friendica\Network\HTTPException;

class ServiceUnavailableException extends HTTPException
{
	protected $code        = 503;
	protected $httpdesc    = 'Service Unavailable';
	protected $explanation = 'The server is currently unavailable (because it is overloaded or down for maintenance). Please try again later.';
}
