<?php

namespace Friendica\Factory;

use Friendica\Core\Config\Cache\ConfigCache;
use Friendica\Util\Profiler;

class ProfilerFactory
{
	/**
	 * Creates a Profiler for the current execution
	 *
	 * @param ConfigCache     $configCache The configuration cache
	 *
	 * @return Profiler
	 */
	public static function create(ConfigCache $configCache)
	{
		$enabled = $configCache->get('system', 'profiler');
		$enabled = isset($enabled) && $enabled !== '0';
		$renderTime = $configCache->get('rendertime', 'callstack');
		$renderTime = isset($renderTime) && $renderTime !== '0';

		return new Profiler($enabled, $renderTime);
	}
}
