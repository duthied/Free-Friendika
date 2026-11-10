<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Capabilities;

use Friendica\BaseEntity;

interface ICanCreateFromTableRow
{
	/**
	 * Returns the corresponding Entity given a table row record
	 *
	 * @param array $row
	 * @return BaseEntity
	 */
	public function createFromTableRow(array $row);
}
