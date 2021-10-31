<?php

namespace Friendica\Core\Lock\Exception;

use Throwable;

class LockPersistenceException extends \RuntimeException
{
	public function __construct($message = "", Throwable $previous = null)
	{
		parent::__construct($message, 500, $previous);
	}
}
