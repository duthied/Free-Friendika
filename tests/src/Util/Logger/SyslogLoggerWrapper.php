<?php
/**
 * @copyright Copyright (C) 2020, Friendica
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
