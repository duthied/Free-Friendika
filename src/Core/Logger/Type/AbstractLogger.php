<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Core\Logger\Type;

use Friendica\Core\Logger\Capability\IHaveCallIntrospections;
use Friendica\Core\Logger\Exception\LoggerException;
use Friendica\Util\Strings;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Stringable;

/**
 * This class contains all necessary dependencies and calls for Friendica
 * Every new Logger should extend this class and define, how addEntry() works
 *
 * Additional information for each Logger, who extends this class:
 * - Introspection
 * - UID for each call
 * - Channel of the current call (i.e. index, worker, daemon, ...)
 */
abstract class AbstractLogger implements LoggerInterface
{
	public const NAME = '';

	/**
	 * The output channel of this logger
	 * @var string
	 */
	protected $channel;

	/**
	 * The Introspection for the current call
	 * @var IHaveCallIntrospections
	 */
	protected $introspection;

	/**
	 * The UID of the current call
	 * @var string
	 */
	protected $logUid;

	/**
	 * Adds a new entry to the log
	 *
	 * @param mixed  $level
	 * @param string $message
	 * @param array  $context
	 *
	 * @return void
	 */
	abstract protected function addEntry($level, string $message, array $context = []);

	/**
	 * @param string        $channel       The output channel
	 * @param IHaveCallIntrospections $introspection The introspection of the current call
	 *
	 * @throws LoggerException
	 */
	public function __construct(string $channel, IHaveCallIntrospections $introspection)
	{
		$this->channel       = $channel;
		$this->introspection = $introspection;

		try {
			$this->logUid = Strings::getRandomHex(6);
		} catch (\Exception $exception) {
			throw new LoggerException('Cannot generate log Id', $exception);
		}
	}

	/**
	 * Simple interpolation of PSR-3 compliant replacements ( variables between '{' and '}' )
	 *
	 * @see https://www.php-fig.org/psr/psr-3/#12-message
	 *
	 * @param string $message
	 * @param array  $context
	 *
	 * @return string the interpolated message
	 */
	protected function psrInterpolate(string $message, array $context = []): string
	{
		$replace = [];
		foreach ($context as $key => $value) {
			// check that the value can be casted to string
			if (!is_array($value) && (!is_object($value) || method_exists($value, '__toString'))) {
				$replace['{' . $key . '}'] = $value;
			} elseif (is_array($value)) {
				$replace['{' . $key . '}'] = @json_encode($value);
			}
		}

		return strtr($message, $replace);
	}

	/**
	 * JSON Encodes a complete array including objects with "__toString()" methods
	 *
	 * @param array $input an Input Array to encode
	 *
	 * @return false|string The json encoded output of the array
	 */
	protected function jsonEncodeArray(array $input)
	{
		$output = [];

		foreach ($input as $key => $value) {
			if (is_object($value) && method_exists($value, '__toString')) {
				$output[$key] = $value->__toString();
			} else {
				$output[$key] = $value;
			}
		}

		return @json_encode($output, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
	}

	/**
	 * {@inheritdoc}
	 */
	public function emergency(string|Stringable $message, array $context = [])
	{
		$this->addEntry(LogLevel::EMERGENCY, (string) $message, $context);
	}

	/**
	 * {@inheritdoc}
	 */
	public function alert(string|Stringable $message, array $context = [])
	{
		$this->addEntry(LogLevel::ALERT, (string) $message, $context);
	}

	/**
	 * {@inheritdoc}
	 */
	public function critical(string|Stringable $message, array $context = [])
	{
		$this->addEntry(LogLevel::CRITICAL, (string) $message, $context);
	}

	/**
	 * {@inheritdoc}
	 */
	public function error(string|Stringable $message, array $context = [])
	{
		$this->addEntry(LogLevel::ERROR, (string) $message, $context);
	}

	/**
	 * {@inheritdoc}
	 */
	public function warning(string|Stringable $message, array $context = [])
	{
		$this->addEntry(LogLevel::WARNING, (string) $message, $context);
	}

	/**
	 * {@inheritdoc}
	 */
	public function notice(string|Stringable $message, array $context = [])
	{
		$this->addEntry(LogLevel::NOTICE, (string) $message, $context);
	}

	/**
	 * {@inheritdoc}
	 */
	public function info(string|Stringable $message, array $context = [])
	{
		$this->addEntry(LogLevel::INFO, (string) $message, $context);
	}

	/**
	 * {@inheritdoc}
	 */
	public function debug(string|Stringable $message, array $context = [])
	{
		$this->addEntry(LogLevel::DEBUG, (string) $message, $context);
	}

	/**
	 * {@inheritdoc}
	 */
	public function log($level, string|Stringable $message, array $context = [])
	{
		$this->addEntry($level, (string) $message, $context);
	}
}
