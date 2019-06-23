<?php

namespace Friendica\Network\HTTPException;

use Friendica\Network\HTTPException;

class UnsupportedMediaTypeException extends HTTPException
{
	protected $code = 415;
}
