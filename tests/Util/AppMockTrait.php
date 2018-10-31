<?php

namespace Friendica\Test\Util;

use Friendica\App;
use Friendica\BaseObject;
use Friendica\Render\FriendicaSmartyEngine;
use org\bovigo\vfs\vfsStreamDirectory;

/**
 * Trait to Mock the global App instance
 */
trait AppMockTrait
{
	use ConfigMockTrait;
	use DBAMockTrait;

	/**
	 * @var App The Friendica global App Mock
	 */
	protected $app;

	/**
	 * Mock the App
	 *
	 * @param vfsStreamDirectory $root The root directory
	 */
	public function mockApp($root)
	{
		/// @todo This mock is ugly. We return an empty string for each translation - no workaround yet
		$l10nMock = \Mockery::mock('alias:Friendica\Core\L10n');
		$l10nMock->shouldReceive('t')
			->andReturn('');

		$this->mockConfigGet('system', 'theme', 'testtheme');

		// Mocking App and most used functions
		$this->app = \Mockery::mock('Friendica\App');
		$this->app
			->shouldReceive('getBasePath')
			->andReturn($root->url());

		$this->app
			->shouldReceive('getConfigValue')
			->with('database', 'hostname')
			->andReturn(getenv('MYSQL_HOST'));
		$this->app
			->shouldReceive('getConfigValue')
			->with('database', 'username')
			->andReturn(getenv('MYSQL_USERNAME'));
		$this->app
			->shouldReceive('getConfigValue')
			->with('database', 'password')
			->andReturn(getenv('MYSQL_PASSWORD'));
		$this->app
			->shouldReceive('getConfigValue')
			->with('database', 'database')
			->andReturn(getenv('MYSQL_DATABASE'));
		$this->app
			->shouldReceive('getTemplateEngine')
			->andReturn(new FriendicaSmartyEngine());
		$this->app
			->shouldReceive('getCurrentTheme')
			->andReturn('Smarty3');
		$this->app
			->shouldReceive('getTemplateLeftDelimiter')
			->with('smarty3')
			->andReturn('{{');
		$this->app
			->shouldReceive('getTemplateRightDelimiter')
			->with('smarty3')
			->andReturn('}}');
		$this->app
			->shouldReceive('saveTimestamp')
			->andReturn(true);
		$this->app
			->shouldReceive('getBaseUrl')
			->andReturn('http://friendica.local');

		// Mocking the Theme
		// Necessary for macro engine with template files
		$themeMock = \Mockery::mock('alias:Friendica\Core\Theme');
		$themeMock
			->shouldReceive('install')
			->with('testtheme')
			->andReturn(true);

		BaseObject::setApp($this->app);
	}
}
