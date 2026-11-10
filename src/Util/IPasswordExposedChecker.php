<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Util;

/**
 * Checks if a plaintext password has been exposed in the public
 */
interface IPasswordExposedChecker
{
	/**
	 * Checks if the provided password has been exposed
	 *
	 * @param string $password The plaintext password to check
	 * @return bool True if exposed, false if not or if the check fails
	 */
	public function isExposed(string $password): bool;
}
