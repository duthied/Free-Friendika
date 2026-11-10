<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Network\HTTPException;

use Friendica\Network\HTTPException;

class ConflictException extends HTTPException
{
	protected $code        = 409;
	protected $httpdesc    = 'Conflict ';
	protected $explanation = 'Indicates that the request could not be processed because of conflict in the current state of the resource, such as an edit conflict between multiple simultaneous updates.';
}
