<?php
/**
 * @copyright Copyright (C) 2010-2022, the Friendica project
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

namespace Friendica\Test\src\Core\KeyValueStorage;

use Friendica\Core\Config\ValueObject\Cache;
use Friendica\Core\KeyValueStorage\Capabilities\IManageKeyValuePairs;
use Friendica\Core\KeyValueStorage\Type\DBKeyValueStorage;
use Friendica\Database\Database;
use Friendica\Database\Definition\DbaDefinition;
use Friendica\Database\Definition\ViewDefinition;
use Friendica\Test\DatabaseTestTrait;
use Friendica\Test\Util\Database\StaticDatabase;
use Friendica\Util\BasePath;
use Friendica\Util\Profiler;

class DBKeyValueStorageTest extends KeyValueStorageTest
{
	use DatabaseTestTrait;

	/** @var Database */
	protected $database;

	protected function setUp(): void
	{
		parent::setUp();

		$this->setUpDb();
	}

	protected function tearDown(): void
	{
		parent::tearDown();

		$this->tearDownDb();
	}

	public function getInstance(): IManageKeyValuePairs
	{
		$cache = new Cache();
		$cache->set('database', 'disable_pdo', true);

		$basePath = new BasePath(dirname(__FILE__, 5), $_SERVER);

		$this->database = new StaticDatabase($cache, new Profiler($cache), (new DbaDefinition($basePath->getPath()))->load(), (new ViewDefinition($basePath->getPath()))->load());
		$this->database->setTestmode(true);

		return new DBKeyValueStorage($this->database);
	}

	/** @dataProvider dataTests */
	public function testUpdatedAt($k, $v)
	{
		$instance = $this->getInstance();

		$instance->set($k, $v);

		self::assertEquals($v, $instance->get($k));
		self::assertEquals($v, $instance[$k]);

		$entry = $this->database->selectFirst(DBKeyValueStorage::DB_KEY_VALUE_TABLE, ['updated_at'], ['k' => $k]);
		self::assertNotEmpty($entry);

		$updateAt = $entry['updated_at'];

		$instance->set($k, 'another_value');

		self::assertEquals('another_value', $instance->get($k));
		self::assertEquals('another_value', $instance[$k]);

		$entry = $this->database->selectFirst(DBKeyValueStorage::DB_KEY_VALUE_TABLE, ['updated_at'], ['k' => $k]);
		self::assertNotEmpty($entry);

		$updateAtAfter = $entry['updated_at'];

		self::assertLessThanOrEqual($updateAt, $updateAtAfter);
	}
}
