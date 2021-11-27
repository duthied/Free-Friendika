<?php

namespace Friendica\Capabilities;

use Friendica\Network\HTTPException\InternalServerErrorException;
use Psr\Http\Message\ResponseInterface;

interface ICanCreateResponses
{
	/**
	 * This constant helps to find the specific return type of responses inside the headers array
	 */
	const X_HEADER = 'X-RESPONSE-TYPE';

	const TYPE_HTML = 'html';
	const TYPE_XML  = 'xml';
	const TYPE_JSON = 'json';
	const TYPE_ATOM = 'atom';
	const TYPE_RSS  = 'rss';

	const ALLOWED_TYPES = [
		self::TYPE_HTML,
		self::TYPE_XML,
		self::TYPE_JSON,
		self::TYPE_ATOM,
		self::TYPE_RSS
	];

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

	/**
	 * Creates a PSR-7 compliant interface
	 * @see https://www.php-fig.org/psr/psr-7/
	 *
	 * @return ResponseInterface
	 */
	public function generate(): ResponseInterface;
}
