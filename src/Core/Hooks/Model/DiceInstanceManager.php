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

namespace Friendica\Core\Hooks\Model;

use Dice\Dice;
use Friendica\Core\Hooks\Capability\ICanCreateInstances;
use Friendica\Core\Hooks\Capability\ICanRegisterStrategies;
use Friendica\Core\Hooks\Exceptions\HookInstanceException;
use Friendica\Core\Hooks\Exceptions\HookRegisterArgumentException;
use Friendica\Core\Hooks\Util\StrategiesFileManager;

/**
 * This class represents an instance register, which uses Dice for creation
 *
 * @see Dice
 */
class DiceInstanceManager implements ICanCreateInstances, ICanRegisterStrategies
{
	protected $instance = [];

	/** @var Dice */
	protected $dice;

	public function __construct(Dice $dice, StrategiesFileManager $strategiesFileManager)
	{
		$this->dice = $dice;
		$strategiesFileManager->setupStrategies($this);
	}

	/** {@inheritDoc} */
	public function registerStrategy(string $interface, string $class, ?string $name = null): ICanRegisterStrategies
	{
		if (!empty($this->instance[$interface][strtolower($name)])) {
			throw new HookRegisterArgumentException(sprintf('A class with the name %s is already set for the interface %s', $name, $interface));
		}

		$this->instance[$interface][strtolower($name)] = $class;

		return $this;
	}

	/** {@inheritDoc} */
	public function create(string $class, string $strategy, array $arguments = []): object
	{
		if (empty($this->instance[$class][strtolower($strategy)])) {
			throw new HookInstanceException(sprintf('The class with the name %s isn\'t registered for the class or interface %s', $strategy, $class));
		}

		return $this->dice->create($this->instance[$class][strtolower($strategy)], $arguments);
	}
}
