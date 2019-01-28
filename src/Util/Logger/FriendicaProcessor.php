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

	private $skipClassesPartials;

	private $skipFunctions = [
		'call_user_func',
		'call_user_func_array',
	];

	/**
	 * @param string|int $level The minimum logging level at which this Processor will be triggered
	 * @param array $skipClassesPartials An array of classes to skip during logging
	 * @param int $skipStackFramesCount If the logger should use information from other hierarchy levels of the call
	 */
	public function __construct($level = Logger::DEBUG, array $skipClassesPartials = array(), $skipStackFramesCount = 0)
	{
		$this->level = Logger::toMonologLevel($level);
		$this->skipClassesPartials = array_merge(array('Monolog\\'), $skipClassesPartials);
		$this->skipStackFramesCount = $skipStackFramesCount;
	}

	public function __invoke(array $record)
	{
		// return if the level is not high enough
		if ($record['level'] < $this->level) {
			return $record;
		}

		$trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);

		$i = 1;

		while ($this->isTraceClassOrSkippedFunction($trace, $i)) {
			if (isset($trace[$i]['class'])) {
				foreach ($this->skipClassesPartials as $part) {
					if (strpos($trace[$i]['class'], $part) !== false) {
						$i++;
						continue 2;
					}
				}
			} elseif (in_array($trace[$i]['function'], $this->skipFunctions)) {
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
				'file'      => isset($trace[$i - 1]['file']) ? basename($trace[$i - 1]['file']) : null,
				'line'      => isset($trace[$i - 1]['line']) ? $trace[$i - 1]['line'] : null,
				'function'  => isset($trace[$i]['function']) ? $trace[$i]['function'] : null,
			]
		);

		return $record;
	}

	private function isTraceClassOrSkippedFunction(array $trace, $index)
	{
		if (!isset($trace[$index])) {
			return false;
		}

		return isset($trace[$index]['class']) || in_array($trace[$index]['function'], $this->skipFunctions);
	}
}
