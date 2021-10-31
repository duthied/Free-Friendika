<?php

namespace Friendica\Core\Config\Exception;

use Throwable;

class ConfigFileException extends \RuntimeException
{
	public function __construct($message = "", $code = 0, Throwable $previous = null)
	{
		parent::__construct($message, 500, $previous);
	}
}
