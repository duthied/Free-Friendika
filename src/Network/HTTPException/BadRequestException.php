<?php

namespace Friendica\Network\HTTPException;

use Friendica\Network\HTTPException;

class BadRequestException extends HTTPException
{
	protected $code = 400;
}
