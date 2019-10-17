<?php

namespace Friendica\Network\HTTPException;

use Friendica\Network\HTTPException;

class NoContentException extends HTTPException
{
	protected $code = 204;
}
