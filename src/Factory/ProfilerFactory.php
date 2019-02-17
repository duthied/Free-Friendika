<?php

namespace Friendica\Factory;

use Friendica\Core\Config\Cache\IConfigCache;
use Friendica\Util\Profiler;

class ProfilerFactory
{
	/**
	 * Creates a Profiler for the current execution
	 *
	 * @param IConfigCache     $configCache The configuration cache
	 *
	 * @return Profiler
	 */
	public static function create(IConfigCache $configCache)
	{
		$enabled = $configCache->get('system', 'profiler');
		$enabled = isset($enabled) && $enabled !== '!<unset>!';
		$renderTime = $configCache->get('rendertime', 'callstack');
		$renderTime = isset($renderTime) && $renderTime !== '!<unset>!';

		return new Profiler($enabled, $renderTime);
	}
}
