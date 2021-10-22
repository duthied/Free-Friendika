<?php

namespace Friendica\Contact\Introduction\Exception;

class IntroductionPersistenceException extends \RuntimeException
{
	public function __construct($message = "", \Throwable $previous = null)
	{
		parent::__construct($message, 500, $previous);
	}
}
