<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Core\Storage\Capability;

use Friendica\Core\Storage\Exception\ReferenceStorageException;
use Friendica\Core\Storage\Exception\StorageException;

/**
 * Interface for basic storage backends
 */
interface ICanReadFromStorage
{
	/**
	 * Get data from backend
	 *
	 * @param string $reference Data reference
	 *
	 * @return string
	 *
	 * @throws StorageException in case there's an unexpected error
	 * @throws ReferenceStorageException in case the reference doesn't exist
	 */
	public function get(string $reference): string;

	/**
	 * The name of the backend
	 *
	 * @return string
	 */
	public function __toString(): string;

	/**
	 * The name of the backend
	 *
	 * @return string
	 */
	public static function getName(): string;
}
