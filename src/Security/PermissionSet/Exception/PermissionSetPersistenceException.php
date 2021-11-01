<?php

namespace Friendica\Security\PermissionSet\Exception;

use Throwable;

class PermissionSetPersistenceException extends \RuntimeException
{
	public function __construct($message = "", Throwable $previous = null)
	{
		parent::__construct($message, 500, $previous);
	}
}
