<?php
/**
 * @file src/Core/BaseObject.php
 */
namespace Friendica\Core;

require_once 'boot.php';

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
	 */
	public function get_app()
	{
		if (self::$app) {
			return self::$app;
		}

		self::$app = boot::get_app();

		return self::$app;
	}

	/**
	 * Set the app
	 *
	 * @param object $app App
	 */
	public static function set_app($app)
	{
		self::$app = $app;
	}
}
