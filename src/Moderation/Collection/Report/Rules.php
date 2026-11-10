<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Moderation\Collection\Report;

class Rules extends \Friendica\BaseCollection
{
	/**
	 * @return \Friendica\Moderation\Entity\Report\Rule
	 */
	public function current(): \Friendica\Moderation\Entity\Report\Rule
	{
		return parent::current();
	}
}
