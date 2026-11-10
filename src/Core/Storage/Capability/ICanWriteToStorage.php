<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Core\Storage\Capability;

use Friendica\Core\Storage\Exception\ReferenceStorageException;
use Friendica\Core\Storage\Exception\StorageException;

/**
 * Interface for writable storage backends
 *
 * Used for storages with CRUD functionality, mainly used for user data (e.g. photos, attachments).
 * There's only one active writable storage possible. This type of storage is selectable by the current administrator.
 */
interface ICanWriteToStorage extends ICanReadFromStorage
{
	/**
	 * Put data in backend as $ref. If $ref is not defined a new reference is created.
	 *
	 * @param string $data      Data to save
	 * @param string $reference Data reference. Optional.
	 *
	 * @return string Saved data reference
	 *
	 * @throws StorageException in case there's an unexpected error
	 */
	public function put(string $data, string $reference = ""): string;

	/**
	 * Remove data from backend
	 *
	 * @param string $reference Data reference
	 *
	 * @throws StorageException in case there's an unexpected error
	 * @throws ReferenceStorageException in case the reference doesn't exist
	 */
	public function delete(string $reference);
}
