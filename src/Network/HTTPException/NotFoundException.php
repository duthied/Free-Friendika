<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Network\HTTPException;

use Friendica\Network\HTTPException;

class NotFoundException extends HTTPException
{
	protected $code        = 404;
	protected $httpdesc    = 'Not Found';
	protected $explanation = 'The requested resource could not be found but may be available in the future.';
}
