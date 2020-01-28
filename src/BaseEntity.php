<?php

namespace Friendica;

/**
 * The API entity classes are meant as data transfer objects. As such, their member should be protected.
 * Then the JsonSerializable interface ensures the protected members will be included in a JSON encode situation.
 *
 * Constructors are supposed to take as arguments the Friendica dependencies/model/collection/data it needs to
 * populate the class members.
 */
abstract class BaseEntity implements \JsonSerializable
{
	/**
	 * Returns the current entity as an json array
	 *
	 * @return array
	 */
	public function jsonSerialize()
	{
		return $this->toArray();
	}

	/**
	 * Returns the current entity as an array
	 *
	 * @return array
	 */
	public function toArray()
	{
		return get_object_vars($this);
	}
}
