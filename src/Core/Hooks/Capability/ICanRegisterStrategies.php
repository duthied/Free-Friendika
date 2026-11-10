<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

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
