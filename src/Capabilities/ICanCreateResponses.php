<?php

namespace Friendica\Capabilities;

use Friendica\Network\HTTPException\InternalServerErrorException;

interface ICanCreateResponses extends IRespondToRequests
{
	/**
	 * Adds a header entry to the module response
	 *
	 * @param string $header
	 * @param string|null $key
	 */
	public function setHeader(string $header, ?string $key = null): void;

	/**
	 * Adds output content to the module response
	 *
	 * @param mixed $content
	 */
	public function addContent($content): void;

	/**
	 * Sets the response type of the current request
	 *
	 * @param string $type
	 * @param string|null $content_type (optional) overrides the direct content_type, otherwise set the default one
	 *
	 * @throws InternalServerErrorException
	 */
	public function setType(string $type, ?string $content_type = null): void;
}
