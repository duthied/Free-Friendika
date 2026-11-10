<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Network\HTTPException;

use Friendica\Network\HTTPException;

class InternalServerErrorException extends HTTPException
{
	protected $code        = 500;
	protected $httpdesc    = 'Internal Server Error';
	protected $explanation = 'An unexpected condition was encountered and no more specific message is suitable.';
}
