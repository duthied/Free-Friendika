<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Capabilities;

use Friendica\Network\HTTPException\InternalServerErrorException;
use Psr\Http\Message\ResponseInterface;

interface ICanCreateResponses
{
	/**
	 * This constant helps to find the specific return type of responses inside the headers array
	 */
	public const X_HEADER = 'X-RESPONSE-TYPE';

	public const TYPE_HTML  = 'html';
	public const TYPE_XML   = 'xml';
	public const TYPE_JSON  = 'json';
	public const TYPE_ATOM  = 'atom';
	public const TYPE_RSS   = 'rss';
	public const TYPE_BLANK = 'blank';

	public const ALLOWED_TYPES = [
		self::TYPE_HTML,
		self::TYPE_XML,
		self::TYPE_JSON,
		self::TYPE_ATOM,
		self::TYPE_RSS,
		self::TYPE_BLANK,
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
	public function setType(string $type = ICanCreateResponses::TYPE_HTML, ?string $content_type = null): void;

	/**
	 * Sets the status and the reason for the response
	 *
	 * @param int $status The HTTP status code
	 * @param null|string $reason Reason phrase (when empty a default will be used based on the status code)
	 *
	 * @return void
	 */
	public function setStatus(int $status = 200, ?string $reason = null): void;

	/**
	 * Creates a PSR-7 compliant interface
	 * @see https://www.php-fig.org/psr/psr-7/
	 *
	 * @return ResponseInterface
	 */
	public function generate(): ResponseInterface;
}
