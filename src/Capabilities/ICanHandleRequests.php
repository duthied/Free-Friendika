<?php

namespace Friendica\Capabilities;

use Friendica\Network\HTTPException;
use Psr\Http\Message\ResponseInterface;

/**
 * This interface provides the capability to handle requests from clients and returns the desired outcome
 */
interface ICanHandleRequests
{
	/**
	 * @param array $request The $_REQUEST content (including content from the PHP input stream)
	 *
	 * @return ResponseInterface responding to the request handling
	 *
	 * @throws HTTPException\InternalServerErrorException
	 */
	public function run(array $request = []): ResponseInterface;
}
