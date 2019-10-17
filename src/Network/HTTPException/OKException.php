<?php

namespace Friendica\Network\HTTPException;

use Friendica\Network\HTTPException;

class OKException extends HTTPException
{
	protected $code = 200;
}
