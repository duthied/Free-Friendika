<?php

namespace Friendica\Profile\ProfileField\Exception;

use Throwable;

class ProfileFieldPersistenceException extends \RuntimeException
{
	public function __construct($message = "", Throwable $previous = null)
	{
		parent::__construct($message, 500, $previous);
	}
}
