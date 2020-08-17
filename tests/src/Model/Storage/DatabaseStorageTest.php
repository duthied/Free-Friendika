<?php
/**
 * @copyright Copyright (C) 2020, Friendica
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

namespace Friendica\Test\src\Model\Storage;

use Friendica\Core\L10n;
use Friendica\Factory\ConfigFactory;
use Friendica\Model\Storage\Database;
use Friendica\Model\Storage\IStorage;
use Friendica\Test\DatabaseTestTrait;
use Friendica\Test\Util\Database\StaticDatabase;
use Friendica\Test\Util\VFSTrait;
use Friendica\Util\ConfigFileLoader;
use Friendica\Util\Profiler;
use Mockery\MockInterface;
use Psr\Log\NullLogger;

class DatabaseStorageTest extends StorageTest
{
	use DatabaseTestTrait;
	use VFSTrait;

	protected function setUp()
	{
		$this->setUpVfsDir();

		parent::setUp();
	}

	protected function getInstance()
	{
		$logger = new NullLogger();
		$profiler = \Mockery::mock(Profiler::class);
		$profiler->shouldReceive('saveTimestamp')->withAnyArgs()->andReturn(true);

		// load real config to avoid mocking every config-entry which is related to the Database class
		$configFactory = new ConfigFactory();
		$loader = new ConfigFileLoader($this->root->url());
		$configCache = $configFactory->createCache($loader);

		$dba = new StaticDatabase($configCache, $profiler, $logger);

		/** @var MockInterface|L10n $l10n */
		$l10n = \Mockery::mock(L10n::class)->makePartial();

		return new Database($dba, $logger, $l10n);
	}

	protected function assertOption(IStorage $storage)
	{
		$this->assertEmpty($storage->getOptions());
	}
}
