<?php

namespace Friendica\Util;

use Friendica\Core;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Log\LoggerInterface;

/**
 * A class to store profiling data
 * It can handle different logging data for specific functions or global performance measures
 *
 * It stores the data as log entries (@see LoggerInterface )
 */
class Profiler implements ContainerInterface
{
	/**
	 * @var array The global performance array
	 */
	private $performance;
	/**
	 * @var array The function specific callstack
	 */
	private $callstack;
	/**
	 * @var bool True, if the Profiler is enabled
	 */
	private $enabled;
	/**
	 * @var bool True, if the Profiler should measure the whole rendertime including functions
	 */
	private $rendertime;

	/**
	 * @var LoggerInterface The profiler logger
	 */
	private $logger;

	/**
	 * @param LoggerInterface $logger The profiler logger
	 * @param bool $enabled           True, if the Profiler is enabled
	 * @param bool $renderTime        True, if the Profiler should measure the whole rendertime including functions
	 */
	public function __construct(LoggerInterface $logger, $enabled = false, $renderTime = false)
	{
		$this->enabled = $enabled;
		$this->rendertime = $renderTime;
		$this->logger = $logger;
		$this->performance = [];
		$this->callstack   = [];
	}

	/**
	 * Saves a timestamp for a value - f.e. a call
	 * Necessary for profiling Friendica
	 *
	 * @param int $timestamp the Timestamp
	 * @param string $value A value to profile
	 */
	public function saveTimestamp($timestamp, $value)
	{
		if (!$this->enabled) {
			return;
		}

		$duration = (float) (microtime(true) - $timestamp);

		if (!isset($this->performance[$value])) {
			// Prevent ugly E_NOTICE
			$this->performance[$value] = 0;
		}

		$this->performance[$value] += (float) $duration;
		$this->performance['marktime'] += (float) $duration;

		$callstack = Core\System::callstack();

		if (!isset($this->callstack[$value][$callstack])) {
			// Prevent ugly E_NOTICE
			$this->callstack[$value][$callstack] = 0;
		}

		$this->callstack[$value][$callstack] += (float) $duration;
	}

	/**
	 * Resets the performance and callstack profiling
	 *
	 * @param bool $performance If true, reset the performance (Default true)
	 * @param bool $callstack   If true, reset the callstack (Default true)
	 */
	public function reset($performance = true, $callstack = true)
	{
		if ($performance) {
			$this->performance = [];
			$this->performance['start'] = microtime(true);
			$this->performance['database'] = 0;
			$this->performance['database_write'] = 0;
			$this->performance['cache'] = 0;
			$this->performance['cache_write'] = 0;
			$this->performance['network'] = 0;
			$this->performance['file'] = 0;
			$this->performance['rendering'] = 0;
			$this->performance['parser'] = 0;
			$this->performance['marktime'] = 0;
			$this->performance['markstart'] = microtime(true);
		}

		if ($callstack) {
			$this->callstack['database'] = [];
			$this->callstack['database_write'] = [];
			$this->callstack['cache'] = [];
			$this->callstack['cache_write'] = [];
			$this->callstack['network'] = [];
			$this->callstack['file'] = [];
			$this->callstack['rendering'] = [];
			$this->callstack['parser'] = [];
		}
	}

	/**
	 * Save the current profiling data to a log entry
	 *
	 * @param string $message Additional message for the log
	 */
	public function saveLog($message)
	{
		// Write down the performance values into the log
		if ($this->enabled) {
			$duration = microtime(true)-$this->get('start');
			$this->logger->info(
				$message,
				[
					'module' => 'api',
					'action' => 'call',
					'database_read' => round($this->get('database') - $this->get('database_write'), 3),
					'database_write' => round($this->get('database_write'), 3),
					'cache_read' => round($this->get('cache'), 3),
					'cache_write' => round($this->get('cache_write'), 3),
					'network_io' => round($this->get('network'), 2),
					'file_io' => round($this->get('file'), 2),
					'other_io' => round($duration - ($this->get('database')
							+ $this->get('cache') + $this->get('cache_write')
							+ $this->get('network') + $this->get('file')), 2),
					'total' => round($duration, 2)
				]
			);

			$o = '';
			if ($this->rendertime) {
				if (isset($this->callstack["database"])) {
					$o .= "\nDatabase Read:\n";
					foreach ($this->callstack["database"] as $func => $time) {
						$time = round($time, 3);
						if ($time > 0) {
							$o .= $func.": ".$time."\n";
						}
					}
				}
				if (isset($this->callstack["database_write"])) {
					$o .= "\nDatabase Write:\n";
					foreach ($this->callstack["database_write"] as $func => $time) {
						$time = round($time, 3);
						if ($time > 0) {
							$o .= $func.": ".$time."\n";
						}
					}
				}
				if (isset($this->callstack["dache"])) {
					$o .= "\nCache Read:\n";
					foreach ($this->callstack["dache"] as $func => $time) {
						$time = round($time, 3);
						if ($time > 0) {
							$o .= $func.": ".$time."\n";
						}
					}
				}
				if (isset($this->callstack["dache_write"])) {
					$o .= "\nCache Write:\n";
					foreach ($this->callstack["dache_write"] as $func => $time) {
						$time = round($time, 3);
						if ($time > 0) {
							$o .= $func.": ".$time."\n";
						}
					}
				}
				if (isset($this->callstack["network"])) {
					$o .= "\nNetwork:\n";
					foreach ($this->callstack["network"] as $func => $time) {
						$time = round($time, 3);
						if ($time > 0) {
							$o .= $func.": ".$time."\n";
						}
					}
				}
			}

			$this->logger->info(
				$message . ": " . sprintf(
					"DB: %s/%s, Cache: %s/%s, Net: %s, I/O: %s, Other: %s, Total: %s".$o,
					number_format($this->get('database') - $this->get('database_write'), 2),
					number_format($this->get('database_write'), 2),
					number_format($this->get('cache'), 2),
					number_format($this->get('cache_write'), 2),
					number_format($this->get('network'), 2),
					number_format($this->get('file'), 2),
					number_format($duration - ($this->get('database')
							+ $this->get('cache') + $this->get('cache_write')
							+ $this->get('network') + $this->get('file')), 2),
					number_format($duration, 2)
				)
			);
		}
	}

	/**
	 * Finds an entry of the container by its identifier and returns it.
	 *
	 * @param string $id Identifier of the entry to look for.
	 *
	 * @throws NotFoundExceptionInterface  No entry was found for **this** identifier.
	 * @throws ContainerExceptionInterface Error while retrieving the entry.
	 *
	 * @return int Entry.
	 */
	public function get($id)
	{
		if (!$this->has($id)) {
			return 0;
		} else {
			return $this->performance[$id];
		}
	}

	/**
	 * Returns true if the container can return an entry for the given identifier.
	 * Returns false otherwise.
	 *
	 * `has($id)` returning true does not mean that `get($id)` will not throw an exception.
	 * It does however mean that `get($id)` will not throw a `NotFoundExceptionInterface`.
	 *
	 * @param string $id Identifier of the entry to look for.
	 *
	 * @return bool
	 */
	public function has($id)
	{
		return isset($this->performance[$id]);
	}
}
