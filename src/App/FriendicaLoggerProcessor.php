<?php

namespace Friendica\App;

use Monolog\Processor\ProcessorInterface;

/**
 * Includes the Friendica specific process_id of "app->process_id"
 *
 * @package Friendica\App
 */
class FriendicaLoggerProcessor implements ProcessorInterface
{
	/**
	 * @var string the ID of the current Friendica process
	 */
	private $processId = null;

	/**
	 * Set the process id based on the Application instance
	 *
	 * @param string $processId the process id
	 */
	public function setProcessId($processId)
	{
		if (!isset($this->processId) || $this->processId == '')
		{
			$this->processId = $processId;
		}
	}

	public function __construct()
	{
		$this->processId = session_id();
	}

	public function __invoke(array $records)
	{
		$records['extra'] = array_merge(
			$records['extra'],
			[
				'app_id' => $this->processId,
			]
		);

		return $records;
	}
}
