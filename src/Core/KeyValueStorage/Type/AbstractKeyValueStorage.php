<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Core\KeyValueStorage\Type;

use Friendica\Core\KeyValueStorage\Capability\IManageKeyValuePairs;

/**
 * An abstract helper class for Key-Value storage classes
 */
abstract class AbstractKeyValueStorage implements IManageKeyValuePairs
{
	public const NAME = '';

	/** {@inheritDoc} */
	public function get(string $key)
	{
		return $this->offsetGet($key);
	}

	/** {@inheritDoc} */
	public function set(string $key, $value): void
	{
		$this->offsetSet($key, $value);
	}

	/** {@inheritDoc} */
	public function delete(string $key): void
	{
		$this->offsetUnset($key);
	}
}
