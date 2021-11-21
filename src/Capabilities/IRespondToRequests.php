<?php

namespace Friendica\Capabilities;

interface IRespondToRequests
{
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
	 * Returns all set headers during the module execution
	 *
	 * @return string[]
	 */
	public function getHeaders(): array;

	/**
	 * Returns the output of the module (mixed content possible)
	 *
	 * @return mixed
	 */
	public function getContent();

	/**
	 * Returns the response type
	 *
	 * @return string
	 */
	public function getType(): string;
}
