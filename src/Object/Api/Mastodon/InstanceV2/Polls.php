<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Object\Api\Mastodon\InstanceV2;

use Friendica\BaseDataTransferObject;

/**
 * Class Polls
 *
 * @see https://docs.joinmastodon.org/entities/Instance/
 */
class Polls extends BaseDataTransferObject
{
	/** @var int */
	protected $max_options = 0;
	/** @var int */
	protected $max_characters_per_option = 0;
	/** @var int */
	protected $min_expiration = 0;
	/** @var int */
	protected $max_expiration = 0;
}
