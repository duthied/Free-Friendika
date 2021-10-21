<?php

namespace Friendica\Contact\Introduction\Exception;

class IntroductionNotFoundException extends \OutOfBoundsException
{
	public function __construct($message = "", \Throwable $previous = null)
	{
		parent::__construct($message, 404, $previous);
	}
}
