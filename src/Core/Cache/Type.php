<?php

namespace Friendica\Core\Cache;

/**
 * Enumeration for cache types
 */
abstract class Type
{
	const APCU      = 'apcu';
	const REDIS     = 'redis';
	const ARRAY     = 'array';
	const MEMCACHE  = 'memcache';
	const DATABASE  = 'database';
	const MEMCACHED = 'memcached';
}
