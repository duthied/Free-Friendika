<?php

namespace Friendica\Core\Cache;

use Friendica\Core\System;
use Friendica\Util\Profiler;

/**
 * This class wraps cache driver so they can get profiled - in case the profiler is enabled
 *
 * It is using the decorator pattern (@see
 */
class ProfilerCache implements ICache, IMemoryCache
{
	/**
	 * @var ICache The original cache driver
	 */
	private $cache;

	/**
	 * @var Profiler The profiler of Friendica
	 */
	private $profiler;

	public function __construct(ICache $cache, Profiler $profiler)
	{
		$this->cache    = $cache;
		$this->profiler = $profiler;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getAllKeys($prefix = null)
	{
		$time = microtime(true);

		$return = $this->cache->getAllKeys($prefix);

		$this->profiler->saveTimestamp($time, 'cache', System::callstack());

		return $return;
	}

	/**
	 * {@inheritDoc}
	 */
	public function get($key)
	{
		$time = microtime(true);

		$return = $this->cache->get($key);

		$this->profiler->saveTimestamp($time, 'cache', System::callstack());

		return $return;
	}

	/**
	 * {@inheritDoc}
	 */
	public function set($key, $value, $ttl = Cache::FIVE_MINUTES)
	{
		$time = microtime(true);

		$return = $this->cache->set($key, $value, $ttl);

		$this->profiler->saveTimestamp($time, 'cache', System::callstack());

		return $return;
	}

	/**
	 * {@inheritDoc}
	 */
	public function delete($key)
	{
		$time = microtime(true);

		$return = $this->cache->delete($key);

		$this->profiler->saveTimestamp($time, 'cache', System::callstack());

		return $return;
	}

	/**
	 * {@inheritDoc}
	 */
	public function clear($outdated = true)
	{
		$time = microtime(true);

		$return = $this->cache->clear($outdated);

		$this->profiler->saveTimestamp($time, 'cache', System::callstack());

		return $return;
	}

	/**
	 * {@inheritDoc}
	 */
	public function add($key, $value, $ttl = Cache::FIVE_MINUTES)
	{
		if ($this->cache instanceof IMemoryCache) {
			$time = microtime(true);

			$return = $this->cache->add($key, $value, $ttl);

			$this->profiler->saveTimestamp($time, 'cache', System::callstack());

			return $return;
		} else {
			return false;
		}
	}

	/**
	 * {@inheritDoc}
	 */
	public function compareSet($key, $oldValue, $newValue, $ttl = Cache::FIVE_MINUTES)
	{
		if ($this->cache instanceof IMemoryCache) {
			$time = microtime(true);

			$return = $this->cache->compareSet($key, $oldValue, $newValue, $ttl);

			$this->profiler->saveTimestamp($time, 'cache', System::callstack());

			return $return;
		} else {
			return false;
		}
	}

	/**
	 * {@inheritDoc}
	 */
	public function compareDelete($key, $value)
	{
		if ($this->cache instanceof IMemoryCache) {
			$time = microtime(true);

			$return = $this->cache->compareDelete($key, $value);

			$this->profiler->saveTimestamp($time, 'cache', System::callstack());

			return $return;
		} else {
			return false;
		}
	}

	/**
	 * {@inheritDoc}
	 */
	public function GetName()
	{
		return $this->cache->getName() . ' (with profiler)';
	}
}
