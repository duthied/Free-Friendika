<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Network\HTTPException;

use Friendica\Network\HTTPException;

class NotImplementedException extends HTTPException
{
	protected $code        = 501;
	protected $httpdesc    = 'Not Implemented';
	protected $explanation = 'The server either does not recognize the request method, or it lacks the ability to fulfil the request. Usually this implies future availability (e.g., a new feature of a web-service API).';
}
