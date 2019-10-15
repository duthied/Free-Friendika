<?php

namespace Friendica\Network\HTTPException;

use Friendica\Network\HTTPException;

class UnauthorizedException extends HTTPException
{
	protected $code = 401;
}
