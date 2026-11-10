<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Core\KeyValueStorage\Factory;

use Friendica\Core\Hooks\Capability\ICanCreateInstances;
use Friendica\Core\Hooks\Util\StrategiesFileManager;
use Friendica\Core\KeyValueStorage\Capability\IManageKeyValuePairs;

class KeyValueStorage
{
	public function create(ICanCreateInstances $instanceCreator): IManageKeyValuePairs
	{
		/** @var IManageKeyValuePairs */
		return $instanceCreator->create(IManageKeyValuePairs::class, StrategiesFileManager::STRATEGY_DEFAULT_KEY);
	}
}
