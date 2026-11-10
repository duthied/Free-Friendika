<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Network\HTTPException;

use Friendica\Network\HTTPException;

class ExpectationFailedException extends HTTPException
{
	protected $code        = 417;
	protected $httpdesc    = 'Expectation Failed';
	protected $explanation = 'The server cannot meet the requirements of the Expect request-header field.';
}
