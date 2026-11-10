<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Object\Api\Mastodon;

use Friendica\BaseDataTransferObject;
use Friendica\Util\DateTimeFormat;

/**
 * Class ExtendedDescription
 *
 * @see https://docs.joinmastodon.org/entities/ExtendedDescription/
 */
class ExtendedDescription extends BaseDataTransferObject
{
	/** @var string (Datetime) */
	protected $updated_at;
	/** @var string */
	protected $content;

	public function __construct(\DateTime $updated_at, string $content)
	{
		$this->updated_at = $updated_at->format(DateTimeFormat::JSON);
		$this->content    = $content;
	}
}
