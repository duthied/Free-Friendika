<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Network\HTTPException;

use Friendica\Network\HTTPException;

class LengthRequiredException extends HTTPException
{
	protected $code        = 411;
	protected $httpdesc    = 'Length Required';
	protected $explanation = 'The request did not specify the length of its content, which is required by the requested resource.';
}
