<?php

namespace Friendica\Test\src\Util\Logger;

use Friendica\Util\Introspection;
use Friendica\Util\Logger\SyslogLogger;
use Psr\Log\LogLevel;

/**
 * Wraps the SyslogLogger for replacing the syslog call with a string field.
 */
class SyslogLoggerWrapper extends SyslogLogger
{
	private $content;

	public function __construct($channel, Introspection $introspection, $level = LogLevel::NOTICE, $logOpts = LOG_PID, $logFacility = LOG_USER)
	{
		parent::__construct($channel, $introspection, $level, $logOpts, $logFacility);

		$this->content = '';
	}

	/**
	 * Gets the content from the wrapped Syslog
	 *
	 * @return string
	 */
	public function getContent()
	{
		return $this->content;
	}

	/**
	 * {@inheritdoc}
	 */
	protected function syslogWrapper($level, $entry)
	{
		$this->content .= $entry . PHP_EOL;
	}
}
