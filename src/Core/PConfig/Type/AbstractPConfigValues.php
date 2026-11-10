<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Core\PConfig\Type;

use Friendica\Core\PConfig\Repository;
use Friendica\Core\PConfig\ValueObject\Cache;
use Friendica\Core\PConfig\Capability\IManagePersonalConfigValues;

/**
 * This class is responsible for the user-specific configuration values in Friendica
 * The values are set through the Config-DB-Table (per Config-DB-model @see Repository\PConfig)
 *
 * The configuration cache (@see Cache) is used for temporary caching of database calls. This will
 * increase the performance.
 */
abstract class AbstractPConfigValues implements IManagePersonalConfigValues
{
	public const NAME = '';

	/**
	 * @var Cache
	 */
	protected $configCache;

	/**
	 * @var Repository\PConfig
	 */
	protected $configModel;

	/**
	 * @param Cache              $configCache The configuration cache
	 * @param Repository\PConfig $configRepo  The configuration model
	 */
	public function __construct(Cache $configCache, Repository\PConfig $configRepo)
	{
		$this->configCache = $configCache;
		$this->configModel = $configRepo;
	}

	/**
	 * Returns the Config Cache
	 *
	 * @return Cache
	 */
	public function getCache(): Cache
	{
		return $this->configCache;
	}
}
