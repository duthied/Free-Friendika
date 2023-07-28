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

namespace Friendica\Core\Logger\Factory;

use Friendica\Core\Logger\Capability\IHaveCallIntrospections;
use Psr\Log\LogLevel;

/**
 * Abstract class for creating logger types, which includes common necessary logic/content
 */
abstract class AbstractLoggerTypeFactory
{
	/** @var string */
	protected $channel;
	/** @var IHaveCallIntrospections */
	protected $introspection;

	/**
	 * @param string $channel The channel for the logger
	 */
	public function __construct(IHaveCallIntrospections $introspection, string $channel)
	{
		$this->channel       = $channel;
		$this->introspection = $introspection;
	}

	/**
	 * Mapping a legacy level to the PSR-3 compliant levels
	 *
	 * @see https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-3-logger-interface.md#5-psrlogloglevel
	 *
	 * @param string $level the level to be mapped
	 *
	 * @return string the PSR-3 compliant level
	 */
	protected static function mapLegacyConfigDebugLevel(string $level): string
	{
		switch ($level) {
			// legacy WARNING
			case "0":
				return LogLevel::ERROR;
			// legacy INFO
			case "1":
				return LogLevel::WARNING;
			// legacy TRACE
			case "2":
				return LogLevel::NOTICE;
			// legacy DEBUG
			case "3":
				return LogLevel::INFO;
			// legacy DATA
			case "4":
			// legacy ALL
			case "5":
				return LogLevel::DEBUG;
			// default if nothing set
			default:
				return $level;
		}
	}
}
