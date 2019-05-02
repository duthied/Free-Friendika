<?php

namespace Friendica\Network\HTTPException;

use Friendica\Network\HTTPException;

class NotFoundException extends HTTPException
{
	protected $code = 404;
}
