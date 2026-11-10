<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test;

/**
 * Abstract class used by tests that need a database.
 */
abstract class DatabaseTestCase extends MockedTestCase
{
	use DatabaseTestTrait;

	protected function setUp(): void
	{
		$this->setUpDb();

		parent::setUp();
	}

	protected function tearDown(): void
	{
		$this->tearDownDb();

		parent::tearDown();
	}
}
