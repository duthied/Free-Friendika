<?php
/**
 * @copyright Copyright (C) 2010-2023, the Friendica project
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

namespace Friendica\App;

use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\System;
use Friendica\Util\Network;
use Friendica\Util\Strings;
use Friendica\Network\HTTPException;

/**
 * A class which checks and contains the basic
 * environment for the BaseURL (url, urlpath, ssl_policy, hostname, scheme)
 */
class BaseURL
{
	/**
	 * No SSL necessary
	 */
	const SSL_POLICY_NONE = 0;

	/**
	 * SSL is necessary
	 */
	const SSL_POLICY_FULL = 1;

	/**
	 * SSL is optional, but preferred
	 */
	const SSL_POLICY_SELFSIGN = 2;

	/**
	 * Define the Default SSL scheme
	 */
	const DEFAULT_SSL_SCHEME = self::SSL_POLICY_SELFSIGN;

	/**
	 * The Friendica Config
	 *
	 * @var IManageConfigValues
	 */
	private $config;

	/**
	 * The server side variables
	 *
	 * @var array
	 */
	private $server;

	/**
	 * The hostname of the Base URL
	 *
	 * @var string
	 */
	private $hostname;

	/**
	 * The SSL_POLICY of the Base URL
	 *
	 * @var int
	 */
	private $sslPolicy;

	/**
	 * The URL sub-path of the Base URL
	 *
	 * @var string
	 */
	private $urlPath;

	/**
	 * The full URL
	 *
	 * @var string
	 */
	private $url;

	/**
	 * The current scheme of this call
	 *
	 * @var string
	 */
	private $scheme;

	/**
	 * Returns the hostname of this node
	 *
	 * @return string
	 */
	public function getHostname(): string
	{
		return $this->hostname;
	}

	/**
	 * Returns the current scheme of this call
	 *
	 * @return string
	 */
	public function getScheme(): string
	{
		return $this->scheme;
	}

	/**
	 * Returns the SSL policy of this node
	 *
	 * @return int
	 */
	public function getSSLPolicy(): int
	{
		return $this->sslPolicy;
	}

	/**
	 * Returns the sub-path of this URL
	 *
	 * @return string
	 */
	public function getUrlPath(): string
	{
		return $this->urlPath;
	}

	/**
	 * Returns the full URL of this call
	 *
	 * Note: $ssl parameter value doesn't directly correlate with the resulting protocol
	 *
	 * @param bool $ssl True, if ssl should get used
	 *
	 * @return string
	 */
	public function get(bool $ssl = false): string
	{
		if ($this->sslPolicy === self::SSL_POLICY_SELFSIGN && $ssl) {
			return Network::switchScheme($this->url);
		}

		return $this->url;
	}

	/**
	 * Save current parts of the base Url
	 *
	 * @param string? $hostname
	 * @param int?    $sslPolicy
	 * @param string? $urlPath
	 *
	 * @return bool true, if successful
	 * @TODO Find proper types
	 */
	public function save($hostname = null, $sslPolicy = null, $urlPath = null): bool
	{
		$currUrl = $this->url;

		$configTransaction = $this->config->beginTransaction();

		if (!empty($hostname) && $hostname !== $this->hostname) {
			$configTransaction->set('config', 'hostname', $hostname);
			$this->hostname = $hostname;
		}

		if (isset($sslPolicy) && $sslPolicy !== $this->sslPolicy) {
			$configTransaction->set('system', 'ssl_policy', $sslPolicy);
			$this->sslPolicy = $sslPolicy;
		}

		if (isset($urlPath) && $urlPath !== $this->urlPath) {
			$configTransaction->set('system', 'urlpath', $urlPath);
			$this->urlPath = $urlPath;
		}

		$this->determineBaseUrl();
		if ($this->url !== $currUrl) {
			$configTransaction->set('system', 'url', $this->url);
		}

		$configTransaction->commit();

		return true;
	}

	/**
	 * Save the current url as base URL
	 *
	 * @param string $url
	 *
	 * @return bool true, if the save was successful
	 */
	public function saveByURL(string $url): bool
	{
		$parsed = @parse_url($url);

		if (empty($parsed) || empty($parsed['host'])) {
			return false;
		}

		$hostname = $parsed['host'];
		if (!empty($hostname) && !empty($parsed['port'])) {
			$hostname .= ':' . $parsed['port'];
		}

		$urlPath = null;
		if (!empty($parsed['path'])) {
			$urlPath = trim($parsed['path'], '\\/');
		}

		$sslPolicy = null;
		if (!empty($parsed['scheme'])) {
			if ($parsed['scheme'] == 'https') {
				$sslPolicy = BaseURL::SSL_POLICY_FULL;
			}
		}

		return $this->save($hostname, $sslPolicy, $urlPath);
	}

	/**
	 * Checks, if a redirect to the HTTPS site would be necessary
	 *
	 * @return bool
	 */
	public function checkRedirectHttps()
	{
		return $this->config->get('system', 'force_ssl') &&
		       ($this->getScheme() == "http") &&
		       intval($this->getSSLPolicy()) == BaseURL::SSL_POLICY_FULL &&
		       strpos($this->get(), 'https://') === 0 &&
		       !empty($this->server['REQUEST_METHOD']) &&
		       $this->server['REQUEST_METHOD'] === 'GET';
	}

	/**
	 * @param IManageConfigValues $config The Friendica IConfiguration
	 * @param array               $server The $_SERVER array
	 */
	public function __construct(IManageConfigValues $config, array $server)
	{
		$this->config    = $config;
		$this->server    = $server;
		$this->hostname  = $this->config->get('config', 'hostname');
		$this->urlPath   = $this->config->get('system', 'urlpath') ?? '';
		$this->sslPolicy = $this->config->get('system', 'ssl_policy') ?? static::DEFAULT_SSL_SCHEME;
		$this->url       = $this->config->get('system', 'url');

		if (empty($this->hostname) || empty($this->url)) {
			throw new \Exception('Invalid config - Missing system.url or config.hostname');
		}

		$this->determineSchema();
	}

	/**
	 * Determine the full URL based on all parts
	 */
	private function determineBaseUrl()
	{
		$scheme = 'http';

		if ($this->sslPolicy == self::SSL_POLICY_FULL) {
			$scheme = 'https';
		}

		$this->url = $scheme . '://' . $this->hostname . (!empty($this->urlPath) ? '/' . $this->urlPath : '');
	}

	/**
	 * Determine the scheme of the current used link
	 */
	private function determineSchema()
	{
		$this->scheme = 'http';

		if (!empty($this->server['HTTPS']) ||
		    !empty($this->server['HTTP_FORWARDED']) && preg_match('/proto=https/', $this->server['HTTP_FORWARDED']) ||
		    !empty($this->server['HTTP_X_FORWARDED_PROTO']) && $this->server['HTTP_X_FORWARDED_PROTO'] == 'https' ||
		    !empty($this->server['HTTP_X_FORWARDED_SSL']) && $this->server['HTTP_X_FORWARDED_SSL'] == 'on' ||
		    !empty($this->server['FRONT_END_HTTPS']) && $this->server['FRONT_END_HTTPS'] == 'on' ||
		    !empty($this->server['SERVER_PORT']) && (intval($this->server['SERVER_PORT']) == 443) // XXX: reasonable assumption, but isn't this hardcoding too much?
		) {
			$this->scheme = 'https';
		}
	}

	/**
	 * Removes the base url from an url. This avoids some mixed content problems.
	 *
	 * @param string $origURL
	 *
	 * @return string The cleaned url
	 */
	public function remove(string $origURL): string
	{
		// Remove the hostname from the url if it is an internal link
		$nurl = Strings::normaliseLink($origURL);
		$base = Strings::normaliseLink($this->get());
		$url  = str_replace($base . '/', '', $nurl);

		// if it is an external link return the orignal value
		if ($url == Strings::normaliseLink($origURL)) {
			return $origURL;
		} else {
			return $url;
		}
	}

	/**
	 * Redirects to another module relative to the current Friendica base URL.
	 * If you want to redirect to a external URL, use System::externalRedirectTo()
	 *
	 * @param string $toUrl The destination URL (Default is empty, which is the default page of the Friendica node)
	 * @param bool   $ssl   if true, base URL will try to get called with https:// (works just for relative paths)
	 *
	 * @throws HTTPException\FoundException
	 * @throws HTTPException\MovedPermanentlyException
	 * @throws HTTPException\TemporaryRedirectException
	 *
	 * @throws HTTPException\InternalServerErrorException In Case the given URL is not relative to the Friendica node
	 */
	public function redirect(string $toUrl = '', bool $ssl = false)
	{
		if (!empty(parse_url($toUrl, PHP_URL_SCHEME))) {
			throw new HTTPException\InternalServerErrorException("'$toUrl is not a relative path, please use System::externalRedirectTo");
		}

		$redirectTo = $this->get($ssl) . '/' . ltrim($toUrl, '/');
		System::externalRedirect($redirectTo);
	}

	/**
	 * Returns the base url as string
	 */
	public function __toString(): string
	{
		return (string) $this->get();
	}
}
