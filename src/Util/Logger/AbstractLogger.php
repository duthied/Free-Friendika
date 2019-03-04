<?php

namespace Friendica\Util\Logger;

use Friendica\Util\Introspection;
use Friendica\Util\Strings;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

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
	/**
	 * The output channel of this logger
	 * @var string
	 */
	protected $channel;

	/**
	 * The Introspection for the current call
	 * @var Introspection
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
	 * @param int    $level
	 * @param string $message
	 * @param array  $context
	 *
	 * @return void
	 */
	abstract protected function addEntry($level, $message, $context = []);

	/**
	 * @param string        $channel       The output channel
	 * @param Introspection $introspection The introspection of the current call
	 *
	 * @throws \Exception
	 */
	public function __construct($channel, Introspection $introspection)
	{
		$this->channel       = $channel;
		$this->introspection = $introspection;
		$this->logUid        = Strings::getRandomHex(6);
	}

	/**
	 * Simple interpolation of PSR-3 compliant replacements ( variables between '{' and '}' )
	 * @see https://www.php-fig.org/psr/psr-3/#12-message
	 *
	 * @param string $message
	 * @param array  $context
	 *
	 * @return string the interpolated message
	 */
	protected function psrInterpolate($message, array $context = array())
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
	 * {@inheritdoc}
	 */
	public function emergency($message, array $context = array())
	{
		$this->addEntry(LogLevel::EMERGENCY, (string) $message, $context);
	}

	/**
	 * {@inheritdoc}
	 */
	public function alert($message, array $context = array())
	{
		$this->addEntry(LogLevel::ALERT, (string) $message, $context);
	}

	/**
	 * {@inheritdoc}
	 */
	public function critical($message, array $context = array())
	{
		$this->addEntry(LogLevel::CRITICAL, (string) $message, $context);
	}

	/**
	 * {@inheritdoc}
	 */
	public function error($message, array $context = array())
	{
		$this->addEntry(LogLevel::ERROR, (string) $message, $context);
	}

	/**
	 * {@inheritdoc}
	 */
	public function warning($message, array $context = array())
	{
		$this->addEntry(LogLevel::WARNING, (string) $message, $context);
	}

	/**
	 * {@inheritdoc}
	 */
	public function notice($message, array $context = array())
	{
		$this->addEntry(LogLevel::NOTICE, (string) $message, $context);
	}

	/**
	 * {@inheritdoc}
	 */
	public function info($message, array $context = array())
	{
		$this->addEntry(LogLevel::INFO, (string) $message, $context);
	}

	/**
	 * {@inheritdoc}
	 */
	public function debug($message, array $context = array())
	{
		$this->addEntry(LogLevel::DEBUG, (string) $message, $context);
	}

	/**
	 * {@inheritdoc}
	 */
	public function log($level, $message, array $context = array())
	{
		$this->addEntry($level, (string) $message, $context);
	}
}
