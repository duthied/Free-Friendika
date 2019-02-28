<?php

namespace Friendica\Util\Logger;

use Friendica\Util\Introspection;
use Friendica\Util\Profiler;

/**
 * A Logger instance for logging into a stream (file, stdout, stderr)
 */
class StreamLogger extends AbstractFriendicaLogger
{
	/**
	 * The minimum loglevel at which this logger will be triggered
	 * @var string
	 */
	private $logLevel;

	public function __construct($channel, Introspection $introspection, Profiler $profiler, $level)
	{
		parent::__construct($channel, $introspection, $profiler);
		$this->logLevel = $level;
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
