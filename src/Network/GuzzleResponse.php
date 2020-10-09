<?php
/**
 * @copyright Copyright (C) 2020, Friendica
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

namespace Friendica\Network;

use Friendica\Core\Logger;
use Friendica\Core\System;
use Friendica\Network\HTTPException\NotImplementedException;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\ResponseInterface;

/**
 * A content wrapper class for Guzzle call results
 */
class GuzzleResponse extends Response implements IHTTPResult, ResponseInterface
{
	/** @var string The URL */
	private $url;
	/** @var boolean */
	private $isTimeout;
	/** @var boolean */
	private $isSuccess;
	/**
	 * @var int the error number or 0 (zero) if no error
	 */
	private $errorNumber;

	/**
	 * @var string the error message or '' (the empty string) if no
	 */
	private $error;

	public function __construct(ResponseInterface $response, string $url, $errorNumber = 0, $error = '')
	{
		parent::__construct($response->getStatusCode(), $response->getHeaders(), $response->getBody(), $response->getProtocolVersion(), $response->getReasonPhrase());
		$this->url         = $url;
		$this->error       = $error;
		$this->errorNumber = $errorNumber;

		$this->checkSuccess();
	}

	private function checkSuccess()
	{
		$this->isSuccess = ($this->getStatusCode() >= 200 && $this->getStatusCode() <= 299) || $this->errorNumber == 0;

		// Everything higher or equal 400 is not a success
		if ($this->getReturnCode() >= 400) {
			$this->isSuccess = false;
		}

		if (!$this->isSuccess) {
			Logger::notice('http error', ['url' => $this->url, 'code' => $this->getReturnCode(), 'error'  => $this->error, 'callstack' => System::callstack(20)]);
			Logger::debug('debug', ['info' => $this->getHeaders()]);
		}

		if (!$this->isSuccess && $this->errorNumber == CURLE_OPERATION_TIMEDOUT) {
			$this->isTimeout = true;
		} else {
			$this->isTimeout = false;
		}
	}

	/** {@inheritDoc} */
	public function getReturnCode()
	{
		return $this->getStatusCode();
	}

	/** {@inheritDoc} */
	public function getContentType()
	{
		return $this->getHeader('Content-Type');
	}

	/** {@inheritDoc} */
	public function inHeader(string $field)
	{
		return $this->hasHeader($field);
	}

	/** {@inheritDoc} */
	public function getHeaderArray()
	{
		return $this->getHeaders();
	}

	/** {@inheritDoc} */
	public function isSuccess()
	{
		return $this->isSuccess;
	}

	/** {@inheritDoc} */
	public function getUrl()
	{
		return $this->url;
	}

	/** {@inheritDoc} */
	public function getRedirectUrl()
	{
		return $this->url;
	}

	/** {@inheritDoc} */
	public function isRedirectUrl()
	{
		throw new NotImplementedException();
	}

	/** {@inheritDoc} */
	public function getErrorNumber()
	{
		return $this->errorNumber;
	}

	/** {@inheritDoc} */
	public function getError()
	{
		return $this->error;
	}

	/** {@inheritDoc} */
	public function isTimeout()
	{
		return $this->isTimeout;
	}
}
