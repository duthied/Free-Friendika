<?php

namespace Friendica\Factory;

use Friendica\Core\Config\ConfigCache;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

class ProfilerFactory
{
	/**
	 * Creates a Profiler for the current execution
	 *
	 * @param LoggerInterface $logger      The logger for saving the profiling data
	 * @param ConfigCache     $configCache The configuration cache
	 *
	 * @return Profiler
	 */
	public static function create(LoggerInterface $logger, ConfigCache $configCache)
	{
		$enabled = $configCache->get('system', 'profiler', false);
		$renderTime = $configCache->get('rendertime', 'callstack', false);
		return new Profiler($logger, $enabled, $renderTime);
	}
}
