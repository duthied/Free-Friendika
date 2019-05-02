<?php

namespace Friendica\Network\HTTPException;

use Friendica\Network\HTTPException;

class AcceptedException extends HTTPException
{
	protected $code = 202;
}
