<?php
/**
 * @copyright Copyright (C) 2010-2023, the Friendica project
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

namespace Friendica\Core\Logger\Type;

use Friendica\Core\Logger\Capability\IHaveCallIntrospections;
use Friendica\Core\Logger\Exception\LoggerException;
use Friendica\Core\Logger\Exception\LogLevelException;
use Friendica\Util\DateTimeFormat;
use Psr\Log\LogLevel;

/**
 * A Logger instance for logging into a stream (file, stdout, stderr)
 */
class StreamLogger extends AbstractLogger
{
	const NAME = 'stream';

	/**
	 * The minimum loglevel at which this logger will be triggered
	 * @var string
	 */
	private $logLevel;

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
	 * Translates LogLevel log levels to integer values
	 * @var array
	 */
	public const levelToInt = [
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
	 * @param string          $level  The minimum loglevel at which this logger will be triggered
	 *
	 * @throws LoggerException
	 */
	public function __construct(string $channel, IHaveCallIntrospections $introspection, $stream, int $logLevel, int $pid)
	{
		parent::__construct($channel, $introspection);

		$this->stream   = $stream;
		$this->pid      = $pid;
		$this->logLevel = $logLevel;
	}

	public function close()
	{
		if (is_resource($this->stream)) {
			fclose($this->stream);
		}

		$this->stream = null;
	}

	/**
	 * Adds a new entry to the log
	 *
	 * @param mixed  $level
	 * @param string $message
	 * @param array  $context
	 *
	 * @return void
	 *
	 * @throws LoggerException
	 * @throws LogLevelException
	 */
	protected function addEntry($level, string $message, array $context = [])
	{
		if (!array_key_exists($level, static::levelToInt)) {
			throw new LogLevelException(sprintf('The level "%s" is not valid.', $level));
		}

		$logLevel = static::levelToInt[$level];

		if ($logLevel > $this->logLevel) {
			return;
		}

		$formattedLog = $this->formatLog($level, $message, $context);
		fwrite($this->stream, $formattedLog);
	}

	/**
	 * Formats a log record for the syslog output
	 *
	 * @param mixed  $level   The loglevel/priority
	 * @param string $message The message
	 * @param array  $context The context of this call
	 *
	 * @return string the formatted syslog output
	 *
	 * @throws LoggerException
	 */
	private function formatLog($level, string $message, array $context = []): string
	{
		$record = $this->introspection->getRecord();
		$record = array_merge($record, ['uid' => $this->logUid, 'process_id' => $this->pid]);

		try {
			$logMessage = DateTimeFormat::utcNow(DateTimeFormat::ATOM) . ' ';
		} catch (\Exception $exception) {
			throw new LoggerException('Cannot get current datetime.', $exception);
		}
		$logMessage .= $this->channel . ' ';
		$logMessage .= '[' . strtoupper($level) . ']: ';
		$logMessage .= $this->psrInterpolate($message, $context) . ' ';
		$logMessage .= $this->jsonEncodeArray($context) . ' - ';
		$logMessage .= $this->jsonEncodeArray($record);
		$logMessage .= PHP_EOL;

		return $logMessage;
	}
}
