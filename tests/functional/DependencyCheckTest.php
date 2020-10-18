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

namespace Friendica\Test\functional;

use Dice\Dice;
use Friendica\App;
use Friendica\Core\Cache\ICache;
use Friendica\Core\Cache\IMemoryCache;
use Friendica\Core\Config\Cache;
use Friendica\Core\Config\IConfig;
use Friendica\Core\Lock\ILock;
use Friendica\Database\Database;
use Friendica\Test\Util\VFSTrait;
use Friendica\Util\BasePath;
use Friendica\Util\ConfigFileLoader;
use Friendica\Util\Profiler;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class DependencyCheckTest extends TestCase
{
	use VFSTrait;

	/**
	 * @var Dice
	 */
	private $dice;

	protected function setUp()
	{
		parent::setUp();

		$this->setUpVfsDir();

		$this->dice = (new Dice())
			->addRules(include __DIR__ . '/../../static/dependencies.config.php');
	}

	/**
	 * Test the creation of the BasePath
	 */
	public function testBasePath()
	{
		/** @var BasePath $basePath */
		$basePath = $this->dice->create(BasePath::class, [$this->root->url()]);

		self::assertInstanceOf(BasePath::class, $basePath);
		self::assertEquals($this->root->url(), $basePath->getPath());
	}

	/**
	 * Test the initial config cache
	 * Should not need any other files
	 */
	public function testConfigFileLoader()
	{
		/** @var ConfigFileLoader $configFileLoader */
		$configFileLoader = $this->dice->create(ConfigFileLoader::class);

		self::assertInstanceOf(ConfigFileLoader::class, $configFileLoader);

		$configCache = new Cache();
		$configFileLoader->setupCache($configCache);

		self::assertNotEmpty($configCache->getAll());
		self::assertArrayHasKey('database', $configCache->getAll());
		self::assertArrayHasKey('system', $configCache->getAll());
	}

	/**
	 * Test the construction of a profiler class with DI
	 */
	public function testProfiler()
	{
		/** @var Profiler $profiler */
		$profiler = $this->dice->create(Profiler::class);

		self::assertInstanceOf(Profiler::class, $profiler);

		$configCache = new Cache([
			'system' => [
				'profiler' => true,
			],
			'rendertime' => [
				'callstack' => true,
			]
		]);

		// create new DI-library because of shared instance rule (so the Profiler wouldn't get created twice)
		$this->dice = new Dice();
		$profiler = $this->dice->create(Profiler::class, [$configCache]);

		self::assertInstanceOf(Profiler::class, $profiler);
		self::assertTrue($profiler->isRendertime());
	}

	public function testDatabase()
	{
		/** @var Database $database */
		$database = $this->dice->create(Database::class);

		self::assertInstanceOf(Database::class, $database);
		self::assertTrue($database->connected());
	}

	public function testAppMode()
	{
		/** @var App\Mode $mode */
		$mode = $this->dice->create(App\Mode::class);

		self::assertInstanceOf(App\Mode::class, $mode);

		self::assertTrue($mode->isNormal());
	}

	public function testConfiguration()
	{
		/** @var IConfig $config */
		$config = $this->dice->create(IConfig::class);

		self::assertInstanceOf(IConfig::class, $config);

		self::assertNotEmpty($config->get('database', 'username'));
	}

	public function testLogger()
	{
		/** @var LoggerInterface $logger */
		$logger = $this->dice->create(LoggerInterface::class, ['test']);

		self::assertInstanceOf(LoggerInterface::class, $logger);
	}

	public function testDevLogger()
	{
		/** @var IConfig $config */
		$config = $this->dice->create(IConfig::class);
		$config->set('system', 'dlogfile', $this->root->url() . '/friendica.log');

		/** @var LoggerInterface $logger */
		$logger = $this->dice->create('$devLogger', ['dev']);

		self::assertInstanceOf(LoggerInterface::class, $logger);
	}

	public function testCache()
	{
		/** @var ICache $cache */
		$cache = $this->dice->create(ICache::class);

		self::assertInstanceOf(ICache::class, $cache);
	}

	public function testMemoryCache()
	{
		/** @var IMemoryCache $cache */
		$cache = $this->dice->create(IMemoryCache::class);

		// We need to check "just" ICache, because the default Cache is DB-Cache, which isn't a memorycache
		self::assertInstanceOf(ICache::class, $cache);
	}

	public function testLock()
	{
		/** @var ILock $cache */
		$lock = $this->dice->create(ILock::class);

		self::assertInstanceOf(ILock::class, $lock);
	}
}
