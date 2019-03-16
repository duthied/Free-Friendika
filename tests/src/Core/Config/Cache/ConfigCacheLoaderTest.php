<?php

namespace Friendica\Test\src\Core\Config\Cache;

use Friendica\Core\Config\Cache\ConfigCache;
use Friendica\Core\Config\Cache\ConfigCacheLoader;
use Friendica\Test\MockedTest;
use Friendica\Test\Util\VFSTrait;
use org\bovigo\vfs\vfsStream;

class ConfigCacheLoaderTest extends MockedTest
{
	use VFSTrait;

	protected function setUp()
	{
		parent::setUp();

		$this->setUpVfsDir();
	}

	/**
	 * Test the loadConfigFiles() method with a wrong local.config.php
	 * @expectedException \Exception
	 * @expectedExceptionMessageRegExp /Error loading config file \w+/
	 */
	public function testLoadConfigWrong()
	{
		$this->delConfigFile('local.config.php');

		vfsStream::newFile('local.config.php')
			->at($this->root->getChild('config'))
			->setContent('<?php return true;');

		$configCacheLoader = new ConfigCacheLoader($this->root->url());
		$configCache = new ConfigCache();

		$configCacheLoader->loadConfigFiles($configCache);
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
			'..' . DIRECTORY_SEPARATOR .
			'datasets' . DIRECTORY_SEPARATOR .
			'config' . DIRECTORY_SEPARATOR .
			'local.config.php';

		vfsStream::newFile('local.config.php')
			->at($this->root->getChild('config'))
			->setContent(file_get_contents($file));

		$configCacheLoader = new ConfigCacheLoader($this->root->url());
		$configCache = new ConfigCache();

		$configCacheLoader->loadConfigFiles($configCache);

		$this->assertEquals('testhost', $configCache->get('database', 'hostname'));
		$this->assertEquals('testuser', $configCache->get('database', 'username'));
		$this->assertEquals('testpw', $configCache->get('database', 'password'));
		$this->assertEquals('testdb', $configCache->get('database', 'database'));

		$this->assertEquals('admin@test.it', $configCache->get('config', 'admin_email'));
		$this->assertEquals('Friendica Social Network', $configCache->get('config', 'sitename'));
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
			'..' . DIRECTORY_SEPARATOR .
			'datasets' . DIRECTORY_SEPARATOR .
			'config' . DIRECTORY_SEPARATOR .
			'local.ini.php';

		vfsStream::newFile('local.ini.php')
			->at($this->root->getChild('config'))
			->setContent(file_get_contents($file));

		$configCacheLoader = new ConfigCacheLoader($this->root->url());
		$configCache = new ConfigCache();

		$configCacheLoader->loadConfigFiles($configCache);

		$this->assertEquals('testhost', $configCache->get('database', 'hostname'));
		$this->assertEquals('testuser', $configCache->get('database', 'username'));
		$this->assertEquals('testpw', $configCache->get('database', 'password'));
		$this->assertEquals('testdb', $configCache->get('database', 'database'));

		$this->assertEquals('admin@test.it', $configCache->get('config', 'admin_email'));
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
			'..' . DIRECTORY_SEPARATOR .
			'datasets' . DIRECTORY_SEPARATOR .
			'config' . DIRECTORY_SEPARATOR .
			'.htconfig.test.php';

		vfsStream::newFile('.htconfig.php')
			->at($this->root)
			->setContent(file_get_contents($file));

		$configCacheLoader = new ConfigCacheLoader($this->root->url());
		$configCache = new ConfigCache();

		$configCacheLoader->loadConfigFiles($configCache);

		$this->assertEquals('testhost', $configCache->get('database', 'hostname'));
		$this->assertEquals('testuser', $configCache->get('database', 'username'));
		$this->assertEquals('testpw', $configCache->get('database', 'password'));
		$this->assertEquals('testdb', $configCache->get('database', 'database'));

		$this->assertEquals('/var/run/friendica.pid', $configCache->get('system', 'pidfile'));
		$this->assertEquals('Europe/Berlin', $configCache->get('system', 'default_timezone'));
		$this->assertEquals('fr', $configCache->get('system', 'language'));

		$this->assertEquals('admin@friendica.local', $configCache->get('config', 'admin_email'));
		$this->assertEquals('Friendly admin', $configCache->get('config', 'admin_nickname'));

		$this->assertEquals('/another/php', $configCache->get('config', 'php_path'));
		$this->assertEquals('999', $configCache->get('config', 'max_import_size'));
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
			'..' . DIRECTORY_SEPARATOR .
			'datasets' . DIRECTORY_SEPARATOR .
			'config' . DIRECTORY_SEPARATOR .
			'local.config.php';

		vfsStream::newFile('test.config.php')
			->at($this->root->getChild('addon')->getChild('test')->getChild('config'))
			->setContent(file_get_contents($file));

		$configCacheLoader = new ConfigCacheLoader($this->root->url());

		$conf = $configCacheLoader->loadAddonConfig('test');

		$this->assertEquals('testhost', $conf['database']['hostname']);
		$this->assertEquals('testuser', $conf['database']['username']);
		$this->assertEquals('testpw', $conf['database']['password']);
		$this->assertEquals('testdb', $conf['database']['database']);

		$this->assertEquals('admin@test.it', $conf['config']['admin_email']);
	}
}
