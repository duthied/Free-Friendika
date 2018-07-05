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
class RedisCacheDriver extends BaseObject implements IMemoryCacheDriver
{
	/**
	 * @var \Redis
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

	public function set($key, $value, $ttl = Cache::FIVE_MINUTES)
	{
		// We store with the hostname as key to avoid problems with other applications
		if ($ttl > 0) {
			return $this->redis->setex(
				self::getApp()->get_hostname() . ":" . $key,
				time() + $ttl,
				serialize($value)
			);
		} else {
			return $this->redis->set(
				self::getApp()->get_hostname() . ":" . $key,
				serialize($value)
			);
		}
	}

	public function delete($key)
	{
		return $this->redis->delete($key);
	}

	public function clear()
	{
		return true;
	}


	/**
	 * (@inheritdoc)
	 */
	public function add($key, $value, $ttl = Cache::FIVE_MINUTES)
	{
		if (!is_int($value)) {
			$value = serialize($value);
		}

		return $this->redis->setnx(self::getApp()->get_hostname() . ":" . $key, $value);
	}

	/**
	 * (@inheritdoc)
	 */
	public function compareSet($key, $oldValue, $newValue, $ttl = Cache::FIVE_MINUTES)
	{
		if (!is_int($newValue)) {
			$newValue = serialize($newValue);
		}

		$this->redis->watch(self::getApp()->get_hostname() . ":" . $key);
		// If the old value isn't what we expected, somebody else changed the key meanwhile
		if ($this->get($key) === $oldValue) {
			if ($ttl > 0) {
				$result = $this->redis->multi()
					->setex(self::getApp()->get_hostname() . ":" . $ttl, $key, $newValue)
					->exec();
			} else {
				$result = $this->redis->multi()
					->set(self::getApp()->get_hostname() . ":" . $key, $newValue)
					->exec();
			}
			return $result !== false;
		}
		$this->redis->unwatch();
		return false;
	}
	/**
	 * (@inheritdoc)
	 */
	public function compareDelete($key, $value)
	{
		$this->redis->watch(self::getApp()->get_hostname() . ":" . $key);
		// If the old value isn't what we expected, somebody else changed the key meanwhile
		if ($this->get($key) === $value) {
			$result = $this->redis->multi()
				->del(self::getApp()->get_hostname() . ":" . $key)
				->exec();
			return $result !== false;
		}
		$this->redis->unwatch();
		return false;
	}
}
