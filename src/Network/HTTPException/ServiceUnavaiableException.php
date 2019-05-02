<?php

namespace Friendica\Network\HTTPException;

use Friendica\Network\HTTPException;

class ServiceUnavaiableException extends HTTPException
{
	protected $code = 503;
}
