<?php

namespace Friendica\Util\Logger;

use Psr\Log\LoggerInterface;

/**
 * A Logger instance to not log
 */
class VoidLogger implements LoggerInterface
{
	/**
	 * System is unusable.
	 *
	 * @param string $message
	 * @param array $context
	 *
	 * @return void
	 */
	public function emergency($message, array $context = array())
	{
		return;
	}

	/**
	 * Action must be taken immediately.
	 *
	 * Example: Entire website down, database unavailable, etc. This should
	 * trigger the SMS alerts and wake you up.
	 *
	 * @param string $message
	 * @param array $context
	 *
	 * @return void
	 */
	public function alert($message, array $context = array())
	{
		return;
	}

	/**
	 * Critical conditions.
	 *
	 * Example: Application component unavailable, unexpected exception.
	 *
	 * @param string $message
	 * @param array $context
	 *
	 * @return void
	 */
	public function critical($message, array $context = array())
	{
		return;
	}

	/**
	 * Runtime errors that do not require immediate action but should typically
	 * be logged and monitored.
	 *
	 * @param string $message
	 * @param array $context
	 *
	 * @return void
	 */
	public function error($message, array $context = array())
	{
		return;
	}

	/**
	 * Exceptional occurrences that are not errors.
	 *
	 * Example: Use of deprecated APIs, poor use of an API, undesirable things
	 * that are not necessarily wrong.
	 *
	 * @param string $message
	 * @param array $context
	 *
	 * @return void
	 */
	public function warning($message, array $context = array())
	{
		return;
	}

	/**
	 * Normal but significant events.
	 *
	 * @param string $message
	 * @param array $context
	 *
	 * @return void
	 */
	public function notice($message, array $context = array())
	{
		return;
	}

	/**
	 * Interesting events.
	 *
	 * Example: User logs in, SQL logs.
	 *
	 * @param string $message
	 * @param array $context
	 *
	 * @return void
	 */
	public function info($message, array $context = array())
	{
		return;
	}

	/**
	 * Detailed debug information.
	 *
	 * @param string $message
	 * @param array $context
	 *
	 * @return void
	 */
	public function debug($message, array $context = array())
	{
		return;
	}

	/**
	 * Logs with an arbitrary level.
	 *
	 * @param mixed $level
	 * @param string $message
	 * @param array $context
	 *
	 * @return void
	 */
	public function log($level, $message, array $context = array())
	{
		return;
	}
}
