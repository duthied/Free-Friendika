<?php

namespace Friendica\Core\Cache;

use Exception;

/**
 * APCu Cache.
 *
 * @author Philipp Holzer <admin@philipp.info>
 */
class APCuCache extends Cache implements IMemoryCache
{
	use TraitCompareSet;
	use TraitCompareDelete;

	/**
	 * @throws Exception
	 */
	public function __construct(string $hostname)
	{
		if (!self::isAvailable()) {
			throw new Exception('APCu is not available.');
		}

		parent::__construct($hostname);
	}

	/**
	 * (@inheritdoc)
	 */
	public function getAllKeys($prefix = null)
	{
		$ns = $this->getCacheKey($prefix);
		$ns = preg_quote($ns, '/');

		if (class_exists('\APCIterator')) {
			$iterator = new \APCIterator('user', '/^' . $ns. '/', APC_ITER_KEY);
		} else {
			$iterator = new \APCUIterator('/^' . $ns . '/', APC_ITER_KEY);
		}

		$keys = [];
		foreach ($iterator as $item) {
			array_push($keys, $item['key']);
		}

		return $this->getOriginalKeys($keys);
	}

	/**
	 * (@inheritdoc)
	 */
	public function get($key)
	{
		$return = null;
		$cachekey = $this->getCacheKey($key);

		$cached = apcu_fetch($cachekey, $success);
		if (!$success) {
			return null;
		}

		$value = unserialize($cached);

		// Only return a value if the serialized value is valid.
		// We also check if the db entry is a serialized
		// boolean 'false' value (which we want to return).
		if ($cached === serialize(false) || $value !== false) {
			$return = $value;
		}

		return $return;
	}

	/**
	 * (@inheritdoc)
	 */
	public function set($key, $value, $ttl = Cache::FIVE_MINUTES)
	{
		$cachekey = $this->getCacheKey($key);

		$cached = serialize($value);

		if ($ttl > 0) {
			return apcu_store(
				$cachekey,
				$cached,
				$ttl
			);
		} else {
			return apcu_store(
				$cachekey,
				$cached
			);
		}
	}

	/**
	 * (@inheritdoc)
	 */
	public function delete($key)
	{
		$cachekey = $this->getCacheKey($key);
		return apcu_delete($cachekey);
	}

	/**
	 * (@inheritdoc)
	 */
	public function clear($outdated = true)
	{
		if ($outdated) {
			return true;
		} else {
			$prefix = $this->getPrefix();
			$prefix = preg_quote($prefix, '/');

			if (class_exists('\APCIterator')) {
				$iterator = new \APCIterator('user', '/^' . $prefix . '/', APC_ITER_KEY);
			} else {
				$iterator = new \APCUIterator('/^' . $prefix . '/', APC_ITER_KEY);
			}

			return apcu_delete($iterator);
		}
	}

	/**
	 * (@inheritdoc)
	 */
	public function add($key, $value, $ttl = Cache::FIVE_MINUTES)
	{
		$cachekey = $this->getCacheKey($key);
		$cached = serialize($value);

		return apcu_add($cachekey, $cached);
	}

	public static function isAvailable()
	{
		if (!extension_loaded('apcu')) {
			return false;
		} elseif (!ini_get('apc.enabled') && !ini_get('apc.enable_cli')) {
			return false;
		} elseif (
			version_compare(phpversion('apc') ?: '0.0.0', '4.0.6') === -1 &&
			version_compare(phpversion('apcu') ?: '0.0.0', '5.1.0') === -1
		) {
			return false;
		}

		return true;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getName()
	{
		return self::TYPE_APCU;
	}
}
