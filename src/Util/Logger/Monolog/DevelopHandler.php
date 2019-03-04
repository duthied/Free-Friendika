<?php

namespace Friendica\Util\Logger\Monolog;

use Monolog\Handler;
use Monolog\Logger;

/**
 * Simple handler for Friendica developers to use for deeper logging
 *
 * If you want to debug only interactions from your IP or the IP of a remote server for federation debug,
 * you'll use Logger::develop() for the duration of your work, and you clean it up when you're done before submitting your PR.
 */
class DevelopHandler extends Handler\AbstractHandler
{
	/**
	 * @var string The IP of the developer who wants to debug
	 */
	private $developerIp;

	/**
	 * @param string $developerIp  The IP of the developer who wants to debug
	 * @param int    $level        The minimum logging level at which this handler will be triggered
	 * @param bool   $bubble       Whether the messages that are handled can bubble up the stack or not
	 */
	public function __construct($developerIp, $level = Logger::DEBUG, $bubble = true)
	{
		parent::__construct($level, $bubble);

		$this->developerIp = $developerIp;
	}

	/**
	 * {@inheritdoc}
	 */
	public function handle(array $record)
	{
		if (!$this->isHandling($record)) {
			return false;
		}

		/// Just in case the remote IP is the same as the developer IP log the output
		if (!is_null($this->developerIp) && $_SERVER['REMOTE_ADDR'] != $this->developerIp)
		{
			return false;
		}

		return false === $this->bubble;
	}
}
