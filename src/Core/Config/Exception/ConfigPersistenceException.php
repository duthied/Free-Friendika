<?php

namespace Friendica\Core\Config\Exception;

use Throwable;

class ConfigPersistenceException extends \RuntimeException
{
	public function __construct($message = "", Throwable $previous = null)
	{
		parent::__construct($message, 500, $previous);
	}
}
