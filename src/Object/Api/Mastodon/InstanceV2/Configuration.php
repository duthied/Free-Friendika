<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Object\Api\Mastodon\InstanceV2;

use Friendica\BaseDataTransferObject;

/**
 * Class Configuration
 *
 * @see https://docs.joinmastodon.org/entities/Instance/
 */
class Configuration extends BaseDataTransferObject
{
	/** @var Accounts */
	protected $accounts;
	/** @var StatusesConfig */
	protected $statuses;
	/** @var MediaAttachmentsConfig */
	protected $media_attachments;
	/** @var Polls */
	protected $polls;

	/**
	 * @param StatusesConfig $statuses
	 * @param MediaAttachmentsConfig $media_attachments
	 */
	public function __construct(
		StatusesConfig $statuses,
		MediaAttachmentsConfig $media_attachments,
		Polls $polls,
		Accounts $accounts,
	) {
		$this->accounts          = $accounts;
		$this->statuses          = $statuses;
		$this->media_attachments = $media_attachments;
		$this->polls             = $polls;
	}
}
