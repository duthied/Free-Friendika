<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Object\Api\Mastodon\InstanceV2;

use Friendica\BaseDataTransferObject;

/**
 * Class Thumbnail
 *
 * @see https://docs.joinmastodon.org/entities/Instance/
 */
class Thumbnail extends BaseDataTransferObject
{
	/** @var string (URL) */
	protected $url;

	/**
	 * @param string $url
	 */
	public function __construct(string $url)
	{
		$this->url = $url;
	}
}
