<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica;

/**
 * These data transfer object classes are meant for API representations. As such, their members should be protected.
 * Then the JsonSerializable interface ensures the protected members will be included in a JSON encode situation.
 *
 * Constructors are supposed to take as arguments the Friendica dependencies/model/collection/data it needs to
 * populate the class members.
 */
abstract class BaseDataTransferObject implements \JsonSerializable
{
	/**
	 * Returns the current entity as an json array
	 *
	 * @return array
	 */
	public function jsonSerialize(): array
	{
		return $this->toArray();
	}

	/**
	 * Returns the current entity as an array
	 *
	 * @return array
	 */
	public function toArray(): array
	{
		return get_object_vars($this);
	}
}
