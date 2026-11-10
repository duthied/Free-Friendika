<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Core\Storage\Type;

use Friendica\Core\Storage\Exception\ReferenceStorageException;
use Friendica\Core\Storage\Exception\StorageException;
use Friendica\Core\Storage\Capability\ICanReadFromStorage;

/**
 * System resource storage class
 *
 * This class is used to load system resources, like images.
 * Is not intended to be selectable by admins as default storage class.
 */
class SystemResource implements ICanReadFromStorage
{
	public const NAME = 'SystemResource';

	// Valid folders to look for resources
	public const VALID_FOLDERS = ["images"];

	/**
	 * @inheritDoc
	 */
	public function get(string $reference): string
	{
		$folder = dirname($reference);
		if (!in_array($folder, self::VALID_FOLDERS)) {
			throw new ReferenceStorageException(sprintf('System Resource is invalid for reference %s, no valid folder found', $reference));
		}
		if (!file_exists($reference)) {
			throw new StorageException(sprintf('System Resource is invalid for reference %s, the file doesn\'t exist', $reference));
		}
		$content = file_get_contents($reference);

		if ($content === false) {
			throw new StorageException(sprintf('Cannot get content for reference %s', $reference));
		}

		return $content;
	}

	/**
	 * @inheritDoc
	 */
	public function __toString(): string
	{
		return self::NAME;
	}

	/**
	 * @inheritDoc
	 */
	public static function getName(): string
	{
		return self::NAME;
	}
}
