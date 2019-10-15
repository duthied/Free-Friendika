<?php

namespace Friendica\Network\HTTPException;

use Friendica\Network\HTTPException;

class NonAcceptableException extends HTTPException
{
	protected $code = 406;
}
