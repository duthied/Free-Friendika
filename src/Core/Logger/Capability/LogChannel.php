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
 * An enum class for the Log channels
 */
interface LogChannel
{
	/** @var string channel for the auth_ejabbered script */
	public const AUTH_JABBERED = 'auth_ejabberd';
	/** @var string Default channel in case it isn't set explicit */
	public const DEFAULT = self::APP;
	/** @var string channel for console execution */
	public const CONSOLE = 'console';
	/** @var string channel for developer focused logging */
	public const DEV = 'dev';
	/** @var string channel for daemon executions */
	public const DAEMON = 'daemon';
	/** @var string channel for worker execution */
	public const WORKER = 'worker';
	/** @var string channel for frontend app executions */
	public const APP = 'app';
}
