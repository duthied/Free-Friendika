<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test;

use Stringable;

trait LoggerDataTrait
{
	public static function dataTests()
	{
		return [
			'emergency' => [
				'function' => 'emergency',
				'message'  => 'test',
				'context'  => ['a' => 'context'],
			],
			'alert' => [
				'function' => 'alert',
				'message'  => 'test {test}',
				'context'  => ['a' => 'context', 2 => 'so', 'test' => 'works'],
			],
			'critical' => [
				'function' => 'critical',
				'message'  => 'test crit 2345',
				'context'  => ['a' => 'context', 'wit' => ['more', 'array']],
			],
			'error' => [
				'function' => 'error',
				'message'  => 2.554,
				'context'  => [],
			],
			'warning' => [
				'function' => 'warning',
				'message'  => 'test warn',
				'context'  => ['a' => 'context'],
			],
			'notice' => [
				'function' => 'notice',
				'message'  => 2346,
				'context'  => ['a' => 'context'],
			],
			'info' => [
				'function' => 'info',
				'message'  => new class () implements Stringable {
					public function __toString(): string
					{
						return 'test with Stringable';
					}
				},
				'context' => ['a' => 'context'],
			],
			'debug' => [
				'function' => 'debug',
				'message'  => true,
				'context'  => ['a' => false],
			],
		];
	}
}
