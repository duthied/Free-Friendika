<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Core\Config\Model;

use Friendica\Core\Config\Capability\ISetConfigValuesTransactionally;
use Friendica\Core\Config\Exception\ConfigPersistenceException;
use Friendica\Core\Config\ValueObject\Cache;

/**
 * Transaction class for configurations, which sets values into a temporary buffer until "save()" is called
 */
class ConfigTransaction implements ISetConfigValuesTransactionally
{
	/** @var DatabaseConfig */
	protected $config;
	/** @var Cache */
	protected $setCache;
	/** @var Cache */
	protected $delCache;
	/** @var bool field to check if something is to save */
	protected $changedConfig = false;

	public function __construct(DatabaseConfig $config)
	{
		$this->config   = $config;
		$this->setCache = new Cache();
		$this->delCache = new Cache();
	}

	/** {@inheritDoc} */
	public function set(string $cat, string $key, $value): ISetConfigValuesTransactionally
	{
		$this->setCache->set($cat, $key, $value, Cache::SOURCE_DATA);
		$this->changedConfig = true;

		return $this;
	}


	/** {@inheritDoc} */
	public function delete(string $cat, string $key): ISetConfigValuesTransactionally
	{
		$this->delCache->set($cat, $key, true, Cache::SOURCE_DATA);
		$this->changedConfig = true;

		return $this;
	}

	/** {@inheritDoc} */
	public function commit(): void
	{
		// If nothing changed, just do nothing :)
		if (!$this->changedConfig) {
			return;
		}

		try {
			$this->config->setAndSave($this->setCache, $this->delCache);
			$this->setCache = new Cache();
			$this->delCache = new Cache();
		} catch (\Exception $e) {
			throw new ConfigPersistenceException('Cannot save config', $e);
		}
	}
}
