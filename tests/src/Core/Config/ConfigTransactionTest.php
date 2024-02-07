<?php
/**
 * @copyright Copyright (C) 2010-2024, the Friendica project
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

namespace Friendica\Test\src\Core\Config;

use Friendica\Core\Config\Capability\ISetConfigValuesTransactionally;
use Friendica\Core\Config\Model\DatabaseConfig;
use Friendica\Core\Config\Model\ReadOnlyFileConfig;
use Friendica\Core\Config\Model\ConfigTransaction;
use Friendica\Core\Config\Util\ConfigFileManager;
use Friendica\Core\Config\ValueObject\Cache;
use Friendica\Database\Database;
use Friendica\Test\DatabaseTest;
use Friendica\Test\FixtureTest;
use Friendica\Test\MockedTest;
use Friendica\Test\Util\Database\StaticDatabase;
use Friendica\Test\Util\VFSTrait;
use Mockery\Exception\InvalidCountException;

class ConfigTransactionTest extends FixtureTest
{
	/** @var ConfigFileManager */
	protected $configFileManager;

	protected function setUp(): void
	{
		parent::setUp();

		$this->configFileManager = new ConfigFileManager($this->root->url(), $this->root->url() . '/config/', $this->root->url() . '/static/');
	}

	public function dataTests(): array
	{
		return [
			'default' => [
				'data' => include dirname(__FILE__, 4) . '/datasets/B.node.config.php',
			]
		];
	}

	public function testInstance()
	{
		$config            = new DatabaseConfig($this->dice->create(Database::class), new Cache());
		$configTransaction = new ConfigTransaction($config);

		self::assertInstanceOf(ISetConfigValuesTransactionally::class, $configTransaction);
		self::assertInstanceOf(ConfigTransaction::class, $configTransaction);
	}

	public function testConfigTransaction()
	{
		$config = new DatabaseConfig($this->dice->create(Database::class), new Cache());
		$config->set('config', 'key1', 'value1');
		$config->set('system', 'key2', 'value2');
		$config->set('system', 'keyDel', 'valueDel');
		$config->set('delete', 'keyDel', 'catDel');

		$configTransaction = new ConfigTransaction($config);

		// new key-value
		$configTransaction->set('transaction', 'key3', 'value3');
		// overwrite key-value
		$configTransaction->set('config', 'key1', 'changedValue1');
		// delete key-value
		$configTransaction->delete('system', 'keyDel');
		// delete last key of category - so the category is gone
		$configTransaction->delete('delete', 'keyDel');

		// The main config still doesn't know about the change
		self::assertNull($config->get('transaction', 'key3'));
		self::assertEquals('value1', $config->get('config', 'key1'));
		self::assertEquals('valueDel', $config->get('system', 'keyDel'));
		self::assertEquals('catDel', $config->get('delete', 'keyDel'));
		// The config file still doesn't know it either

		// save it back!
		$configTransaction->commit();

		// Now every config and file knows the change
		self::assertEquals('changedValue1', $config->get('config', 'key1'));
		self::assertEquals('value3', $config->get('transaction', 'key3'));
		self::assertNull($config->get('system', 'keyDel'));
		self::assertNull($config->get('delete', 'keyDel'));
		// the whole category should be gone
		self::assertNull($tempData['delete'] ?? null);
	}

	/**
	 * This test asserts that in empty transactions, no saveData is called, thus no config file writing was performed
	 */
	public function testNothingToDo()
	{
		$this->configFileManager = \Mockery::spy(ConfigFileManager::class);

		$config = new DatabaseConfig($this->dice->create(Database::class), new Cache());
		$configTransaction = new ConfigTransaction($config);

		// commit empty transaction
		$configTransaction->commit();

		try {
			$this->configFileManager->shouldNotHaveReceived('saveData');
		} catch (InvalidCountException $exception) {
			self::fail($exception);
		}

		// If not failed, the test ends successfully :)
		self::assertTrue(true);
	}
}
