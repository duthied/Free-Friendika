<?php

namespace Friendica\Core;

use Friendica\Core\Config;



/**
 * @brief Manage storage backends
 *
 * Core code uses this class to get and set current storage backend class.
 * Addons use this class to register and unregister additional backends.
 */
class StorageManager
{
	private static $default_backends = [
		'Filesystem' => \Friendica\Model\Storage\Filesystem::class,
		'Database' => \Friendica\Model\Storage\Database::class,
	];

	private static $backends = [];

	private static function setup()
	{
		if (count(self::$backends)==0) {
			self::$backends = Config::get('storage', 'backends', self::$default_backends);
		}
	}

	/**
	 * @brief Return current storage backend class
	 * @return string
	 */
	public static function getBackend()
	{
		return Config::get('storage', 'class', '');
	}

	/**
	 * @brief Return storage backend class by registered name
	 *
	 * @param string  $name  Backend name
	 * @return string Empty if no backend registered at $name exists
	 */
	public static function getByName($name)
	{
		self::setup();
		return defaults(self::$backends, $name, '');
	}

	/**
	 * @brief Set current storage backend class
	 *
	 * @param string  $class  Backend class name
	 */
	public static function setBackend($class)
	{
		/// @todo Check that $class implements IStorage
		Config::set('storage', 'class', $class);
	}

	/**
	 * @brief Get registered backends
	 *
	 * @return array
	 */
	public static function listBackends()
	{
		self::setup();
		return self::$backends;
	}



	/**
	 * @brief Register a storage backend class
	 *
	 * @param string  $name   User readable backend name
	 * @param string  $class  Backend class name
	 */
	public static function register($name, $class)
	{
		/// @todo Check that $class implements IStorage
		self::setup();
		self::$backends[$name] = $class;
		Config::set('storage', 'backends', self::$backends);
	}


	/**
	 * @brief Unregister a storage backend class
	 *
	 * @param string  $name   User readable backend name
	 */
	public static function unregister($class)
	{
		self::setup();
		unset(self::$backends[$name]);
		Config::set('storage', 'backends', self::$backends);
	}
}