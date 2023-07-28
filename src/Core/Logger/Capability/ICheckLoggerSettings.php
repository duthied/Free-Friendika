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

namespace Friendica\Core\Logger\Capability;

/**
 * Whenever a logging specific check is necessary, use this interface to encapsulate and centralize this logic
 */
interface ICheckLoggerSettings
{
	/**
	 * Checks if the logfile is set and usable
	 *
	 * @return string|null null in case everything is ok, otherwise returns the error
	 */
	public function checkLogfile(): ?string;

	/**
	 * Checks if the debugging logfile is usable in case it is set!
	 *
	 * @return string|null null in case everything is ok, otherwise returns the error
	 */
	public function checkDebugLogfile(): ?string;
}
