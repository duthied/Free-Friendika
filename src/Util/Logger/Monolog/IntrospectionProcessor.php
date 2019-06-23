<?php

namespace Friendica\Util\Logger\Monolog;

use Friendica\Util\Introspection;
use Monolog\Logger;
use Monolog\Processor\ProcessorInterface;

/**
 * Injects line/file//function where the log message came from
 */
class IntrospectionProcessor implements ProcessorInterface
{
	private $level;

	private $introspection;

	/**
	 * @param Introspection $introspection Holds the Introspection of the current call
	 * @param string|int    $level         The minimum logging level at which this Processor will be triggered
	 */
	public function __construct(Introspection $introspection, $level = Logger::DEBUG)
	{
		$this->level = Logger::toMonologLevel($level);
		$introspection->addClasses(array('Monolog\\'));
		$this->introspection = $introspection;
	}

	public function __invoke(array $record)
	{
		// return if the level is not high enough
		if ($record['level'] < $this->level) {
			return $record;
		}
		// we should have the call source now
		$record['extra'] = array_merge(
			$record['extra'],
			$this->introspection->getRecord()
		);

		return $record;
	}
}
