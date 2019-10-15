<?php

namespace Friendica\Network\HTTPException;

use Friendica\Network\HTTPException;

class NotImplementedException extends HTTPException
{
	protected $code = 501;
}
