<?php

namespace Friendica\Security\PermissionSet\Exception;

use Exception;

class PermissionSetNotFoundException extends \RuntimeException
{
	public function __construct($message = '', Exception $previous = null)
	{
		parent::__construct($message, 404, $previous);
	}
}
