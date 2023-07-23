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

namespace Friendica\Core\Hooks\Capability;

/**
 * An enum of hook types, based on behavioral design patterns
 * @see https://refactoring.guru/design-patterns/behavioral-patterns
 */
interface BehavioralHookType
{
	/**
	 * Defines the key for the list of strategy-hooks.
	 *
	 * @see https://refactoring.guru/design-patterns/strategy
	 */
	const STRATEGY = 'strategy';
	const EVENT    = 'event';
}
