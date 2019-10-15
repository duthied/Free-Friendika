<?php

namespace Friendica\Network\HTTPException;

use Friendica\Network\HTTPException;

class ForbiddenException extends HTTPException
{
	protected $code = 403;
}
