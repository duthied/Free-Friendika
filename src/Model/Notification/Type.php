<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Model\Notification;

/**
 * Enum for different types of the Notify
 */
class Type
{
	/** @var int Notification about a introduction */
	public const INTRO = 1;
	/** @var int Notification about a confirmed introduction */
	public const CONFIRM = 2;
	/** @var int Notification about a post on your wall */
	public const WALL = 4;
	/** @var int Notification about a followup comment */
	public const COMMENT = 8;
	/** @var int Notification about a private message */
	public const MAIL = 16;
	/** @var int Notification about a friend suggestion */
	public const SUGGEST = 32;
	/** @var int Notification about being tagged in a post */
	public const TAG_SELF = 128;
	/** @var int Notification about getting poked/prodded/etc. (Obsolete) */
	public const POKE = 512;
	/** @var int Notification about either a contact had posted something directly or the contact is a mentioned group */
	public const SHARE = 1024;

	/** @var int Global System notifications */
	public const SYSTEM = 32768;
}
