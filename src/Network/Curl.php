<?php

namespace Friendica\Network;


/**
 * A content class for Curl call results
 */
class Curl
{
	/**
	 * @var string the Code of the Curl call
	 */
	private $code;

	/**
	 * @var string the content type of the Curl call
	 */
	private $contentType;

	/**
	 * @var string the headers of the Curl call
	 */
	private $headers;

	public function __construct($code = '', $contentType = '', $headers = '')
	{
		$this->code = $code;
		$this->contentType = $contentType;
		$this->headers = $headers;
	}

	/**
	 * Sets the Curl Code
	 *
	 * @param string $code The Curl Code
	 */
	public function setCode($code)
	{
		$this->code = $code;
	}

	/**
	 * Gets the Curl Code
	 *
	 * @return string The Curl Code
	 */
	public function getCode()
	{
		return $this->code;
	}

	/**
	 * Sets the Curl Content Type
	 *
	 * @param string $content_type The Curl Content Type
	 */
	public function setContentType($content_type)
	{
		$this->contentType = $content_type;
	}

	/**
	 * Returns the Curl Content Type
	 *
	 * @return string the Curl Content Type
	 */
	public function getContentType()
	{
		return $this->contentType;
	}

	/**
	 * Sets the Curl headers
	 *
	 * @param string $headers the Curl headers
	 */
	public function setHeaders($headers)
	{
		$this->headers = $headers;
	}

	/**
	 * Returns the Curl headers
	 *
	 * @return string the Curl headers
	 */
	public function getHeaders()
	{
		return $this->headers;
	}
}
