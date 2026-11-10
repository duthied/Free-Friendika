<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Network\HTTPException;

use Friendica\Network\HTTPException;

class BadGatewayException extends HTTPException
{
	protected $code        = 502;
	protected $httpdesc    = 'Bad Gateway';
	protected $explanation = 'The server was acting as a gateway or proxy and received an invalid response from the upstream server.';
}
