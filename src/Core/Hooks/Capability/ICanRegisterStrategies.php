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

use Friendica\Core\Hooks\Exceptions\HookRegisterArgumentException;

/**
 * Register strategies for given classes
 */
interface ICanRegisterStrategies
{
	/**
	 * Register a class(strategy) for a given interface with a unique name.
	 *
	 * @see https://refactoring.guru/design-patterns/strategy
	 *
	 * @param string  $interface The interface, which the given class implements
	 * @param string  $class     The fully-qualified given class name
	 *                           A placeholder for dependencies is possible as well
	 * @param ?string $name      An arbitrary identifier for the given strategy, which will be used for factories, dependency injections etc.
	 *
	 * @return $this This interface for chain-calls
	 *
	 * @throws HookRegisterArgumentException in case the given class for the interface isn't valid or already set
	 */
	public function registerStrategy(string $interface, string $class, ?string $name = null): self;
}
