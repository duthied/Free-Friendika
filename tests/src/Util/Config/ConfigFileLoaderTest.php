<?php
/**
 * @copyright Copyright (C) 2010-2021, the Friendica project
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

namespace Friendica\Test\src\Util\Config;

use Friendica\Core\Config\Cache;
use Friendica\Test\MockedTest;
use Friendica\Test\Util\VFSTrait;
use Friendica\Util\ConfigFileLoader;
use org\bovigo\vfs\vfsStream;

class ConfigFileLoaderTest extends MockedTest
{
	use VFSTrait;

	protected function setUp(): void
	{
		parent::setUp();

		$this->setUpVfsDir();
	}

	/**
	 * Test the loadConfigFiles() method with default values
	 */
	public function testLoadConfigFiles()
	{
		$this->delConfigFile('local.config.php');

		$configFileLoader = new ConfigFileLoader($this->root->url());
		$configCache = new Cache();

		$configFileLoader->setupCache($configCache);

		self::assertEquals($this->root->url(), $configCache->get('system', 'basepath'));
	}

	/**
	 * Test the loadConfigFiles() method with a wrong local.config.php
	 *
	 */
	public function testLoadConfigWrong()
	{
		$this->expectExceptionMessageRegExp("/Error loading config file \w+/");
		$this->expectException(\Exception::class);
		$this->delConfigFile('local.config.php');

		vfsStream::newFile('local.config.php')
			->at($this->root->getChild('config'))
			->setContent('<?php return true;');

		$configFileLoader = new ConfigFileLoader($this->root->url());
		$configCache = new Cache();

		$configFileLoader->setupCache($configCache);
	}

	/**
	 * Test the loadConfigFiles() method with a local.config.php file
	 */
	public function testLoadConfigFilesLocal()
	{
		$this->delConfigFile('local.config.php');

		$file = dirname(__DIR__) . DIRECTORY_SEPARATOR .
			'..' . DIRECTORY_SEPARATOR .
			'..' . DIRECTORY_SEPARATOR .
			'datasets' . DIRECTORY_SEPARATOR .
			'config' . DIRECTORY_SEPARATOR .
			'A.config.php';

		vfsStream::newFile('local.config.php')
			->at($this->root->getChild('config'))
			->setContent(file_get_contents($file));

		$configFileLoader = new ConfigFileLoader($this->root->url());
		$configCache = new Cache();

		$configFileLoader->setupCache($configCache);

		self::assertEquals('testhost', $configCache->get('database', 'hostname'));
		self::assertEquals('testuser', $configCache->get('database', 'username'));
		self::assertEquals('testpw', $configCache->get('database', 'password'));
		self::assertEquals('testdb', $configCache->get('database', 'database'));

		self::assertEquals('admin@test.it', $configCache->get('config', 'admin_email'));
		self::assertEquals('Friendica Social Network', $configCache->get('config', 'sitename'));
	}

	/**
	 * Test the loadConfigFile() method with a local.ini.php file
	 */
	public function testLoadConfigFilesINI()
	{
		$this->delConfigFile('local.config.php');

		$file = dirname(__DIR__) . DIRECTORY_SEPARATOR .
			'..' . DIRECTORY_SEPARATOR .
			'..' . DIRECTORY_SEPARATOR .
			'datasets' . DIRECTORY_SEPARATOR .
			'config' . DIRECTORY_SEPARATOR .
			'A.ini.php';

		vfsStream::newFile('local.ini.php')
			->at($this->root->getChild('config'))
			->setContent(file_get_contents($file));

		$configFileLoader = new ConfigFileLoader($this->root->url());
		$configCache = new Cache();

		$configFileLoader->setupCache($configCache);

		self::assertEquals('testhost', $configCache->get('database', 'hostname'));
		self::assertEquals('testuser', $configCache->get('database', 'username'));
		self::assertEquals('testpw', $configCache->get('database', 'password'));
		self::assertEquals('testdb', $configCache->get('database', 'database'));

		self::assertEquals('admin@test.it', $configCache->get('config', 'admin_email'));
	}

	/**
	 * Test the loadConfigFile() method with a .htconfig.php file
	 */
	public function testLoadConfigFilesHtconfig()
	{
		$this->delConfigFile('local.config.php');

		$file = dirname(__DIR__) . DIRECTORY_SEPARATOR .
			'..' . DIRECTORY_SEPARATOR .
			'..' . DIRECTORY_SEPARATOR .
			'datasets' . DIRECTORY_SEPARATOR .
			'config' . DIRECTORY_SEPARATOR .
			'.htconfig.php';

		vfsStream::newFile('.htconfig.php')
			->at($this->root)
			->setContent(file_get_contents($file));

		$configFileLoader = new ConfigFileLoader($this->root->url());
		$configCache = new Cache();

		$configFileLoader->setupCache($configCache);

		self::assertEquals('testhost', $configCache->get('database', 'hostname'));
		self::assertEquals('testuser', $configCache->get('database', 'username'));
		self::assertEquals('testpw', $configCache->get('database', 'password'));
		self::assertEquals('testdb', $configCache->get('database', 'database'));
		self::assertEquals('anotherCharset', $configCache->get('database', 'charset'));

		self::assertEquals('/var/run/friendica.pid', $configCache->get('system', 'pidfile'));
		self::assertEquals('Europe/Berlin', $configCache->get('system', 'default_timezone'));
		self::assertEquals('fr', $configCache->get('system', 'language'));

		self::assertEquals('admin@test.it', $configCache->get('config', 'admin_email'));
		self::assertEquals('Friendly admin', $configCache->get('config', 'admin_nickname'));

		self::assertEquals('/another/php', $configCache->get('config', 'php_path'));
		self::assertEquals('999', $configCache->get('config', 'max_import_size'));
		self::assertEquals('666', $configCache->get('system', 'maximagesize'));

		self::assertEquals('frio,quattro,vier,duepuntozero', $configCache->get('system', 'allowed_themes'));
		self::assertEquals('1', $configCache->get('system', 'no_regfullname'));
	}

	public function testLoadAddonConfig()
	{
		$structure = [
			'addon' => [
				'test' => [
					'config' => [],
				],
			],
		];

		vfsStream::create($structure, $this->root);

		$file = dirname(__DIR__) . DIRECTORY_SEPARATOR .
			'..' . DIRECTORY_SEPARATOR .
			'..' . DIRECTORY_SEPARATOR .
			'datasets' . DIRECTORY_SEPARATOR .
			'config' . DIRECTORY_SEPARATOR .
			'A.config.php';

		vfsStream::newFile('test.config.php')
			->at($this->root->getChild('addon')->getChild('test')->getChild('config'))
			->setContent(file_get_contents($file));

		$configFileLoader = new ConfigFileLoader($this->root->url());

		$conf = $configFileLoader->loadAddonConfig('test');

		self::assertEquals('testhost', $conf['database']['hostname']);
		self::assertEquals('testuser', $conf['database']['username']);
		self::assertEquals('testpw', $conf['database']['password']);
		self::assertEquals('testdb', $conf['database']['database']);

		self::assertEquals('admin@test.it', $conf['config']['admin_email']);
	}

	/**
	 * test loading multiple config files - the last config should work
	 */
	public function testLoadMultipleConfigs()
	{
		$this->delConfigFile('local.config.php');

		$fileDir = dirname(__DIR__) . DIRECTORY_SEPARATOR .
		        '..' . DIRECTORY_SEPARATOR .
		        '..' . DIRECTORY_SEPARATOR .
		        'datasets' . DIRECTORY_SEPARATOR .
		        'config' . DIRECTORY_SEPARATOR;

		vfsStream::newFile('A.config.php')
		         ->at($this->root->getChild('config'))
		         ->setContent(file_get_contents($fileDir . 'A.config.php'));
		vfsStream::newFile('B.config.php')
				->at($this->root->getChild('config'))
		         ->setContent(file_get_contents($fileDir . 'B.config.php'));

		$configFileLoader = new ConfigFileLoader($this->root->url());
		$configCache = new Cache();

		$configFileLoader->setupCache($configCache);

		self::assertEquals('admin@overwritten.local', $configCache->get('config', 'admin_email'));
		self::assertEquals('newValue', $configCache->get('system', 'newKey'));
	}

	/**
	 * test loading multiple config files - the last config should work (INI-version)
	 */
	public function testLoadMultipleInis()
	{
		$this->delConfigFile('local.config.php');

		$fileDir = dirname(__DIR__) . DIRECTORY_SEPARATOR .
		           '..' . DIRECTORY_SEPARATOR .
		           '..' . DIRECTORY_SEPARATOR .
		           'datasets' . DIRECTORY_SEPARATOR .
		           'config' . DIRECTORY_SEPARATOR;

		vfsStream::newFile('A.ini.php')
		         ->at($this->root->getChild('config'))
		         ->setContent(file_get_contents($fileDir . 'A.ini.php'));
		vfsStream::newFile('B.ini.php')
		         ->at($this->root->getChild('config'))
		         ->setContent(file_get_contents($fileDir . 'B.ini.php'));

		$configFileLoader = new ConfigFileLoader($this->root->url());
		$configCache = new Cache();

		$configFileLoader->setupCache($configCache);

		self::assertEquals('admin@overwritten.local', $configCache->get('config', 'admin_email'));
		self::assertEquals('newValue', $configCache->get('system', 'newKey'));
	}

	/**
	 * Test that sample-files (e.g. local-sample.config.php) is never loaded
	 */
	public function testNotLoadingSamples()
	{
		$this->delConfigFile('local.config.php');

		$fileDir = dirname(__DIR__) . DIRECTORY_SEPARATOR .
		           '..' . DIRECTORY_SEPARATOR .
		           '..' . DIRECTORY_SEPARATOR .
		           'datasets' . DIRECTORY_SEPARATOR .
		           'config' . DIRECTORY_SEPARATOR;

		vfsStream::newFile('A.ini.php')
		         ->at($this->root->getChild('config'))
		         ->setContent(file_get_contents($fileDir . 'A.ini.php'));
		vfsStream::newFile('B-sample.ini.php')
		         ->at($this->root->getChild('config'))
		         ->setContent(file_get_contents($fileDir . 'B.ini.php'));

		$configFileLoader = new ConfigFileLoader($this->root->url());
		$configCache = new Cache();

		$configFileLoader->setupCache($configCache);

		self::assertEquals('admin@test.it', $configCache->get('config', 'admin_email'));
		self::assertEmpty($configCache->get('system', 'NewKey'));
	}
}
