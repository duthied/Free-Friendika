<?php

namespace Friendica\Core\Cache;

use Friendica\Core\Cache;

/**
 * Redis Cache Driver. This driver is based on Memcache driver
 *
 * @author Hypolite Petovan <mrpetovan@gmail.com>
 * @author Roland Haeder <roland@mxchange.org>
 */
class RedisCacheDriver extends AbstractCacheDriver
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
		$cachekey = $this->getCacheKey($key);

		// We fetch with the hostname as key to avoid problems with other applications
		$cached = $this->redis->get($cachekey);

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
		$cachekey = $this->getCacheKey($key);

		// We store with the hostname as key to avoid problems with other applications
		if ($ttl > 0) {
			return $this->redis->setex(
				$cachekey,
				time() + $ttl,
				serialize($value)
			);
		} else {
			return $this->redis->set(
				$cachekey,
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
		$cachekey = $this->getCacheKey($key);

		if (!is_int($value)) {
			$value = serialize($value);
		}

		return $this->redis->setnx($cachekey, $value);
	}

	/**
	 * (@inheritdoc)
	 */
	public function compareSet($key, $oldValue, $newValue, $ttl = Cache::FIVE_MINUTES)
	{
		$cachekey = $this->getCacheKey($key);

		if (!is_int($newValue)) {
			$newValue = serialize($newValue);
		}

		$this->redis->watch($cachekey);
		// If the old value isn't what we expected, somebody else changed the key meanwhile
		if ($this->get($cachekey) === $oldValue) {
			if ($ttl > 0) {
				$result = $this->redis->multi()
					->setex($cachekey, $ttl, $newValue)
					->exec();
			} else {
				$result = $this->redis->multi()
					->set($cachekey, $newValue)
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
		$cachekey = $this->getCacheKey($key);

		$this->redis->watch($cachekey);
		// If the old value isn't what we expected, somebody else changed the key meanwhile
		if ($this->get($key) === $value) {
			$result = $this->redis->multi()
				->del($cachekey)
				->exec();
			return $result !== false;
		}
		$this->redis->unwatch();
		return false;
	}
}
