<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Network\HTTPException;

use Friendica\Network\HTTPException;

class ForbiddenException extends HTTPException
{
	protected $code        = 403;
	protected $httpdesc    = 'Forbidden';
	protected $explanation = 'The request was valid, but the server is refusing action. The user might not have the necessary permissions for a resource, or may need an account.';
}
