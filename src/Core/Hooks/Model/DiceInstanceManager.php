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
use Friendica\Core\Hooks\Capabilities\ICanCreateInstances;
use Friendica\Core\Hooks\Capabilities\ICanRegisterInstances;
use Friendica\Core\Hooks\Exceptions\HookInstanceException;
use Friendica\Core\Hooks\Exceptions\HookRegisterArgumentException;
use Friendica\Core\Hooks\Util\HookFileManager;

/**
 * This class represents an instance register, which uses Dice for creation
 *
 * @see Dice
 */
class DiceInstanceManager implements ICanCreateInstances, ICanRegisterInstances
{
	protected $instance  = [];
	protected $decorator = [];

	/** @var Dice */
	protected $dice;

	public function __construct(Dice $dice, HookFileManager $hookFileManager)
	{
		$this->dice = $dice;
		$hookFileManager->setupHooks($this);
	}

	/** {@inheritDoc} */
	public function registerStrategy(string $interface, string $class, ?string $name = null): ICanRegisterInstances
	{
		if (!empty($this->instance[$interface][$name])) {
			throw new HookRegisterArgumentException(sprintf('A class with the name %s is already set for the interface %s', $name, $interface));
		}

		$this->instance[$interface][$name] = $class;

		return $this;
	}

	/** {@inheritDoc} */
	public function registerDecorator(string $class, string $decoratorClass): ICanRegisterInstances
	{
		$this->decorator[$class][] = $decoratorClass;

		return $this;
	}

	/** {@inheritDoc} */
	public function create(string $class, array $arguments = []): object
	{
		$instanceClassName = $class;
		$instanceRule      = $this->dice->getRule($instanceClassName) ?? [];

		$instanceRule = array_replace_recursive($instanceRule, [
			'constructParams' => $arguments,
		]);

		$this->dice = $this->dice->addRule($instanceClassName, $instanceRule);

		foreach ($this->decorator[$class] ?? [] as $decorator) {
			$dependencyRule = $this->dice->getRule($decorator);
			for ($i = 0; $i < count($dependencyRule['call'] ?? []); $i++) {
				$dependencyRule['call'][$i][1] = [[Dice::INSTANCE => $instanceClassName]];
			}
			$dependencyRule['constructParams'] = $arguments;
			$dependencyRule['substitutions']   = [
				$class => $instanceClassName,
			];

			$this->dice = $this->dice->addRule($decorator, $dependencyRule);

			$instanceClassName = $decorator;
		}

		return $this->dice->create($instanceClassName);
	}

	/** {@inheritDoc} */
	public function createWithName(string $class, string $name, array $arguments = []): object
	{
		if (empty($this->instance[$class][$name])) {
			throw new HookInstanceException(sprintf('The class with the name %s isn\'t registered for the class or interface %s', $name, $class));
		}

		$instanceClassName = $this->instance[$class][$name];

		return $this->create($instanceClassName, $arguments);
	}
}
