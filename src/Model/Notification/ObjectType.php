<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Model\Notification;

/**
 * Enum for different otypes of the Notify
 */
class ObjectType
{
	public const PERSON = 'person';
	public const MAIL   = 'mail';
	public const ITEM   = 'item';
	public const INTRO  = 'intro';
}
