<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Object\Api\Mastodon\InstanceV2;

use Friendica\BaseDataTransferObject;

/**
 * Class FriendicaExtensions
 *
 * Friendica specific additional fields on the Instance V2 object
 *
 * @see https://docs.joinmastodon.org/entities/Instance/
 */
class FriendicaExtensions extends BaseDataTransferObject
{
	/** @var string */
	protected $version;
	/** @var string */
	protected $codename;
	/** @var int */
	protected $db_version;

	/**
	 * @param string $version
	 * @param string $codename
	 * @param int $db_version
	 */
	public function __construct(string $version, string $codename, int $db_version)
	{
		$this->version    = $version;
		$this->codename   = $codename;
		$this->db_version = $db_version;
	}
}
