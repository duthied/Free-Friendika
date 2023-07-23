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

namespace Friendica\Core\Logger\Factory;

use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\Hooks\Capability\ICanCreateInstances;
use Friendica\Core\Logger\Capability\LogChannel;
use Friendica\Core\Logger\Type\ProfilerLogger as ProfilerLoggerClass;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Throwable;

/**
 * The logger factory for the core logging instances
 */
class Logger
{
	/** @var string The channel */
	protected $channel;

	public function __construct(string $channel = LogChannel::DEFAULT)
	{
		$this->channel = $channel;
	}

	public function create(ICanCreateInstances $instanceCreator, IManageConfigValues $config, Profiler $profiler): LoggerInterface
	{
		if (empty($config->get('system', 'debugging') ?? false)) {
			return new NullLogger();
		}

		$name = $config->get('system', 'logger_config') ?? '';

		try {
			/** @var LoggerInterface $logger */
			$logger = $instanceCreator->create(LoggerInterface::class, $name, [$this->channel]);
			if ($config->get('system', 'profiling') ?? false) {
				return new ProfilerLoggerClass($logger, $profiler);
			} else {
				return $logger;
			}
		} catch (Throwable $e) {
			// No logger ...
			return new NullLogger();
		}
	}
}
