<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Core\Session\Type;

use Friendica\Core\Session\Capability\IHandleSessions;

/**
 * Usable for backend processes (daemon/worker) and testing
 *
 * @todo after replacing the last direct $_SESSION call, use a internal array instead of the global variable
 */
class Memory extends AbstractSession implements IHandleSessions
{
	public function __construct()
	{
		// Backward compatibility until all Session variables are replaced
		// with the Session class
		$_SESSION = [];
	}
}
