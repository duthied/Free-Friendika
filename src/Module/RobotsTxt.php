<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module;

use Friendica\BaseModule;
use Friendica\Core\System;

/**
 * Return the default robots.txt
 */
class RobotsTxt extends BaseModule
{
	protected function rawContent(array $request = [])
	{
		$allDisallowed = [
			'/settings/',
			'/admin/',
			'/message/',
			'/search',
			'/help',
			'/proxy',
			'/photo',
			'/avatar',
		];

		header('Content-Type: text/plain');
		echo 'User-agent: *' . PHP_EOL;
		foreach ($allDisallowed as $disallowed) {
			echo 'Disallow: ' . $disallowed . PHP_EOL;
		}

		echo PHP_EOL;
		echo 'User-agent: ChatGPT-User' . PHP_EOL;
		echo 'Disallow: /' . PHP_EOL;

		echo PHP_EOL;
		echo 'User-agent: Google-Extended' . PHP_EOL;
		echo 'Disallow: /' . PHP_EOL;

		echo PHP_EOL;
		echo 'User-agent: GPTBot' . PHP_EOL;
		echo 'Disallow: /' . PHP_EOL;

		System::exit();
	}
}
