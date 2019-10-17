<?php

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
