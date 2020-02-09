<?php
/**
 * @copyright Copyright (C) 2020, Friendica
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

namespace Friendica\Util\Logger;

use Friendica\Core\System;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

/**
 * This Logger adds additional profiling data in case profiling is enabled.
 * It uses a predefined logger.
 */
class ProfilerLogger implements LoggerInterface
{
	/**
	 * The Logger of the current call
	 * @var LoggerInterface
	 */
	private $logger;

	/**
	 * The Profiler for the current call
	 * @var Profiler
	 */
	protected $profiler;

	/**
	 * ProfilerLogger constructor.
	 * @param LoggerInterface $logger   The Logger of the current call
	 * @param Profiler        $profiler The profiler of the current call
	 */
	public function __construct(LoggerInterface $logger, Profiler $profiler)
	{
		$this->logger = $logger;
		$this->profiler = $profiler;
	}

	/**
	 * {@inheritdoc}
	 */
	public function emergency($message, array $context = array())
	{
		$stamp1 = microtime(true);
		$this->logger->emergency($message, $context);
		$this->profiler->saveTimestamp($stamp1, 'file', System::callstack());
	}

	/**
	 * {@inheritdoc}
	 */
	public function alert($message, array $context = array())
	{
		$stamp1 = microtime(true);
		$this->logger->alert($message, $context);
		$this->profiler->saveTimestamp($stamp1, 'file', System::callstack());
	}

	/**
	 * {@inheritdoc}
	 */
	public function critical($message, array $context = array())
	{
		$stamp1 = microtime(true);
		$this->logger->critical($message, $context);
		$this->profiler->saveTimestamp($stamp1, 'file', System::callstack());
	}

	/**
	 * {@inheritdoc}
	 */
	public function error($message, array $context = array())
	{
		$stamp1 = microtime(true);
		$this->logger->error($message, $context);
		$this->profiler->saveTimestamp($stamp1, 'file', System::callstack());
	}

	/**
	 * {@inheritdoc}
	 */
	public function warning($message, array $context = array())
	{
		$stamp1 = microtime(true);
		$this->logger->warning($message, $context);
		$this->profiler->saveTimestamp($stamp1, 'file', System::callstack());
	}

	/**
	 * {@inheritdoc}
	 */
	public function notice($message, array $context = array())
	{
		$stamp1 = microtime(true);
		$this->logger->notice($message, $context);
		$this->profiler->saveTimestamp($stamp1, 'file', System::callstack());
	}

	/**
	 * {@inheritdoc}
	 */
	public function info($message, array $context = array())
	{
		$stamp1 = microtime(true);
		$this->logger->info($message, $context);
		$this->profiler->saveTimestamp($stamp1, 'file', System::callstack());
	}

	/**
	 * {@inheritdoc}
	 */
	public function debug($message, array $context = array())
	{
		$stamp1 = microtime(true);
		$this->logger->debug($message, $context);
		$this->profiler->saveTimestamp($stamp1, 'file', System::callstack());
	}

	/**
	 * {@inheritdoc}
	 */
	public function log($level, $message, array $context = array())
	{
		$stamp1 = microtime(true);
		$this->logger->log($level, $message, $context);
		$this->profiler->saveTimestamp($stamp1, 'file', System::callstack());
	}
}
