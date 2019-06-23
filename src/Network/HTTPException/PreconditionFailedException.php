<?php

namespace Friendica\Network\HTTPException;

use Friendica\Network\HTTPException;

class PreconditionFailedException extends HTTPException
{
	protected $code = 412;
}
