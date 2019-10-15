<?php

namespace Friendica\Network\HTTPException;

use Friendica\Network\HTTPException;

class ImATeapotException extends HTTPException
{
	protected $code = 418;
	var $httpdesc = "I'm A Teapot";
}
