<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Worker;

use Friendica\Model\Contact;

/**
 * Update contact suggestions
 */
class UpdateSuggestions
{
	public static function execute(int $uid)
	{
		Contact\Relation::updateCachedSuggestions($uid);
	}
}
