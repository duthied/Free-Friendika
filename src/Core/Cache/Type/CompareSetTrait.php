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
trait CompareSetTrait
{
	abstract public function get(string $key);

	abstract public function set(string $key, $value, int $ttl = Duration::FIVE_MINUTES);

	abstract public function delete(string $key);

	abstract public function add(string $key, $value, int $ttl = Duration::FIVE_MINUTES);

	/**
	 * NonNative - Compares if the old value is set and sets the new value
	 *
	 * @param string $key      The cache key
	 * @param mixed  $oldValue The old value we know from the cache
	 * @param mixed  $newValue The new value we want to set
	 * @param int    $ttl      The cache lifespan, must be one of the Cache constants
	 *
	 * @return bool
	 */
	public function compareSet(string $key, $oldValue, $newValue, int $ttl = Duration::FIVE_MINUTES): bool
	{
		if ($this->add($key . "_lock", true)) {
			if ($this->get($key) === $oldValue) {
				$this->set($key, $newValue, $ttl);
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
