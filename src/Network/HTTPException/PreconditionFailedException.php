<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Network\HTTPException;

use Friendica\Network\HTTPException;

class PreconditionFailedException extends HTTPException
{
	protected $code        = 412;
	protected $httpdesc    = 'Precondition Failed';
	protected $explanation = 'The server does not meet one of the preconditions that the requester put on the request header fields.';
}
