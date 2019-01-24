<?php

namespace Friendica\Util\Logger;

use Monolog\Logger;
use Monolog\Processor\ProcessorInterface;

/**
 * Injects line/file//function where the log message came from
 *
 * Based on the class IntrospectionProcessor without the "class" information
 * @see IntrospectionProcessor
 */
class FriendicaProcessor implements ProcessorInterface
{
	private $level;

	private $skipStackFramesCount;

	private $skipFunctions = [
		'call_user_func',
		'call_user_func_array',
	];

	/**
	 * @param string|int $level The minimum logging level at which this Processor will be triggered
	 * @param int $skipStackFramesCount If the logger should use information from other hierarchy levels of the call
	 */
	public function __construct($level = Logger::DEBUG, $skipStackFramesCount = 0)
	{
		$this->level = Logger::toMonologLevel($level);
		$this->skipStackFramesCount = $skipStackFramesCount;
	}

	public function __invoke(array $record)
	{
		// return if the level is not high enough
		if ($record['level'] < $this->level) {
			return $record;
		}

		$trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);

		// skip first since it's always the current method
		array_shift($trace);
		// the call_user_func call is also skipped
		array_shift($trace);

		$i = 0;

		while ($this->isSkippedFunction($trace, $i)) {
			if (in_array($trace[$i]['function'], $this->skipFunctions)) {
				$i++;

				continue;
			}

			break;
		}

		$i += $this->skipStackFramesCount;

		// we should have the call source now
		$record['extra'] = array_merge(
			$record['extra'],
			[
				'file'      => isset($trace[$i - 1]['file']) ? $trace[$i - 1]['file'] : null,
				'line'      => isset($trace[$i - 1]['line']) ? $trace[$i - 1]['line'] : null,
				'function'  => isset($trace[$i]['function']) ? $trace[$i]['function'] : null,
			]
		);

		return $record;
	}

	private function isSkippedFunction(array $trace, $index)
	{
		if (!isset($trace[$index])) {
			return false;
		}

		return in_array($trace[$index]['function'], $this->skipFunctions);
	}
}
