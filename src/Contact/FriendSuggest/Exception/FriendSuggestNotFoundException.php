<?php

namespace Friendica\Contact\FriendSuggest\Exception;

class FriendSuggestNotFoundException extends \OutOfBoundsException
{
	public function __construct($message = "", \Throwable $previous = null)
	{
		parent::__construct($message, 404, $previous);
	}
}
