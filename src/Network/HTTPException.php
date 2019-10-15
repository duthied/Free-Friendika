<?php

/**
 * Throwable exceptions to return HTTP status code
 *
 * This list of Exception has been extracted from
 * here http://racksburg.com/choosing-an-http-status-code/
 */

namespace Friendica\Network;

use Exception;

abstract class HTTPException extends Exception
{
	public $httpdesc = '';

	public function __construct($message = '', Exception $previous = null)
	{
		parent::__construct($message, $this->code, $previous);

		if (empty($this->httpdesc)) {
			$classname = str_replace('Exception', '', str_replace('Friendica\Network\HTTPException\\', '', get_class($this)));
			$this->httpdesc = preg_replace("|([a-z])([A-Z])|",'$1 $2', $classname);
		}
	}
}
