<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\HTTPException;

use Friendica\BaseModule;
use Friendica\Network\HTTPException;

class MethodNotAllowed extends BaseModule
{
	protected function content(array $request = []): string
	{
		throw new HTTPException\MethodNotAllowedException($this->t('Method Not Allowed.'));
	}
}
