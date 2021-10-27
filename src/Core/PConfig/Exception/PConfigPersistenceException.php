<?php

namespace Friendica\Core\PConfig\Exception;

use Throwable;

class PConfigPersistenceException extends \RuntimeException
{
	public function __construct($message = "", Throwable $previous = null)
	{
		parent::__construct($message, 500, $previous);
	}
}
