<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Content\Conversation\Entity;

class UserDefinedChannel extends Channel
{
	public const CIRCLE_GLOBAL    = 0;
	public const CIRCLE_ACTIVITY  = -5;
	public const CIRCLE_POSTS     = -4;
	public const CIRCLE_CREATION  = -3;
	public const CIRCLE_FOLLOWING = -1;
	public const CIRCLE_FOLLOWERS = -2;
}
