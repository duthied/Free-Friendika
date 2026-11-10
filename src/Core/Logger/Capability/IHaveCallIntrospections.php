<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Core\Logger\Capability;

interface IHaveCallIntrospections
{
	/**
	 * A list of classes, which shouldn't get logged
	 *
	 * @var string[]
	 */
	public const IGNORE_CLASS_LIST = [
		'Friendica\\Core\\Logger\\Type',
		\Friendica\Util\Profiler::class,
	];

	/**
	 * Adds new classes to get skipped
	 *
	 * @param array $classNames
	 */
	public function addClasses(array $classNames): void;

	/**
	 * Returns the introspection record of the current call
	 *
	 * @return array
	 */
	public function getRecord(): array;
}
