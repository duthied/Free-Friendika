<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Network\HTTPException;

use Friendica\Network\HTTPException;

class GoneException extends HTTPException
{
	protected $code        = 410;
	protected $httpdesc    = 'Gone';
	protected $explanation = 'Indicates that the resource requested is no longer available and will not be available again.';
}
