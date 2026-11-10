<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Network\HTTPException;

use Friendica\Network\HTTPException;

class GatewayTimeoutException extends HTTPException
{
	protected $code        = 504;
	protected $httpdesc    = 'Gateway Timeout';
	protected $explanation = 'The server was acting as a gateway or proxy and did not receive a timely response from the upstream server.';
}
