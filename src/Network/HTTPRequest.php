<?php
/**
 * @copyright Copyright (C) 2010-2021, the Friendica project
 *
 * @license GNU APGL version 3 or any later version
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

use DOMDocument;
use DomXPath;
use Friendica\App;
use Friendica\Core\Config\IConfig;
use Friendica\Core\System;
use Friendica\Util\Network;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

/**
 * Performs HTTP requests to a given URL
 */
class HTTPRequest implements IHTTPRequest
{
	/** @var LoggerInterface */
	private $logger;
	/** @var Profiler */
	private $profiler;
	/** @var IConfig */
	private $config;
	/** @var string */
	private $baseUrl;

	public function __construct(LoggerInterface $logger, Profiler $profiler, IConfig $config, App\BaseURL $baseUrl)
	{
		$this->logger   = $logger;
		$this->profiler = $profiler;
		$this->config   = $config;
		$this->baseUrl  = $baseUrl->get();
	}

	/** {@inheritDoc}
	 *
	 * @throws HTTPException\InternalServerErrorException
	 */
	public function head(string $url, array $opts = [])
	{
		$opts['nobody'] = true;

		return $this->get($url, $opts);
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param int $redirects The recursion counter for internal use - default 0
	 *
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public function get(string $url, array $opts = [], int &$redirects = 0)
	{
		$stamp1 = microtime(true);

		if (strlen($url) > 1000) {
			$this->logger->debug('URL is longer than 1000 characters.', ['url' => $url, 'callstack' => System::callstack(20)]);
			$this->profiler->saveTimestamp($stamp1, 'network');
			return CurlResult::createErrorCurl(substr($url, 0, 200));
		}

		$parts2     = [];
		$parts      = parse_url($url);
		$path_parts = explode('/', $parts['path'] ?? '');
		foreach ($path_parts as $part) {
			if (strlen($part) <> mb_strlen($part)) {
				$parts2[] = rawurlencode($part);
			} else {
				$parts2[] = $part;
			}
		}
		$parts['path'] = implode('/', $parts2);
		$url           = Network::unparseURL($parts);

		if (Network::isUrlBlocked($url)) {
			$this->logger->info('Domain is blocked.', ['url' => $url]);
			$this->profiler->saveTimestamp($stamp1, 'network');
			return CurlResult::createErrorCurl($url);
		}

		$ch = @curl_init($url);

		if (($redirects > 8) || (!$ch)) {
			$this->profiler->saveTimestamp($stamp1, 'network');
			return CurlResult::createErrorCurl($url);
		}

		@curl_setopt($ch, CURLOPT_HEADER, true);

		if (!empty($opts['cookiejar'])) {
			curl_setopt($ch, CURLOPT_COOKIEJAR, $opts["cookiejar"]);
			curl_setopt($ch, CURLOPT_COOKIEFILE, $opts["cookiejar"]);
		}

		// These settings aren't needed. We're following the location already.
		//	@curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		//	@curl_setopt($ch, CURLOPT_MAXREDIRS, 5);

		if (!empty($opts['accept_content'])) {
			curl_setopt(
				$ch,
				CURLOPT_HTTPHEADER,
				['Accept: ' . $opts['accept_content']]
			);
		}

		if (!empty($opts['header'])) {
			curl_setopt($ch, CURLOPT_HTTPHEADER, $opts['header']);
		}

		@curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		@curl_setopt($ch, CURLOPT_USERAGENT, $this->getUserAgent());

		$range = intval($this->config->get('system', 'curl_range_bytes', 0));

		if ($range > 0) {
			@curl_setopt($ch, CURLOPT_RANGE, '0-' . $range);
		}

		// Without this setting it seems as if some webservers send compressed content
		// This seems to confuse curl so that it shows this uncompressed.
		/// @todo  We could possibly set this value to "gzip" or something similar
		curl_setopt($ch, CURLOPT_ENCODING, '');

		if (!empty($opts['headers'])) {
			$this->logger->notice('Wrong option \'headers\' used.');
			@curl_setopt($ch, CURLOPT_HTTPHEADER, $opts['headers']);
		}

		if (!empty($opts['nobody'])) {
			@curl_setopt($ch, CURLOPT_NOBODY, $opts['nobody']);
		}

		@curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);

		if (!empty($opts['timeout'])) {
			@curl_setopt($ch, CURLOPT_TIMEOUT, $opts['timeout']);
		} else {
			$curl_time = $this->config->get('system', 'curl_timeout', 60);
			@curl_setopt($ch, CURLOPT_TIMEOUT, intval($curl_time));
		}

		// by default we will allow self-signed certs
		// but you can override this

		$check_cert = $this->config->get('system', 'verifyssl');
		@curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, (($check_cert) ? true : false));

		if ($check_cert) {
			@curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
		}

		$proxy = $this->config->get('system', 'proxy');

		if (!empty($proxy)) {
			@curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, 1);
			@curl_setopt($ch, CURLOPT_PROXY, $proxy);
			$proxyuser = $this->config->get('system', 'proxyuser');

			if (!empty($proxyuser)) {
				@curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxyuser);
			}
		}

		if ($this->config->get('system', 'ipv4_resolve', false)) {
			curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
		}

		$s         = @curl_exec($ch);
		$curl_info = @curl_getinfo($ch);

		// Special treatment for HTTP Code 416
		// See https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/416
		if (($curl_info['http_code'] == 416) && ($range > 0)) {
			@curl_setopt($ch, CURLOPT_RANGE, '');
			$s         = @curl_exec($ch);
			$curl_info = @curl_getinfo($ch);
		}

		$curlResponse = new CurlResult($url, $s, $curl_info, curl_errno($ch), curl_error($ch));

		if (!Network::isRedirectBlocked($url) && $curlResponse->isRedirectUrl()) {
			$redirects++;
			$this->logger->notice('Curl redirect.', ['url' => $url, 'to' => $curlResponse->getRedirectUrl()]);
			@curl_close($ch);
			$this->profiler->saveTimestamp($stamp1, 'network');
			return $this->get($curlResponse->getRedirectUrl(), $opts, $redirects);
		}

		@curl_close($ch);

		$this->profiler->saveTimestamp($stamp1, 'network');

		return $curlResponse;
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param int $redirects The recursion counter for internal use - default 0
	 *
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public function post(string $url, $params, array $headers = [], int $timeout = 0, int &$redirects = 0)
	{
		$stamp1 = microtime(true);

		if (Network::isUrlBlocked($url)) {
			$this->logger->info('Domain is blocked.' . ['url' => $url]);
			$this->profiler->saveTimestamp($stamp1, 'network');
			return CurlResult::createErrorCurl($url);
		}

		$ch = curl_init($url);

		if (($redirects > 8) || (!$ch)) {
			$this->profiler->saveTimestamp($stamp1, 'network');
			return CurlResult::createErrorCurl($url);
		}

		$this->logger->debug('Post_url: start.', ['url' => $url]);

		curl_setopt($ch, CURLOPT_HEADER, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
		curl_setopt($ch, CURLOPT_USERAGENT, $this->getUserAgent());

		if ($this->config->get('system', 'ipv4_resolve', false)) {
			curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
		}

		@curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);

		if (intval($timeout)) {
			curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
		} else {
			$curl_time = $this->config->get('system', 'curl_timeout', 60);
			curl_setopt($ch, CURLOPT_TIMEOUT, intval($curl_time));
		}

		if (!empty($headers)) {
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		}

		$check_cert = $this->config->get('system', 'verifyssl');
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, (($check_cert) ? true : false));

		if ($check_cert) {
			@curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
		}

		$proxy = $this->config->get('system', 'proxy');

		if (!empty($proxy)) {
			curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, 1);
			curl_setopt($ch, CURLOPT_PROXY, $proxy);
			$proxyuser = $this->config->get('system', 'proxyuser');
			if (!empty($proxyuser)) {
				curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxyuser);
			}
		}

		// don't let curl abort the entire application
		// if it throws any errors.

		$s = @curl_exec($ch);

		$curl_info = curl_getinfo($ch);

		$curlResponse = new CurlResult($url, $s, $curl_info, curl_errno($ch), curl_error($ch));

		if (!Network::isRedirectBlocked($url) && $curlResponse->isRedirectUrl()) {
			$redirects++;
			$this->logger->info('Post redirect.', ['url' => $url, 'to' => $curlResponse->getRedirectUrl()]);
			curl_close($ch);
			$this->profiler->saveTimestamp($stamp1, 'network');
			return $this->post($curlResponse->getRedirectUrl(), $params, $headers, $redirects, $timeout);
		}

		curl_close($ch);

		$this->profiler->saveTimestamp($stamp1, 'network');

		// Very old versions of Lighttpd don't like the "Expect" header, so we remove it when needed
		if ($curlResponse->getReturnCode() == 417) {
			$redirects++;

			if (empty($headers)) {
				$headers = ['Expect:'];
			} else {
				if (!in_array('Expect:', $headers)) {
					array_push($headers, 'Expect:');
				}
			}
			$this->logger->info('Server responds with 417, applying workaround', ['url' => $url]);
			return $this->post($url, $params, $headers, $redirects, $timeout);
		}

		$this->logger->debug('Post_url: End.', ['url' => $url]);

		return $curlResponse;
	}

	/**
	 * {@inheritDoc}
	 */
	public function finalUrl(string $url, int $depth = 1, bool $fetchbody = false)
	{
		if (Network::isUrlBlocked($url)) {
			$this->logger->info('Domain is blocked.', ['url' => $url]);
			return $url;
		}

		if (Network::isRedirectBlocked($url)) {
			$this->logger->info('Domain should not be redirected.', ['url' => $url]);
			return $url;
		}

		$url = Network::stripTrackingQueryParams($url);

		if ($depth > 10) {
			return $url;
		}

		$url = trim($url, "'");

		$stamp1 = microtime(true);

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HEADER, 1);
		curl_setopt($ch, CURLOPT_NOBODY, 1);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
		curl_setopt($ch, CURLOPT_TIMEOUT, 10);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_USERAGENT, $this->getUserAgent());

		curl_exec($ch);
		$curl_info = @curl_getinfo($ch);
		$http_code = $curl_info['http_code'];
		curl_close($ch);

		$this->profiler->saveTimestamp($stamp1, "network");

		if ($http_code == 0) {
			return $url;
		}

		if (in_array($http_code, ['301', '302'])) {
			if (!empty($curl_info['redirect_url'])) {
				return $this->finalUrl($curl_info['redirect_url'], ++$depth, $fetchbody);
			} elseif (!empty($curl_info['location'])) {
				return $this->finalUrl($curl_info['location'], ++$depth, $fetchbody);
			}
		}

		// Check for redirects in the meta elements of the body if there are no redirects in the header.
		if (!$fetchbody) {
			return $this->finalUrl($url, ++$depth, true);
		}

		// if the file is too large then exit
		if ($curl_info["download_content_length"] > 1000000) {
			return $url;
		}

		// if it isn't a HTML file then exit
		if (!empty($curl_info["content_type"]) && !strstr(strtolower($curl_info["content_type"]), "html")) {
			return $url;
		}

		$stamp1 = microtime(true);

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_NOBODY, 0);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
		curl_setopt($ch, CURLOPT_TIMEOUT, 10);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_USERAGENT, $this->getUserAgent());

		$body = curl_exec($ch);
		curl_close($ch);

		$this->profiler->saveTimestamp($stamp1, "network");

		if (trim($body) == "") {
			return $url;
		}

		// Check for redirect in meta elements
		$doc = new DOMDocument();
		@$doc->loadHTML($body);

		$xpath = new DomXPath($doc);

		$list = $xpath->query("//meta[@content]");
		foreach ($list as $node) {
			$attr = [];
			if ($node->attributes->length) {
				foreach ($node->attributes as $attribute) {
					$attr[$attribute->name] = $attribute->value;
				}
			}

			if (@$attr["http-equiv"] == 'refresh') {
				$path = $attr["content"];
				$pathinfo = explode(";", $path);
				foreach ($pathinfo as $value) {
					if (substr(strtolower($value), 0, 4) == "url=") {
						return $this->finalUrl(substr($value, 4), ++$depth);
					}
				}
			}
		}

		return $url;
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param int $redirects The recursion counter for internal use - default 0
	 *
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public function fetch(string $url, int $timeout = 0, string $accept_content = '', string $cookiejar = '', int &$redirects = 0)
	{
		$ret = $this->fetchFull($url, $timeout, $accept_content, $cookiejar, $redirects);

		return $ret->getBody();
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param int $redirects The recursion counter for internal use - default 0
	 *
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public function fetchFull(string $url, int $timeout = 0, string $accept_content = '', string $cookiejar = '', int &$redirects = 0)
	{
		return $this->get(
			$url,
			[
				'timeout'        => $timeout,
				'accept_content' => $accept_content,
				'cookiejar'      => $cookiejar
			],
			$redirects
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function getUserAgent()
	{
		return
			FRIENDICA_PLATFORM . " '" .
			FRIENDICA_CODENAME . "' " .
			FRIENDICA_VERSION . '-' .
			DB_UPDATE_VERSION . '; ' .
			$this->baseUrl;
	}
}
