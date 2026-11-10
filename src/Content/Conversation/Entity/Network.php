<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Content\Conversation\Entity;

final class Network extends Timeline
{
	public const STAR      = 'star';
	public const MENTION   = 'mention';
	public const RECEIVED  = 'received';
	public const COMMENTED = 'commented';
	public const CREATED   = 'created';
}
