<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

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
