<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Api\Mastodon\Instance;

use Friendica\Core\System;
use Friendica\Module\BaseApi;
use Friendica\Network\HTTPException;

/**
 * Undocumented API endpoint
 */
class Rules extends BaseApi
{
	/**
	 * @throws HTTPException\InternalServerErrorException
	 */
	protected function rawContent(array $request = []): never
	{
		$this->earlyJsonExit(System::getRules());
	}
}
