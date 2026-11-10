<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Core\Session\Handler;

abstract class AbstractSessionHandler implements \SessionHandlerInterface
{
	/** @var int Duration of the Session */
	public const EXPIRE = 180000;
}
