<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\Unit\Object\Api\Mastodon;

use Friendica\Object\Api\Mastodon\Preferences;
use PHPUnit\Framework\TestCase;

class PreferencesTest extends TestCase
{
	public function testToArrayReturnsArray(): void
	{
		$preferences = new Preferences('visibility', true, 'language', 'media', false);

		self::assertSame(
			[
				'posting:default:visibility' => 'visibility',
				'posting:default:sensitive'  => true,
				'posting:default:language'   => 'language',
				'reading:expand:media'       => 'media',
				'reading:expand:spoilers'    => false,
			],
			$preferences->toArray(),
		);
	}

	public function testJsonSerializeReturnsArray(): void
	{
		$preferences = new Preferences('visibility', true, 'language', 'media', false);

		self::assertSame(
			[
				'posting:default:visibility' => 'visibility',
				'posting:default:sensitive'  => true,
				'posting:default:language'   => 'language',
				'reading:expand:media'       => 'media',
				'reading:expand:spoilers'    => false,
			],
			$preferences->jsonSerialize(),
		);
	}
}
