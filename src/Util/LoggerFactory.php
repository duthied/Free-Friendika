<?php

namespace Friendica\Util;

use Friendica\Network\HTTPException\InternalServerErrorException;
use Friendica\Util\Logger\FriendicaDevelopHandler;
use Monolog;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

/**
 * A logger factory
 *
 * Currently only Monolog is supported
 */
class LoggerFactory
{
	/**
	 * Creates a new PSR-3 compliant logger instances
	 *
	 * @param string $channel The channel of the logger instance
	 *
	 * @return LoggerInterface The PSR-3 compliant logger instance
	 */
	public static function create($channel)
	{
		$logger = new Monolog\Logger($channel);
		$logger->pushProcessor(new Monolog\Processor\PsrLogMessageProcessor());

		return $logger;
	}

	/**
	 * Creates a new PSR-3 compliant develop logger
	 *
	 * If you want to debug only interactions from your IP or the IP of a remote server for federation debug,
	 * you'll use this logger instance for the duration of your work.
	 *
	 * It should never get filled during normal usage of Friendica
	 *
	 * @param string $channel      The channel of the logger instance
	 * @param string $developerIp  The IP of the developer who wants to use the logger
	 *
	 * @return LoggerInterface The PSR-3 compliant logger instance
	 */
	public static function createDev($channel, $developerIp)
	{
		$logger = new Monolog\Logger($channel);
		$logger->pushProcessor(new Monolog\Processor\PsrLogMessageProcessor());

		$logger->pushHandler(new FriendicaDevelopHandler($developerIp));

		return $logger;
	}

	/**
	 * Adding a handler to a given logger instance
	 *
	 * @param LoggerInterface $logger  The logger instance
	 * @param mixed           $stream  The stream which handles the logger output
	 * @param string          $level   The level, for which this handler at least should handle logging
	 *
	 * @return void
	 *
	 * @throws InternalServerErrorException if the logger is incompatible to the logger factory
	 * @throws \Exception in case of general failures
	 */
	public static function addStreamHandler($logger, $stream, $level = LogLevel::NOTICE)
	{
		if ($logger instanceof Monolog\Logger) {
			$fileHandler = new Monolog\Handler\StreamHandler($stream . ".1", Monolog\Logger::toMonologLevel($level));

			$formatter = new Monolog\Formatter\LineFormatter("%datetime% %channel% [%level_name%]: %message% %context% %extra%\n");
			$fileHandler->setFormatter($formatter);

			$logger->pushHandler($fileHandler);
		} else {
			throw new InternalServerErrorException('Logger instance incompatible for MonologFactory');
		}
	}
}
