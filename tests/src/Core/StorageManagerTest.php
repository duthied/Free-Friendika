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

namespace Friendica\Test\src\Core;

use Dice\Dice;
use Friendica\Core\Config\IConfig;
use Friendica\Core\Config\PreloadConfig;
use Friendica\Core\Hook;
use Friendica\Core\L10n;
use Friendica\Core\Session\ISession;
use Friendica\Core\StorageManager;
use Friendica\Database\Database;
use Friendica\DI;
use Friendica\Factory\ConfigFactory;
use Friendica\Model\Config\Config;
use Friendica\Model\Storage;
use Friendica\Core\Session;
use Friendica\Test\DatabaseTest;
use Friendica\Test\Util\Database\StaticDatabase;
use Friendica\Test\Util\VFSTrait;
use Friendica\Util\ConfigFileLoader;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Friendica\Test\Util\SampleStorageBackend;

class StorageManagerTest extends DatabaseTest
{
	/** @var Database */
	private $dba;
	/** @var IConfig */
	private $config;
	/** @var LoggerInterface */
	private $logger;
	/** @var L10n */
	private $l10n;

	use VFSTrait;

	public function setUp()
	{
		parent::setUp();

		$this->setUpVfsDir();

		$this->logger = new NullLogger();

		$profiler = \Mockery::mock(Profiler::class);
		$profiler->shouldReceive('saveTimestamp')->withAnyArgs()->andReturn(true);

		// load real config to avoid mocking every config-entry which is related to the Database class
		$configFactory = new ConfigFactory();
		$loader        = new ConfigFileLoader($this->root->url());
		$configCache   = $configFactory->createCache($loader);

		$this->dba = new StaticDatabase($configCache, $profiler, $this->logger);

		$configModel  = new Config($this->dba);
		$this->config = new PreloadConfig($configCache, $configModel);

		$this->l10n = \Mockery::mock(L10n::class);
	}

	/**
	 * Test plain instancing first
	 */
	public function testInstance()
	{
		$storageManager = new StorageManager($this->dba, $this->config, $this->logger, $this->l10n);

		$this->assertInstanceOf(StorageManager::class, $storageManager);
	}

	public function dataStorages()
	{
		return [
			'empty'          => [
				'name'        => '',
				'assert'      => null,
				'assertName'  => '',
				'userBackend' => false,
			],
			'database'       => [
				'name'        => Storage\Database::NAME,
				'assert'      => Storage\Database::class,
				'assertName'  => Storage\Database::NAME,
				'userBackend' => true,
			],
			'filesystem'     => [
				'name'        => Storage\Filesystem::NAME,
				'assert'      => Storage\Filesystem::class,
				'assertName'  => Storage\Filesystem::NAME,
				'userBackend' => true,
			],
			'systemresource' => [
				'name'        => Storage\SystemResource::NAME,
				'assert'      => Storage\SystemResource::class,
				'assertName'  => Storage\SystemResource::NAME,
				// false here, because SystemResource isn't meant to be a user backend,
				// it's for system resources only
				'userBackend' => false,
			],
			'invalid'        => [
				'name'        => 'invalid',
				'assert'      => null,
				'assertName'  => '',
				'userBackend' => false,
			],
		];
	}

	/**
	 * Data array for legacy backends
	 *
	 * @todo 2020.09 After 2 releases, remove the legacy functionality and these data array with it
	 *
	 * @return array
	 */
	public function dataLegacyBackends()
	{
		return [
			'legacyDatabase'          => [
				'name'        => 'Friendica\Model\Storage\Database',
				'assert'      => Storage\Database::class,
				'assertName'  => Storage\Database::NAME,
				'userBackend' => true,
			],
			'legacyFilesystem'       => [
				'name'        => 'Friendica\Model\Storage\Filesystem',
				'assert'      => Storage\Filesystem::class,
				'assertName'  => Storage\Filesystem::NAME,
				'userBackend' => true,
			],
			'legacySystemResource'     => [
				'name'        => 'Friendica\Model\Storage\SystemResource',
				'assert'      => Storage\SystemResource::class,
				'assertName'  => Storage\SystemResource::NAME,
				'userBackend' => false,
			],
		];
	}

	/**
	 * Test the getByName() method
	 *
	 * @dataProvider dataStorages
	 * @dataProvider dataLegacyBackends
	 */
	public function testGetByName($name, $assert, $assertName, $userBackend)
	{
		$storageManager = new StorageManager($this->dba, $this->config, $this->logger, $this->l10n);

		$storage = $storageManager->getByName($name, $userBackend);

		if (!empty($assert)) {
			$this->assertInstanceOf(Storage\IStorage::class, $storage);
			$this->assertInstanceOf($assert, $storage);
		} else {
			$this->assertNull($storage);
		}
		$this->assertEquals($assertName, $storage);
	}

	/**
	 * Test the isValidBackend() method
	 *
	 * @dataProvider dataStorages
	 */
	public function testIsValidBackend($name, $assert, $assertName, $userBackend)
	{
		$storageManager = new StorageManager($this->dba, $this->config, $this->logger, $this->l10n);

		// true in every of the backends
		$this->assertEquals(!empty($assertName), $storageManager->isValidBackend($name));

		// if userBackend is set to true, filter out e.g. SystemRessource
		$this->assertEquals($userBackend, $storageManager->isValidBackend($name, true));
	}

	/**
	 * Test the method listBackends() with default setting
	 */
	public function testListBackends()
	{
		$storageManager = new StorageManager($this->dba, $this->config, $this->logger, $this->l10n);

		$this->assertEquals(StorageManager::DEFAULT_BACKENDS, $storageManager->listBackends());
	}

	/**
	 * Test the method getBackend()
	 *
	 * @dataProvider dataStorages
	 */
	public function testGetBackend($name, $assert, $assertName, $userBackend)
	{
		$storageManager = new StorageManager($this->dba, $this->config, $this->logger, $this->l10n);

		$this->assertNull($storageManager->getBackend());

		if ($userBackend) {
			$storageManager->setBackend($name);

			$this->assertInstanceOf($assert, $storageManager->getBackend());
		}
	}

	/**
	 * Test the method getBackend() with a pre-configured backend
	 *
	 * @dataProvider dataStorages
	 * @dataProvider dataLegacyBackends
	 */
	public function testPresetBackend($name, $assert, $assertName, $userBackend)
	{
		$this->config->set('storage', 'name', $name);

		$storageManager = new StorageManager($this->dba, $this->config, $this->logger, $this->l10n);

		if ($userBackend) {
			$this->assertInstanceOf($assert, $storageManager->getBackend());
		} else {
			$this->assertNull($storageManager->getBackend());
		}
	}

	/**
	 * Tests the register and unregister methods for a new backend storage class
	 *
	 * Uses a sample storage for testing
	 *
	 * @see SampleStorageBackend
	 */
	public function testRegisterUnregisterBackends()
	{
		/// @todo Remove dice once "Hook" is dynamic and mockable
		$dice   = (new Dice())
			->addRules(include __DIR__ . '/../../../static/dependencies.config.php')
			->addRule(Database::class, ['instanceOf' => StaticDatabase::class, 'shared' => true])
			->addRule(ISession::class, ['instanceOf' => Session\Memory::class, 'shared' => true, 'call' => null]);
		DI::init($dice);

		$storageManager = new StorageManager($this->dba, $this->config, $this->logger, $this->l10n);

		$this->assertTrue($storageManager->register(SampleStorageBackend::class));

		$this->assertEquals(array_merge(StorageManager::DEFAULT_BACKENDS, [
			SampleStorageBackend::getName() => SampleStorageBackend::class,
		]), $storageManager->listBackends());
		$this->assertEquals(array_merge(StorageManager::DEFAULT_BACKENDS, [
			SampleStorageBackend::getName() => SampleStorageBackend::class,
		]), $this->config->get('storage', 'backends'));

		// inline call to register own class as hook (testing purpose only)
		SampleStorageBackend::registerHook();
		Hook::loadHooks();

		$this->assertTrue($storageManager->setBackend(SampleStorageBackend::NAME));
		$this->assertEquals(SampleStorageBackend::NAME, $this->config->get('storage', 'name'));

		$this->assertInstanceOf(SampleStorageBackend::class, $storageManager->getBackend());

		$this->assertTrue($storageManager->unregister(SampleStorageBackend::class));
		$this->assertEquals(StorageManager::DEFAULT_BACKENDS, $this->config->get('storage', 'backends'));
		$this->assertEquals(StorageManager::DEFAULT_BACKENDS, $storageManager->listBackends());

		$this->assertNull($storageManager->getBackend());
		$this->assertNull($this->config->get('storage', 'name'));
	}

	/**
	 * Test moving data to a new storage (currently testing db & filesystem)
	 *
	 * @dataProvider dataStorages
	 */
	public function testMoveStorage($name, $assert, $assertName, $userBackend)
	{
		if (!$userBackend) {
			return;
		}

		$this->loadFixture(__DIR__ . '/../../datasets/storage/database.fixture.php', $this->dba);

		$storageManager = new StorageManager($this->dba, $this->config, $this->logger, $this->l10n);
		$storage = $storageManager->getByName($name);
		$storageManager->move($storage);

		$photos = $this->dba->select('photo', ['backend-ref', 'backend-class', 'id', 'data']);

		while ($photo = $this->dba->fetch($photos)) {

			$this->assertEmpty($photo['data']);

			$storage = $storageManager->getByName($photo['backend-class']);
			$data = $storage->get($photo['backend-ref']);

			$this->assertNotEmpty($data);
		}
	}

	/**
	 * Test moving data to a WRONG storage
	 *
	 * @expectedException \Friendica\Model\Storage\StorageException
	 * @expectedExceptionMessage Can't move to storage backend 'SystemResource'
	 */
	public function testMoveStorageWrong()
	{
		$storageManager = new StorageManager($this->dba, $this->config, $this->logger, $this->l10n);
		$storage = $storageManager->getByName(Storage\SystemResource::getName());
		$storageManager->move($storage);
	}
}
