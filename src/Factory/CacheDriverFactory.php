<?php

namespace Friendica\Factory;

use Friendica\Core\Cache;
use Friendica\Core\Cache\ICacheDriver;
use Friendica\Core\Config;

/**
 * Class CacheDriverFactory
 *
 * @package Friendica\Core\Cache
 *
 * A basic class to generate a CacheDriver
 */
class CacheDriverFactory
{
	/**
	 * This method creates a CacheDriver for the given cache driver name
	 *
	 * @param string $driver The name of the cache driver
	 * @return ICacheDriver  The instance of the CacheDriver
	 * @throws \Exception    The exception if something went wrong during the CacheDriver creation
	 */
	public static function create($driver) {

		switch ($driver) {
			case 'memcache':
				$memcache_host = Config::get('system', 'memcache_host');
				$memcache_port = Config::get('system', 'memcache_port');

				return new Cache\MemcacheCacheDriver($memcache_host, $memcache_port);
				break;

			case 'memcached':
				$memcached_hosts = Config::get('system', 'memcached_hosts');

				return new Cache\MemcachedCacheDriver($memcached_hosts);
				break;
			case 'redis':
				$redis_host = Config::get('system', 'redis_host');
				$redis_port = Config::get('system', 'redis_port');
				$redis_pw   = Config::get('system', 'redis_password');
				$redis_db   = Config::get('system', 'redis_db', 0);

				return new Cache\RedisCacheDriver($redis_host, $redis_port, $redis_db, $redis_pw);
				break;

			case 'apcu':
				return new Cache\APCuCache();
				break;

			default:
				return new Cache\DatabaseCacheDriver();
		}
	}
}
