<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Network\HTTPException;

use Friendica\Network\HTTPException;

class BadRequestException extends HTTPException
{
	protected $code        = 400;
	protected $httpdesc    = 'Bad Request';
	protected $explanation = 'The server cannot or will not process the request due to an apparent client error.';
}
