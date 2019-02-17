<?php

namespace Friendica\Test\Util;

use Friendica\App;
use Friendica\BaseObject;
use Friendica\Core\Config;
use Friendica\Render\FriendicaSmartyEngine;
use Friendica\Util\Profiler;
use Mockery\MockInterface;
use org\bovigo\vfs\vfsStreamDirectory;

/**
 * Trait to Mock the global App instance
 */
trait AppMockTrait
{
	/**
	 * @var MockInterface|App The mocked Friendica\App
	 */
	protected $app;

	/**
	 * @var MockInterface|Config\Configuration The mocked Config Cache
	 */
	protected $configMock;

	/**
	 * @var MockInterface|Profiler The mocked profiler
	 */
	protected $profilerMock;

	/**
	 * Mock the App
	 *
	 * @param vfsStreamDirectory $root The root directory
	 */
	public function mockApp($root)
	{
		$this->configMock = \Mockery::mock(Config\Cache\IConfigCache::class);
		$configAdapterMock = \Mockery::mock(Config\Adapter\IConfigAdapter::class);
		// Disable the adapter
		$configAdapterMock->shouldReceive('isConnected')->andReturn(false);

		$config = new Config\Configuration($this->configMock, $configAdapterMock);
		// Initialize empty Config
		Config::init($config);

		// Mocking App and most used functions
		$this->app = \Mockery::mock(App::class);
		$this->app
			->shouldReceive('getBasePath')
			->andReturn($root->url());

		$this->configMock
			->shouldReceive('has')
			->andReturn(true);
		$this->configMock
			->shouldReceive('get')
			->with('database', 'hostname')
			->andReturn(getenv('MYSQL_HOST'));
		$this->configMock
			->shouldReceive('get')
			->with('database', 'username')
			->andReturn(getenv('MYSQL_USERNAME'));
		$this->configMock
			->shouldReceive('get')
			->with('database', 'password')
			->andReturn(getenv('MYSQL_PASSWORD'));
		$this->configMock
			->shouldReceive('get')
			->with('database', 'database')
			->andReturn(getenv('MYSQL_DATABASE'));
		$this->configMock
			->shouldReceive('get')
			->with('config', 'hostname')
			->andReturn('localhost');
		$this->configMock
			->shouldReceive('get')
			->with('system', 'theme')
			->andReturn('system_theme');

		$this->profilerMock = \Mockery::mock(Profiler::class);
		$this->profilerMock->shouldReceive('saveTimestamp');

		$this->app
			->shouldReceive('getConfigCache')
			->andReturn($this->configMock);
		$this->app
			->shouldReceive('getTemplateEngine')
			->andReturn(new FriendicaSmartyEngine());
		$this->app
			->shouldReceive('getCurrentTheme')
			->andReturn('Smarty3');
		$this->app
			->shouldReceive('getBaseUrl')
			->andReturn('http://friendica.local');
		$this->app
			->shouldReceive('getProfiler')
			->andReturn($this->profilerMock);

		BaseObject::setApp($this->app);
	}
}
