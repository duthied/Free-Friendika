<?php

namespace Friendica\Network\HTTPException;

use Friendica\Network\HTTPException;

class InternalServerErrorException extends HTTPException
{
	protected $code = 500;
}
