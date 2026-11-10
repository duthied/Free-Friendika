<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Network\HTTPException;

use Friendica\Network\HTTPException;

class UnsupportedMediaTypeException extends HTTPException
{
	protected $code        = 415;
	protected $httpdesc    = 'Unsupported Media Type';
	protected $explanation = 'The request entity has a media type which the server or resource does not support. For example, the client uploads an image as image/svg+xml, but the server requires that images use a different format.';
}
