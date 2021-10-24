<?php

namespace Friendica\Core\Worker\Exception;

use Throwable;

class ProcessPersistenceException extends \RuntimeException
{
	public function __construct($message = "", Throwable $previous = null)
	{
		parent::__construct($message, 500, $previous);
	}
}
