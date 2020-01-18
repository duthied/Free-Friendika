<?php

namespace Friendica\Core\Lock;

use Friendica\Core\Cache\Type as CacheType;

/**
 * Enumeration for lock types
 *
 * There's no "Cache" lock type, because the type depends on the concrete, used cache
 */
abstract class Type
{
	const DATABASE  = CacheType::DATABASE;
	const SEMAPHORE = 'semaphore';
}
