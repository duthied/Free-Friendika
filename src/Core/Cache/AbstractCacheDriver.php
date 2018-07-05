<?php

namespace Friendica\Core\Cache;
use Friendica\BaseObject;


/**
 * Abstract class for common used functions
 *
 * Class AbstractCacheDriver
 *
 * @package Friendica\Core\Cache
 */
abstract class AbstractCacheDriver extends BaseObject
{
	/**
	 * @param string $key	The original key
	 * @return string		The cache key used for the cache
	 */
	protected function getCacheKey($key) {
		return self::getApp()->get_hostname() . ":" . $key;
	}
}
