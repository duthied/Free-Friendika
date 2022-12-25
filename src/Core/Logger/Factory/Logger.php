<?php
/**
 * @copyright Copyright (C) 2010-2022, the Friendica project
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
use Friendica\Core;
use Friendica\Core\Logger\Capabilities\IHaveCallIntrospections;
use Friendica\Core\Logger\Exception\LogLevelException;
use Friendica\Database\Database;
use Friendica\Network\HTTPException\InternalServerErrorException;
use Friendica\Util\FileSystem;
use Friendica\Core\Logger\Type\ProfilerLogger;
use Friendica\Core\Logger\Type\StreamLogger;
use Friendica\Core\Logger\Type\SyslogLogger;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Psr\Log\NullLogger;

/**
 * A logger factory
 */
class Logger
{
	const DEV_CHANNEL = 'dev';

	/** @var string The log-channel (app, worker, ...) */
	private $channel;

	public function __construct(string $channel, bool $includeAddon = true)
	{
		$this->channel = $channel;

		/// @fixme clean solution = Making Addon & Hook dynamic and load them inside the constructor, so there's no custom load logic necessary anymore
		if ($includeAddon) {
			Core\Addon::loadAddons();
			Core\Hook::loadHooks();
		}
	}

	/**
	 * Creates a new PSR-3 compliant logger instances
	 *
	 * @param Database            $database   The Friendica Database instance
	 * @param IManageConfigValues $config     The config
	 * @param Profiler            $profiler   The profiler of the app
	 * @param FileSystem          $fileSystem FileSystem utils
	 * @param string|null         $minLevel   (optional) Override minimum Loglevel to log
	 *
	 * @return LoggerInterface The PSR-3 compliant logger instance
	 */
	public function create(Database $database, IManageConfigValues $config, Profiler $profiler, FileSystem $fileSystem, IHaveCallIntrospections $introspection, ?string $minLevel = null): LoggerInterface
	{
		if (empty($config->get('system', 'debugging', false))) {
			$logger = new NullLogger();
			$database->setLogger($logger);
			return $logger;
		}

		$minLevel      = $minLevel ?? $config->get('system', 'loglevel');
		$loglevel      = self::mapLegacyConfigDebugLevel((string)$minLevel);

		$name = $config->get('system', 'logger_config', 'stream');

		switch ($name) {
			case 'syslog':
				try {
					$logger = new SyslogLogger($this->channel, $introspection, $loglevel, $config->get('system', 'syslog_flags', SyslogLogger::DEFAULT_FLAGS), $config->get('system', 'syslog_facility', SyslogLogger::DEFAULT_FACILITY));
				} catch (LogLevelException $exception) {
					// If there's a wrong config value for loglevel, try again with standard
					$logger = $this->create($database, $config, $profiler, $fileSystem,  $introspection, LogLevel::NOTICE);
					$logger->warning('Invalid loglevel set in config.', ['loglevel' => $loglevel]);
				} catch (\Throwable $e) {
					// No logger ...
					$logger = new NullLogger();
				}
				break;

			case 'stream':
			default:
				$data = [
					'name'          => $name,
					'channel'       => $this->channel,
					'introspection' => $introspection,
					'loglevel'      => $loglevel,
					'logger'        => null,
				];
				try {
					Core\Hook::callAll('logger_instance', $data);
				} catch (InternalServerErrorException $exception) {
					$data['logger'] = null;
				}

				if (($data['logger'] ?? null) instanceof LoggerInterface) {
					$logger = $data['logger'];
				}

				if (empty($logger)) {
					$stream = $config->get('system', 'logfile');
					// just add a stream in case it's either writable or not file
					if (!is_file($stream) || is_writable($stream)) {
						try {
							$logger = new StreamLogger($this->channel, $stream, $introspection, $fileSystem, $loglevel);
						} catch (LogLevelException $exception) {
							// If there's a wrong config value for loglevel, try again with standard
							$logger = $this->create($database, $config, $profiler, $fileSystem, $introspection, LogLevel::NOTICE);
							$logger->warning('Invalid loglevel set in config.', ['loglevel' => $loglevel]);
						} catch (\Throwable $t) {
							// No logger ...
							$logger = new NullLogger();
						}
					} else {
						try {
							$logger = new SyslogLogger($this->channel, $introspection, $loglevel);
						} catch (LogLevelException $exception) {
							// If there's a wrong config value for loglevel, try again with standard
							$logger = $this->create($database, $config, $profiler, $fileSystem, $introspection, LogLevel::NOTICE);
							$logger->warning('Invalid loglevel set in config.', ['loglevel' => $loglevel]);
						} catch (\Throwable $e) {
							// No logger ...
							$logger = new NullLogger();
						}
					}
				}
				break;
		}

		$profiling = $config->get('system', 'profiling', false);

		// In case profiling is enabled, wrap the ProfilerLogger around the current logger
		if (isset($profiling) && $profiling !== false) {
			$logger = new ProfilerLogger($logger, $profiler);
		}

		$database->setLogger($logger);
		return $logger;
	}

	/**
	 * Creates a new PSR-3 compliant develop logger
	 *
	 * If you want to debug only interactions from your IP or the IP of a remote server for federation debug,
	 * you'll use this logger instance for the duration of your work.
	 *
	 * It should never get filled during normal usage of Friendica
	 *
	 * @param IManageConfigValues $config     The config
	 * @param Profiler            $profiler   The profiler of the app
	 * @param FileSystem          $fileSystem FileSystem utils
	 *
	 * @return LoggerInterface The PSR-3 compliant logger instance
	 * @throws \Exception
	 */
	public static function createDev(IManageConfigValues $config, Profiler $profiler, FileSystem $fileSystem, IHaveCallIntrospections $introspection)
	{
		$debugging   = $config->get('system', 'debugging');
		$stream      = $config->get('system', 'dlogfile');
		$developerIp = $config->get('system', 'dlogip');

		if ((!isset($developerIp) || !$debugging) &&
			(!is_file($stream) || is_writable($stream))) {
			return new NullLogger();
		}

		$name = $config->get('system', 'logger_config', 'stream');

		switch ($name) {

			case 'syslog':
				$logger = new SyslogLogger(self::DEV_CHANNEL, $introspection, LogLevel::DEBUG);
				break;

			case 'stream':
			default:
				$data = [
					'name'          => $name,
					'channel'       => self::DEV_CHANNEL,
					'introspection' => $introspection,
					'loglevel'      => LogLevel::DEBUG,
					'logger'        => null,
				];
				try {
					Core\Hook::callAll('logger_instance', $data);
				} catch (InternalServerErrorException $exception) {
					$data['logger'] = null;
				}

				if (($data['logger'] ?? null) instanceof LoggerInterface) {
					return $data['logger'];
				}

				$logger = new StreamLogger(self::DEV_CHANNEL, $stream, $introspection, $fileSystem, LogLevel::DEBUG);
				break;
		}

		$profiling = $config->get('system', 'profiling', false);

		// In case profiling is enabled, wrap the ProfilerLogger around the current logger
		if (isset($profiling) && $profiling !== false) {
			$logger = new ProfilerLogger($logger, $profiler);
		}

		return $logger;
	}

	/**
	 * Mapping a legacy level to the PSR-3 compliant levels
	 *
	 * @see https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-3-logger-interface.md#5-psrlogloglevel
	 *
	 * @param string $level the level to be mapped
	 *
	 * @return string the PSR-3 compliant level
	 */
	private static function mapLegacyConfigDebugLevel(string $level): string
	{
		switch ($level) {
			// legacy WARNING
			case "0":
				return LogLevel::ERROR;
			// legacy INFO
			case "1":
				return LogLevel::WARNING;
			// legacy TRACE
			case "2":
				return LogLevel::NOTICE;
			// legacy DEBUG
			case "3":
				return LogLevel::INFO;
			// legacy DATA
			case "4":
			// legacy ALL
			case "5":
				return LogLevel::DEBUG;
			// default if nothing set
			default:
				return $level;
		}
	}
}
