<?php

namespace Friendica\Test\Util;

use Friendica\App;
use Friendica\BaseObject;
use Friendica\Core\Config\ConfigCache;
use Friendica\Render\FriendicaSmartyEngine;
use Mockery\MockInterface;
use org\bovigo\vfs\vfsStreamDirectory;

/**
 * Trait to Mock the global App instance
 */
trait AppMockTrait
{
	use ConfigMockTrait;

	/**
	 * @var MockInterface|App The mocked Friendica\App
	 */
	protected $app;

	/**
	 * Mock the App
	 *
	 * @param vfsStreamDirectory $root The root directory
	 * @param MockInterface|ConfigCache $config The config cache
	 */
	public function mockApp($root, $config)
	{
		$this->mockConfigGet('system', 'theme', 'testtheme');

		// Mocking App and most used functions
		$this->app = \Mockery::mock(App::class);
		$this->app
			->shouldReceive('getBasePath')
			->andReturn($root->url());

		$config
			->shouldReceive('get')
			->with('database', 'hostname')
			->andReturn(getenv('MYSQL_HOST'));
		$config
			->shouldReceive('get')
			->with('database', 'username')
			->andReturn(getenv('MYSQL_USERNAME'));
		$config
			->shouldReceive('get')
			->with('database', 'password')
			->andReturn(getenv('MYSQL_PASSWORD'));
		$config
			->shouldReceive('get')
			->with('database', 'database')
			->andReturn(getenv('MYSQL_DATABASE'));
		$this->app
			->shouldReceive('getConfig')
			->andReturn($config);

		$this->app
			->shouldReceive('getTemplateEngine')
			->andReturn(new FriendicaSmartyEngine());
		$this->app
			->shouldReceive('getCurrentTheme')
			->andReturn('Smarty3');
		$this->app
			->shouldReceive('saveTimestamp')
			->andReturn(true);
		$this->app
			->shouldReceive('getBaseUrl')
			->andReturn('http://friendica.local');

		BaseObject::setApp($this->app);
	}
}
