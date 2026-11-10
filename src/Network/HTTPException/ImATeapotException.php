<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Network\HTTPException;

use Friendica\Network\HTTPException;

class ImATeapotException extends HTTPException
{
	protected $code        = 418;
	protected $httpdesc    = "I'm A Teapot";
	protected $explanation = 'This is a teapot that is requested to brew coffee.';
}
