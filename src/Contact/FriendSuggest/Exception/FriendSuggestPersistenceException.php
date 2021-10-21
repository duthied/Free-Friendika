<?php

namespace Friendica\Contact\FriendSuggest\Exception;

class FriendSuggestPersistenceException extends \RuntimeException
{
	public function __construct($message = "", \Throwable $previous = null)
	{
		parent::__construct($message, 500, $previous);
	}
}
