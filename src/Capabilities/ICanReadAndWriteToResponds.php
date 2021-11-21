<?php

namespace Friendica\Capabilities;

use Friendica\Network\HTTPException\InternalServerErrorException;

interface ICanReadAndWriteToResponds extends IRespondToRequests
{
	/**
	 * Adds a header entry to the module response
	 *
	 * @param string $key
	 * @param string $value
	 */
	public function addHeader(string $key, string $value);

	/**
	 * Adds output content to the module response
	 *
	 * @param string $content
	 */
	public function addContent(string $content);

	/**
	 * Sets the response type of the current request
	 *
	 * @param string $type
	 *
	 * @throws InternalServerErrorException
	 */
	public function setType(string $type);
}
