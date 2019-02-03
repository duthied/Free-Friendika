<?php
/**
 * @file src/BaseObject.php
 */
namespace Friendica;

require_once 'boot.php';

use Friendica\Core\Config;
use Friendica\Factory;
use Friendica\Util\BasePath;

/**
 * Basic object
 *
 * Contains what is useful to any object
 */
class BaseObject
{
	private static $app = null;

	/**
	 * Get the app
	 *
	 * Same as get_app from boot.php
	 *
	 * @return App
	 * @throws \Exception
	 */
	public static function getApp()
	{
		if (empty(self::$app)) {
			$basedir = BasePath::create(dirname(__DIR__));
			$configLoader = new Config\ConfigCacheLoader($basedir);
			$config = Factory\ConfigFactory::createCache($configLoader);
			$logger = Factory\LoggerFactory::create('app', $config);
			self::$app = new App($config, $logger);
		}

		return self::$app;
	}

	/**
	 * Set the app
	 *
	 * @param App $app App
	 *
	 * @return void
	 */
	public static function setApp(App $app)
	{
		self::$app = $app;
	}
}
