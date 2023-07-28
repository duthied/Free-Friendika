<?php
/**
 * @copyright Copyright (C) 2010-2023, the Friendica project
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

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
