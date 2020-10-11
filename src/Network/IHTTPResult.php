<?php

namespace Friendica\Network;

/**
 * Temporary class to map Friendica used variables based on PSR-7 HTTPResponse
 */
interface IHTTPResult
{
	/**
	 * Gets the Return Code
	 *
	 * @return string The Return Code
	 */
	public function getReturnCode();

	/**
	 * Returns the Content Type
	 *
	 * @return string the Content Type
	 */
	public function getContentType();

	/**
	 * Returns the headers
	 *
	 * @param string $field optional header field. Return all fields if empty
	 *
	 * @return string the headers or the specified content of the header variable
	 */
	public function getHeader(string $field = '');

	/**
	 * Check if a specified header exists
	 *
	 * @param string $field header field
	 *
	 * @return boolean "true" if header exists
	 */
	public function inHeader(string $field);

	/**
	 * Returns the headers as an associated array
	 *
	 * @return array associated header array
	 */
	public function getHeaderArray();

	/**
	 * @return bool
	 */
	public function isSuccess();

	/**
	 * @return string
	 */
	public function getUrl();

	/**
	 * @return string
	 */
	public function getRedirectUrl();

	/**
	 * @return string
	 */
	public function getBody();

	/**
	 * @return array
	 */
	public function getInfo();

	/**
	 * @return boolean
	 */
	public function isRedirectUrl();

	/**
	 * @return integer
	 */
	public function getErrorNumber();

	/**
	 * @return string
	 */
	public function getError();

	/**
	 * @return boolean
	 */
	public function isTimeout();
}
