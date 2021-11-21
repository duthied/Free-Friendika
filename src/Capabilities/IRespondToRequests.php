<?php

namespace Friendica\Capabilities;

interface IRespondToRequests
{
	const TYPE_CONTENT     = 'content';
	const TYPE_RAW_CONTENT = 'rawContent';
	const TYPE_POST        = 'post';
	const TYPE_PUT         = 'put';
	const TYPE_DELETE      = 'delete';
	const TYPE_PATCH       = 'patch';

	const ALLOWED_TYPES = [
		self::TYPE_CONTENT,
		self::TYPE_RAW_CONTENT,
		self::TYPE_POST,
		self::TYPE_PUT,
		self::TYPE_DELETE,
		self::TYPE_PATCH,
	];

	/**
	 * Returns all set headers during the module execution
	 *
	 * @return string[][]
	 */
	public function getHeaders(): array;

	/**
	 * Returns the output of the module
	 *
	 * @return string
	 */
	public function getContent(): string;

	/**
	 * Returns the response type of the current request
	 *
	 * @return string
	 */
	public function getTyp(): string;
}
