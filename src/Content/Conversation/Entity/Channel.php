<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Content\Conversation\Entity;

class Channel extends Timeline
{
	public const WHATSHOT         = 'whatshot';
	public const FORYOU           = 'foryou';
	public const DISCOVER         = 'discover';
	public const FOLLOWERS        = 'followers';
	public const SHARERSOFSHARERS = 'sharersofsharers';
	public const QUIETSHARERS     = 'quietsharers';
	public const IMAGE            = 'image';
	public const VIDEO            = 'video';
	public const AUDIO            = 'audio';
	public const LANGUAGE         = 'language';
}
