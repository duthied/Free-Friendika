<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Object\Api\Mastodon\InstanceV2;

use Friendica\BaseDataTransferObject;

/**
 * Class StatusConfig
 *
 * @see https://docs.joinmastodon.org/entities/Instance/
 */
class StatusesConfig extends BaseDataTransferObject
{
	/** @var int */
	protected $max_characters = 0;
	/** @var int */
	protected $max_media_attachments = 0;
	/** @var int */
	protected $characters_reserved_per_url = 0;

	/**
	 * @param int $max_characters
	 * @param int $max_media_attachments
	 * @param int $characters_reserved_per_url
	 */
	public function __construct(int $max_characters, int $max_media_attachments, int $characters_reserved_per_url)
	{
		$this->max_characters              = $max_characters;
		$this->max_media_attachments       = $max_media_attachments;
		$this->characters_reserved_per_url = $characters_reserved_per_url;
	}
}
