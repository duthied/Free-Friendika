<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Object\Api\Mastodon;

use Friendica\BaseDataTransferObject;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\Protocol;
use Friendica\Database\Database;
use Friendica\DI;

/**
 * Class Stats
 *
 * @see https://docs.joinmastodon.org/api/entities/#stats
 */
class Stats extends BaseDataTransferObject
{
	/** @var int */
	protected $user_count = 0;
	/** @var int */
	protected $status_count = 0;
	/** @var int */
	protected $domain_count = 0;

	public function __construct(IManageConfigValues $config, Database $database)
	{
		if (!empty($config->get('system', 'nodeinfo'))) {
			$this->user_count   = intval(DI::keyValue()->get('nodeinfo_total_users'));
			$this->status_count = (int) DI::keyValue()->get('nodeinfo_local_posts') + (int) DI::keyValue()->get('nodeinfo_local_comments');
			$this->domain_count = $database->count('gserver', ["`network` in (?, ?) AND NOT `failed` AND NOT `blocked`", Protocol::DFRN, Protocol::ACTIVITYPUB]);
		}
	}
}
