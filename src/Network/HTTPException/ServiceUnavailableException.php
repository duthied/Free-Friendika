<?php

namespace Friendica\Network\HTTPException;

use Friendica\Network\HTTPException;

class ServiceUnavailableException extends HTTPException
{
	protected $code = 503;
}
