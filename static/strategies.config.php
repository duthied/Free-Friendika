<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

use Friendica\Core\Cache;
use Friendica\Core\Hooks\Util\StrategiesFileManager;
use Friendica\Core\Logger\Type;
use Friendica\Core\KeyValueStorage;
use Friendica\Core\PConfig;
use Psr\Log;

return [
	Log\LoggerInterface::class => [
		Log\NullLogger::class    => [StrategiesFileManager::STRATEGY_DEFAULT_KEY],
		Type\SyslogLogger::class => [Type\SyslogLogger::NAME],
		Type\StreamLogger::class => [Type\StreamLogger::NAME],
	],
	Cache\Capability\ICanCache::class => [
		Cache\Type\DatabaseCache::class  => [Cache\Type\DatabaseCache::NAME, StrategiesFileManager::STRATEGY_DEFAULT_KEY],
		Cache\Type\APCuCache::class      => [Cache\Type\APCuCache::NAME],
		Cache\Type\MemcacheCache::class  => [Cache\Type\MemcacheCache::NAME],
		Cache\Type\MemcachedCache::class => [Cache\Type\MemcachedCache::NAME],
		Cache\Type\RedisCache::class     => [Cache\Type\RedisCache::NAME],
	],
	KeyValueStorage\Capability\IManageKeyValuePairs::class => [
		KeyValueStorage\Type\DBKeyValueStorage::class => [KeyValueStorage\Type\DBKeyValueStorage::NAME, StrategiesFileManager::STRATEGY_DEFAULT_KEY],
	],
	PConfig\Capability\IManagePersonalConfigValues::class => [
		PConfig\Type\JitPConfig::class     => [PConfig\Type\JitPConfig::NAME],
		PConfig\Type\PreloadPConfig::class => [PConfig\Type\PreloadPConfig::NAME, StrategiesFileManager::STRATEGY_DEFAULT_KEY],
	],
];
