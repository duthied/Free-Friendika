<?php

namespace Friendica\Util\Logger;

use Friendica\Network\HTTPException\InternalServerErrorException;
use Friendica\Util\Introspection;
use Friendica\Util\Strings;
use Psr\Log\InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

/**
 * A Logger instance for logging into a stream
 * @see http://php.net/manual/en/function.syslog.php
 */
class StreamLogger implements LoggerInterface
{
	const IDENT = 'Friendica';

	/**
	 * Translates LogLevel log levels to syslog log priorities.
	 * @var array
	 */
	private $logLevels = [
		LogLevel::DEBUG     => LOG_DEBUG,
		LogLevel::INFO      => LOG_INFO,
		LogLevel::NOTICE    => LOG_NOTICE,
		LogLevel::WARNING   => LOG_WARNING,
		LogLevel::ERROR     => LOG_ERR,
		LogLevel::CRITICAL  => LOG_CRIT,
		LogLevel::ALERT     => LOG_ALERT,
		LogLevel::EMERGENCY => LOG_EMERG,
	];

	/**
	 * Translates log priorities to string outputs
	 * @var array
	 */
	private $logToString = [
		LOG_DEBUG   => 'DEBUG',
		LOG_INFO    => 'INFO',
		LOG_NOTICE  => 'NOTICE',
		LOG_WARNING => 'WARNING',
		LOG_ERR     => 'ERROR',
		LOG_CRIT    => 'CRITICAL',
		LOG_ALERT   => 'ALERT',
		LOG_EMERG   => 'EMERGENCY'
	];

	/**
	 * The channel of the current process (added to each message)
	 * @var string
	 */
	private $channel;

	/**
	 * Indicates what logging options will be used when generating a log message
	 * @see http://php.net/manual/en/function.openlog.php#refsect1-function.openlog-parameters
	 *
	 * @var int
	 */
	private $logOpts;

	/**
	 * Used to specify what type of program is logging the message
	 * @see http://php.net/manual/en/function.openlog.php#refsect1-function.openlog-parameters
	 *
	 * @var int
	 */
	private $logFacility;

	/**
	 * The minimum loglevel at which this logger will be triggered
	 * @var int
	 */
	private $logLevel;

	/**
	 * The Introspection for the current call
	 * @var Introspection
	 */
	private $introspection;

	/**
	 * The UID of the current call
	 * @var string
	 */
	private $logUid;

	/**
	 * @param string        $channel       The output channel
	 * @param Introspection $introspection The introspection of the current call
	 * @param string        $level         The minimum loglevel at which this logger will be triggered
	 * @param int           $logOpts       Indicates what logging options will be used when generating a log message
	 * @param int           $logFacility   Used to specify what type of program is logging the message
	 *
	 * @throws \Exception
	 */
	public function __construct($channel, Introspection $introspection, $level = LogLevel::NOTICE, $logOpts = LOG_PID, $logFacility = LOG_USER)
	{
		$this->logUid = Strings::getRandomHex(6);
		$this->channel = $channel;
		$this->logOpts = $logOpts;
		$this->logFacility = $logFacility;
		$this->logLevel = $this->mapLevelToPriority($level);
		$this->introspection = $introspection;
	}

	/**
	 * Maps the LogLevel (@see LogLevel ) to a SysLog priority (@see http://php.net/manual/en/function.syslog.php#refsect1-function.syslog-parameters )
	 *
	 * @param string $level A LogLevel
	 *
	 * @return int The SysLog priority
	 *
	 * @throws \Psr\Log\InvalidArgumentException If the loglevel isn't valid
	 */
	public function mapLevelToPriority($level)
	{
		if (!array_key_exists($level, $this->logLevels)) {
			throw new InvalidArgumentException('LogLevel \'' . $level . '\' isn\'t valid.');
		}

		return $this->logLevels[$level];
	}

	/**
	 * Writes a message to the syslog
	 * @see http://php.net/manual/en/function.syslog.php#refsect1-function.syslog-parameters
	 *
	 * @param int    $priority The Priority
	 * @param string $message  The message of the log
	 *
	 * @throws InternalServerErrorException if syslog cannot be used
	 */
	private function write($priority, $message)
	{
		if (!openlog(self::IDENT, $this->logOpts, $this->logFacility)) {
			throw new InternalServerErrorException('Can\'t open syslog for ident "' . $this->channel . '" and facility "' . $this->logFacility . '""');
		}

		syslog($priority, $message);
	}

	/**
	 * Closes the Syslog
	 */
	public function close()
	{
		closelog();
	}

	/**
	 * Formats a log record for the syslog output
	 *
	 * @param int    $level   The loglevel/priority
	 * @param string $message The message
	 * @param array  $context The context of this call
	 *
	 * @return string the formatted syslog output
	 */
	private function formatLog($level, $message, $context = [])
	{
		$record = $this->introspection->getRecord();
		$record = array_merge($record, ['uid' => $this->logUid]);
		$logMessage = '';

		$logMessage .= $this->channel . ' ';
		$logMessage .= '[' . $this->logToString[$level] . ']: ';
		$logMessage .= $this->psrInterpolate($message, $context) . ' ';
		$logMessage .= @json_encode($context) . ' - ';
		$logMessage .= @json_encode($record);

		return $logMessage;
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
	private function psrInterpolate($message, array $context = array())
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
	 * Adds a new entry to the syslog
	 *
	 * @param int    $level
	 * @param string $message
	 * @param array  $context
	 *
	 * @throws InternalServerErrorException if the syslog isn't available
	 */
	private function addEntry($level, $message, $context = [])
	{
		if ($level >= $this->logLevel) {
			return;
		}

		$formattedLog = $this->formatLog($level, $message, $context);
		$this->write($level, $formattedLog);
	}

	/**
	 * {@inheritdoc}
	 * @throws InternalServerErrorException if the syslog isn't available
	 */
	public function emergency($message, array $context = array())
	{
		$this->addEntry(LOG_EMERG, $message, $context);
	}

	/**
	 * {@inheritdoc}
	 * @throws InternalServerErrorException if the syslog isn't available
	 */
	public function alert($message, array $context = array())
	{
		$this->addEntry(LOG_ALERT, $message, $context);
	}

	/**
	 * {@inheritdoc}
	 * @throws InternalServerErrorException if the syslog isn't available
	 */
	public function critical($message, array $context = array())
	{
		$this->addEntry(LOG_CRIT, $message, $context);
	}

	/**
	 * {@inheritdoc}
	 * @throws InternalServerErrorException if the syslog isn't available
	 */
	public function error($message, array $context = array())
	{
		$this->addEntry(LOG_ERR, $message, $context);
	}

	/**
	 * {@inheritdoc}
	 * @throws InternalServerErrorException if the syslog isn't available
	 */
	public function warning($message, array $context = array())
	{
		$this->addEntry(LOG_WARNING, $message, $context);
	}

	/**
	 * {@inheritdoc}
	 * @throws InternalServerErrorException if the syslog isn't available
	 */
	public function notice($message, array $context = array())
	{
		$this->addEntry(LOG_NOTICE, $message, $context);
	}

	/**
	 * {@inheritdoc}
	 * @throws InternalServerErrorException if the syslog isn't available
	 */
	public function info($message, array $context = array())
	{
		$this->addEntry(LOG_INFO, $message, $context);
	}

	/**
	 * {@inheritdoc}
	 * @throws InternalServerErrorException if the syslog isn't available
	 */
	public function debug($message, array $context = array())
	{
		$this->addEntry(LOG_DEBUG, $message, $context);
	}

	/**
	 * {@inheritdoc}
	 * @throws InternalServerErrorException if the syslog isn't available
	 */
	public function log($level, $message, array $context = array())
	{
		$logLevel = $this->mapLevelToPriority($level);
		$this->addEntry($logLevel, $message, $context);
	}
}
