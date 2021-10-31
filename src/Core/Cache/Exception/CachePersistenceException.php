<?php

namespace Friendica\Core\Cache\Exception;

use Throwable;

class CachePersistenceException extends \RuntimeException
{
	public function __construct($message = "", Throwable $previous = null)
	{
		parent::__construct($message, 500, $previous);
	}
}
