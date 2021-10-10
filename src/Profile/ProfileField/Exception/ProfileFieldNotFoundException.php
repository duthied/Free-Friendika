<?php

namespace Friendica\Profile\ProfileField\Exception;

use OutOfBoundsException;
use Throwable;

class ProfileFieldNotFoundException extends OutOfBoundsException
{
	public function __construct($message = "", Throwable $previous = null)
	{
		parent::__construct($message, 404, $previous);
	}
}
