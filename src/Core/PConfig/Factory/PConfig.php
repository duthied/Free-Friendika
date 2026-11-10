<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Core\PConfig\Factory;

use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\Hooks\Capability\ICanCreateInstances;
use Friendica\Core\PConfig\Capability\IManagePersonalConfigValues;

class PConfig
{
	public function create(ICanCreateInstances $instanceCreator, IManageConfigValues $config): IManagePersonalConfigValues
	{
		$strategy = $config->get('system', 'config_adapter');

		/** @var IManagePersonalConfigValues */
		return $instanceCreator->create(IManagePersonalConfigValues::class, $strategy);
	}
}
