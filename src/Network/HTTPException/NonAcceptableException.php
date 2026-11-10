<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Network\HTTPException;

use Friendica\Network\HTTPException;

class NonAcceptableException extends HTTPException
{
	protected $code        = 406;
	protected $httpdesc    = 'Not Acceptable';
	protected $explanation = 'The requested resource is capable of generating only content not acceptable according to the Accept headers sent in the request.';
}
