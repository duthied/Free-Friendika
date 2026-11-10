<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Moderation\Factory\Report;

use Friendica\Capabilities\ICanCreateFromTableRow;

class Rule extends \Friendica\BaseFactory implements ICanCreateFromTableRow
{
	public function createFromTableRow(array $row): \Friendica\Moderation\Entity\Report\Rule
	{
		return new \Friendica\Moderation\Entity\Report\Rule(
			$row['line-id'],
			$row['text'],
		);
	}
}
