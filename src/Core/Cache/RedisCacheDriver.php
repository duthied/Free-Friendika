<?php

namespace Friendica\Core\Cache;

use Friendica\BaseObject;
use Friendica\Core\Cache;

/**
 * Redis Cache Driver. This driver is based on Memcache driver
 *
 * @author Hypolite Petovan <mrpetovan@gmail.com>
 * @author Roland Haeder <roland@mxchange.org>
 */
class RedisCacheDriver extends BaseObject implements ICacheDriver
{
	/**
	 * @var Redis
	 */
	private $redis;

	public function __construct($redis_host, $redis_port)
	{
		if (!class_exists('Redis', false)) {
			throw new \Exception('Redis class isn\'t available');
		}

		$this->redis = new \Redis();

		if (!$this->redis->connect($redis_host, $redis_port)) {
			throw new \Exception('Expected Redis server at ' . $redis_host . ':' . $redis_port . ' isn\'t available');
		}
	}

	public function get($key)
	{
		$return = null;

		// We fetch with the hostname as key to avoid problems with other applications
		$cached = $this->redis->get(self::getApp()->get_hostname() . ':' . $key);

		// @see http://php.net/manual/en/redis.get.php#84275
		if (is_bool($cached) || is_double($cached) || is_long($cached)) {
			return $return;
		}

		$value = @unserialize($cached);

		// Only return a value if the serialized value is valid.
		// We also check if the db entry is a serialized
		// boolean 'false' value (which we want to return).
		if ($cached === serialize(false) || $value !== false) {
			$return = $value;
		}

		return $return;
	}

	public function set($key, $value, $duration = Cache::MONTH)
	{
		// We store with the hostname as key to avoid problems with other applications
		return $this->redis->set(
			self::getApp()->get_hostname() . ":" . $key,
			serialize($value),
			time() + $duration
		);
	}

	public function delete($key)
	{
		return $this->redis->delete($key);
	}

	public function clear()
	{
		return true;
	}
}
