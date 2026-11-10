<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Object\Api\Mastodon\Status;

use Friendica\BaseDataTransferObject;

/**
 * Class FriendicaVisibility
 *
 * Fields for the user's visibility settings on a post if they own that post
 *
 * @see https://docs.joinmastodon.org/entities/status
 */
class FriendicaVisibility extends BaseDataTransferObject
{
	/** @var array */
	protected $allow_cid;
	/** @var array */
	protected $deny_cid;
	/** @var array */
	protected $allow_gid;
	/** @var array */
	protected $deny_gid;

	public function __construct(array $allow_cid, array $deny_cid, array $allow_gid, array $deny_gid)
	{
		$this->allow_cid = $allow_cid;
		$this->deny_cid  = $deny_cid;
		$this->allow_gid = $allow_gid;
		$this->deny_gid  = $deny_gid;
	}
}
