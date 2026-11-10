<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Core\Logger\Capability;

/**
 * Whenever a logging specific check is necessary, use this interface to encapsulate and centralize this logic
 */
interface ICheckLoggerSettings
{
	/**
	 * Checks if the logfile is set and usable
	 *
	 * @return string|null null in case everything is ok, otherwise returns the error
	 */
	public function checkLogfile(): ?string;

	/**
	 * Checks if the debugging logfile is usable in case it is set!
	 *
	 * @return string|null null in case everything is ok, otherwise returns the error
	 */
	public function checkDebugLogfile(): ?string;
}
