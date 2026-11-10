<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\src\Core\Storage;

use Friendica\Core\Storage\Type\Database;
use Friendica\Test\StorageTestCase;
use Friendica\Test\Util\CreateDatabaseTrait;

class DatabaseStorageTest extends StorageTestCase
{
	use CreateDatabaseTrait;

	protected function setUp(): void
	{
		$this->setUpVfsDir();

		$this->setUpDb();

		parent::setUp();
	}

	protected function getInstance()
	{
		return new Database($this->getDbInstance());
	}

	protected function tearDown(): void
	{
		parent::tearDown();

		$this->tearDownDb();
	}
}
