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
 * creates special instances for given classes
 */
interface ICanCreateInstances
{
	/**
	 * Returns a new instance of a given class for the corresponding name
	 *
	 * The instance will be build based on the registered strategy and the (unique) name
	 *
	 * @param string $class     The fully-qualified name of the given class or interface which will get returned
	 * @param string $strategy  An arbitrary identifier to find a concrete instance strategy.
	 * @param array  $arguments Additional arguments, which can be passed to the constructor of "$class" at runtime
	 *
	 * @return object The concrete instance of the type "$class"
	 */
	public function create(string $class, string $strategy, array $arguments = []): object;
}
