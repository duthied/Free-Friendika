<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica;

use Friendica\Network\HTTPException\InternalServerErrorException;

/**
 * The Entity classes directly inheriting from this abstract class are meant to represent a single business entity.
 * Their properties may or may not correspond with the database fields of the table we use to represent it.
 * Each model method must correspond to a business action being performed on this entity.
 * Only these methods will be allowed to alter the model data.
 *
 * To persist such a model, the associated Repository must be instantiated and the "save" method must be called
 * and passed the entity as a parameter.
 *
 * Ideally, the constructor should only be called in the associated Factory which will instantiate entities depending
 * on the provided data.
 *
 * Since these objects aren't meant to be using any dependency, including logging, unit tests can and must be
 * written for each and all of their methods
 */
abstract class BaseEntity extends BaseDataTransferObject
{
	/**
	 * @param string $name
	 * @return mixed
	 * @throws InternalServerErrorException
	 */
	public function __get(string $name)
	{
		if (!property_exists($this, $name)) {
			throw new InternalServerErrorException('Unknown property ' . $name . ' in Entity ' . static::class);
		}

		return $this->$name;
	}

	/**
	 * @param mixed $name
	 * @return bool
	 * @throws InternalServerErrorException
	 */
	public function __isset($name): bool
	{
		if (!property_exists($this, $name)) {
			throw new InternalServerErrorException('Unknown property ' . $name . ' of type ' . gettype($name) . ' in Entity ' . static::class);
		}

		return !empty($this->$name);
	}
}
