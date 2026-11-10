<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Content\Conversation\Collection;

use Friendica\Content\Conversation\Entity;

class UserDefinedChannels extends Timelines
{
	public function current(): Entity\UserDefinedChannel
	{
		return parent::current();
	}
}
