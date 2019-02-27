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
class Introspection implements ProcessorInterface
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
	public function __construct($level = Logger::DEBUG, $skipClassesPartials = array(), $skipStackFramesCount = 0)
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
		// we should have the call source now
		$record['extra'] = array_merge(
			$record['extra'],
			$this->getRecord()
		);

		return $record;
	}

	/**
	 * Returns the introspection record of the current call
	 *
	 * @return array
	 */
	public function getRecord()
	{
		$trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);

		$i = 1;

		while ($this->isTraceClassOrSkippedFunction($trace, $i)) {
			$i++;
		}

		$i += $this->skipStackFramesCount;

		return [
			'file' => isset($trace[$i - 1]['file']) ? basename($trace[$i - 1]['file']) : null,
			'line' => isset($trace[$i - 1]['line']) ? $trace[$i - 1]['line'] : null,
			'function' => isset($trace[$i]['function']) ? $trace[$i]['function'] : null,
		];
	}

	/**
	 * Checks if the current trace class or function has to be skipped
	 *
	 * @param array $trace The current trace array
	 * @param int   $index The index of the current hierarchy level
	 * @return bool True if the class or function should get skipped, otherwise false
	 */
	private function isTraceClassOrSkippedFunction(array $trace, $index)
	{
		if (!isset($trace[$index])) {
			return false;
		}

		if (isset($trace[$index]['class'])) {
			foreach ($this->skipClassesPartials as $part) {
				if (strpos($trace[$index]['class'], $part) !== false) {
					return true;
				}
			}
		} elseif (in_array($trace[$index]['function'], $this->skipFunctions)) {
			return true;
		}

		return false;
	}
}
