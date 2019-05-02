<?php

namespace Friendica\Network\HTTPException;

use Friendica\Network\HTTPException;

class LenghtRequiredException extends HTTPException
{
	protected $code = 411;
}
