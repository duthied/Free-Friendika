<?php

namespace Friendica\Contact\LocalRelationship\Exception;

class LocalRelationshipPersistenceException extends \RuntimeException
{
	public function __construct($message = '', \Throwable $previous = null)
	{
		parent::__construct($message, 500, $previous);
	}
}
