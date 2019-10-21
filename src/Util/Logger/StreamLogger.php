<?php

namespace Friendica\Util\Logger;

use Friendica\Util\DateTimeFormat;
use Friendica\Util\Introspection;
use Psr\Log\LogLevel;

/**
 * A Logger instance for logging into a stream (file, stdout, stderr)
 */
class StreamLogger extends AbstractLogger
{
	/**
	 * The minimum loglevel at which this logger will be triggered
	 * @var string
	 */
	private $logLevel;

	/**
	 * The file URL of the stream (if needed)
	 * @var string
	 */
	private $url;

	/**
	 * The stream, where the current logger is writing into
	 * @var resource
	 */
	private $stream;

	/**
	 * The current process ID
	 * @var int
	 */
	private $pid;

	/**
	 * An error message
	 * @var string
	 */
	private $errorMessage;

	/**
	 * Translates LogLevel log levels to integer values
	 * @var array
	 */
	private $levelToInt = [
		LogLevel::EMERGENCY => 0,
		LogLevel::ALERT     => 1,
		LogLevel::CRITICAL  => 2,
		LogLevel::ERROR     => 3,
		LogLevel::WARNING   => 4,
		LogLevel::NOTICE    => 5,
		LogLevel::INFO      => 6,
		LogLevel::DEBUG     => 7,
	];

	/**
	 * {@inheritdoc}
	 * @param string|resource $stream The stream to write with this logger (either a file or a stream, i.e. stdout)
	 * @param string          $level  The minimum loglevel at which this logger will be triggered
	 *
	 * @throws \Exception
	 */
	public function __construct($channel, $stream, Introspection $introspection, $level = LogLevel::DEBUG)
	{
		parent::__construct($channel, $introspection);

		if (is_resource($stream)) {
			$this->stream = $stream;
		} elseif (is_string($stream)) {
			$this->url = $stream;
		} else {
			throw new \InvalidArgumentException('A stream must either be a resource or a string.');
		}

		$this->pid = getmypid();
		if (array_key_exists($level, $this->levelToInt)) {
			$this->logLevel = $this->levelToInt[$level];
		} else {
			throw new \InvalidArgumentException(sprintf('The level "%s" is not valid.', $level));
		}

		$this->checkStream();
	}

	public function close()
	{
		if ($this->url && is_resource($this->stream)) {
			fclose($this->stream);
		}

		$this->stream = null;
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
		if (!array_key_exists($level, $this->levelToInt)) {
			throw new \InvalidArgumentException(sprintf('The level "%s" is not valid.', $level));
		}

		$logLevel = $this->levelToInt[$level];

		if ($logLevel > $this->logLevel) {
			return;
		}

		$this->checkStream();

		$formattedLog = $this->formatLog($level, $message, $context);
		fwrite($this->stream, $formattedLog);
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
		$record = array_merge($record, ['uid' => $this->logUid, 'process_id' => $this->pid]);
		$logMessage = '';

		$logMessage .= DateTimeFormat::utcNow() . ' ';
		$logMessage .= $this->channel . ' ';
		$logMessage .= '[' . strtoupper($level) . ']: ';
		$logMessage .= $this->psrInterpolate($message, $context) . ' ';
		$logMessage .= @json_encode($context) . ' - ';
		$logMessage .= @json_encode($record);
		$logMessage .= PHP_EOL;

		return $logMessage;
	}

	private function checkStream()
	{
		if (is_resource($this->stream)) {
			return;
		}

		if (empty($this->url)) {
			throw new \LogicException('Missing stream URL.');
		}

		$this->createDir();
		set_error_handler([$this, 'customErrorHandler']);
		$this->stream = fopen($this->url, 'ab');
		restore_error_handler();

		if (!is_resource($this->stream)) {
			$this->stream = null;

			throw new \UnexpectedValueException(sprintf('The stream or file "%s" could not be opened: ' . $this->errorMessage, $this->url));
		}
	}

	private function createDir()
	{
		$dirname = null;
		$pos = strpos($this->url, '://');
		if (!$pos) {
			$dirname = dirname($this->url);
		}

		if (substr($this->url, 0, 7) === 'file://') {
			$dirname = dirname(substr($this->url, 7));
		}

		if (isset($dirname) && !is_dir($dirname)) {
			set_error_handler([$this, 'customErrorHandler']);
			$status = mkdir($dirname, 0777, true);
			restore_error_handler();

			if (!$status && !is_dir($dirname)) {
				throw new \UnexpectedValueException(sprintf('Directory "%s" cannot get created: ' . $this->errorMessage, $dirname));
			}
		}
	}

	private function customErrorHandler($code, $msg)
	{
		$this->errorMessage = preg_replace('{^(fopen|mkdir)\(.*?\): }', '', $msg);
	}
}
