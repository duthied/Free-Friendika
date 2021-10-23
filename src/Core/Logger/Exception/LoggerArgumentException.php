<?php

namespace Friendica\Core\Logger\Exception;

use Throwable;

class LoggerArgumentException extends \InvalidArgumentException
{
	public function __construct($message = "", Throwable $previous = null)
	{
		parent::__construct($message, 500, $previous);
	}
}
