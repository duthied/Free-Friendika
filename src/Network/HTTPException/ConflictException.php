<?php

namespace Friendica\Network\HTTPException;

use Friendica\Network\HTTPException;

class ConflictException extends HTTPException
{
	protected $code = 409;
}
