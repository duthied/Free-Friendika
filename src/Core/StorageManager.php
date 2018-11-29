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
	private static $default_storages = [
		'Filesystem' => \Friendica\Model\Storage\Filesystem::class,
		'Database' => \Friendica\Model\Storage\Database::class,
	];

	private static $storages = [];

	private static function setup()
	{
		if (count(self::$storages)==0) {
			self::$storage = Config::get('storage', 'backends', self::$default_storages);
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
		return defaults(self::$storages, $name, '');
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
		self::$storages[$name] = $class;
		Config::set('storage', 'backends', self::$storages);
	}


	/**
	 * @brief Unregister a storage backend class
	 *
	 * @param string  $name   User readable backend name
	 */
	public static function unregister($class)
	{
		self::setup();
		unset(self::$storages[$name]);
		Config::set('storage', 'backends', self::$storages);
	}
}