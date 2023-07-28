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

namespace Friendica\Test\src\Core\Logger;

use Friendica\Core\Logger\Capability\IHaveCallIntrospections;
use Friendica\Core\Logger\Type\SyslogLogger;

/**
 * Wraps the SyslogLogger for replacing the syslog call with a string field.
 */
class SyslogLoggerWrapper extends SyslogLogger
{
	private $content;

	public function __construct(string $channel, IHaveCallIntrospections $introspection, string $logLevel, string $logOptions, string $logFacility)
	{
		parent::__construct($channel, $introspection, $logLevel, $logOptions, $logFacility);

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
	 * @noinspection PhpMissingParentCallCommonInspection
	 */
	protected function syslogWrapper(int $level, string $entry)
	{
		$this->content .= $entry . PHP_EOL;
	}
}
