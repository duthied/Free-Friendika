<?php

namespace Friendica\Util\Logger;

use Friendica\Util\Introspection;
use Friendica\Util\Profiler;

/**
 * A Logger instance for logging into a stream
 */
class StreamLogger extends AbstractFriendicaLogger
{
	public function __construct($channel, Introspection $introspection, Profiler $profiler)
	{
		parent::__construct($channel, $introspection, $profiler);
	}

	/**
	 * Adds a new entry to the log
	 *
	 * @param int $level
	 * @param string $message
	 * @param array $context
	 *
	 * @return void
	 */
	protected function addEntry($level, $message, $context = [])
	{
		// TODO: Implement addEntry() method.
	}
}
