<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Object\Api\Mastodon\InstanceV2;

use Friendica\BaseDataTransferObject;

/**
 * Class Registrations
 *
 * @see https://docs.joinmastodon.org/entities/Instance/
 */
class Registrations extends BaseDataTransferObject
{
	/** @var bool */
	protected $enabled;
	/** @var bool */
	protected $approval_required;
	/** @var string|null */
	protected $message;
	/** @var string|null */
	protected $url;

	/**
	 * @param bool $enabled
	 * @param bool $approval_required
	 */
	public function __construct(bool $enabled, bool $approval_required)
	{
		$this->enabled           = $enabled;
		$this->approval_required = $approval_required;
	}
}
