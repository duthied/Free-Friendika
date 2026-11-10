<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Network\HTTPException;

use Friendica\Network\HTTPException;

class UnprocessableEntityException extends HTTPException
{
	protected $code        = 422;
	protected $httpdesc    = 'Unprocessable Entity';
	protected $explanation = 'The request was well-formed but was unable to be followed due to semantic errors.';
}
