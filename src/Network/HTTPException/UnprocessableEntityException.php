<?php

namespace Friendica\Network\HTTPException;

use Friendica\Network\HTTPException;

class UnprocessableEntityException extends HTTPException
{
	protected $code = 422;
}
