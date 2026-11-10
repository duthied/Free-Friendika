<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Network\HTTPException;

use Friendica\Network\HTTPException;

class UnauthorizedException extends HTTPException
{
	protected $code        = 401;
	protected $httpdesc    = 'Unauthorized';
	protected $explanation = 'Authentication is required and has failed or has not yet been provided.';
}
