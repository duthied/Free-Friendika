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

use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\Logger\Exception\LogLevelException;
use Friendica\Core\Logger\Factory\SyslogLogger;
use Friendica\Core\Logger\Type\SyslogLogger as SyslogLoggerClass;
use Psr\Log\LoggerInterface;

class SyslogLoggerFactoryWrapper extends SyslogLogger
{
	public function create(IManageConfigValues $config): LoggerInterface
	{
		$logOpts     = $config->get('system', 'syslog_flags')    ?? SyslogLoggerClass::DEFAULT_FLAGS;
		$logFacility = $config->get('system', 'syslog_facility') ?? SyslogLoggerClass::DEFAULT_FACILITY;
		$loglevel    = SyslogLogger::mapLegacyConfigDebugLevel($config->get('system', 'loglevel'));

		if (array_key_exists($loglevel, SyslogLoggerClass::logLevels)) {
			$loglevel = SyslogLoggerClass::logLevels[$loglevel];
		} else {
			throw new LogLevelException(sprintf('The level "%s" is not valid.', $loglevel));
		}

		return new SyslogLoggerWrapper($this->channel, $this->introspection, $loglevel, $logOpts, $logFacility);
	}
}
