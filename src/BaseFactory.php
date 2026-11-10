<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica;

use Psr\Log\LoggerInterface;

/**
 * Factories act as an intermediary to avoid direct Entity instantiation.
 *
 * @see BaseCollection
 */
abstract class BaseFactory
{
	/** @var LoggerInterface */
	protected $logger;

	public function __construct(LoggerInterface $logger)
	{
		$this->logger = $logger;
	}
}
