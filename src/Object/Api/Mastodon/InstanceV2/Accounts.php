<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Object\Api\Mastodon\InstanceV2;

use Friendica\BaseDataTransferObject;

/**
 * Class Accounts
 *
 * @see https://docs.joinmastodon.org/entities/Instance/
 */
class Accounts extends BaseDataTransferObject
{
	/** @var int */
	protected $max_featured_tags = 0;
}
