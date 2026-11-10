<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Core\Cache\Type;

use Friendica\Core\Cache\Enum\Duration;

/**
 * This Trait is to compensate nonnative "exclusive" sets/deletes in caches
 */
trait CompareDeleteTrait
{
	abstract public function get(string $key);

	abstract public function set(string $key, $value, int $ttl = Duration::FIVE_MINUTES);

	abstract public function delete(string $key);

	abstract public function add(string $key, $value, int $ttl = Duration::FIVE_MINUTES);

	/**
	 * NonNative - Compares if the old value is set and removes it
	 *
	 * @param string $key   The cache key
	 * @param mixed  $value The old value we know and want to delete
	 *
	 * @return bool
	 */
	public function compareDelete(string $key, $value): bool
	{
		if ($this->add($key . "_lock", true)) {
			if ($this->get($key) === $value) {
				$this->delete($key);
				$this->delete($key . "_lock");
				return true;
			} else {
				$this->delete($key . "_lock");
				return false;
			}
		} else {
			return false;
		}
	}
}
