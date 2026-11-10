<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module;

use Friendica\BaseModule;

/**
 * Redirect to the friendica page
 */
class About extends BaseModule
{
	protected function rawContent(array $request = [])
	{
		$this->baseUrl->redirect('friendica');
	}
}
