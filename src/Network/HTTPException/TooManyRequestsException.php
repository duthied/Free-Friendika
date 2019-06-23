<?php

namespace Friendica\Network\HTTPException;

use Friendica\Network\HTTPException;

class TooManyRequestsException extends HTTPException
{
	protected $code = 429;
}
