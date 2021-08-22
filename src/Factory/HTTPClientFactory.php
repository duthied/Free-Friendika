<?php

namespace Friendica\Factory;

use Friendica\App;
use Friendica\BaseFactory;
use Friendica\Core\Config\IConfig;
use Friendica\Network\HTTPClient;
use Friendica\Network\IHTTPClient;
use Friendica\Util\Profiler;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;
use Psr\Log\LoggerInterface;

class HTTPClientFactory extends BaseFactory
{
	/** @var IConfig */
	private $config;
	/** @var Profiler */
	private $profiler;
	/** @var App\BaseURL */
	private $baseUrl;

	public function __construct(LoggerInterface $logger, IConfig $config, Profiler $profiler, App\BaseURL $baseUrl)
	{
		parent::__construct($logger);
		$this->config   = $config;
		$this->profiler = $profiler;
		$this->baseUrl  = $baseUrl;
	}

	public function createClient(): IHTTPClient
	{
		$proxy = $this->config->get('system', 'proxy');

		if (!empty($proxy)) {
			$proxyuser = $this->config->get('system', 'proxyuser');

			if (!empty($proxyuser)) {
				$proxy = $proxyuser . '@' . $proxy;
			}
		}

		$logger = $this->logger;

		$onRedirect = function (
			RequestInterface $request,
			ResponseInterface $response,
			UriInterface $uri
		) use ($logger) {
			$logger->notice('Curl redirect.', ['url' => $request->getUri(), 'to' => $uri]);
		};

		$guzzle = new Client([
			RequestOptions::ALLOW_REDIRECTS => [
				'max'            => 8,
				'on_redirect'    => $onRedirect,
				'track_redirect' => true,
				'strict'         => true,
				'referer'        => true,
			],
			RequestOptions::HTTP_ERRORS => false,
			// Without this setting it seems as if some webservers send compressed content
			// This seems to confuse curl so that it shows this uncompressed.
			/// @todo  We could possibly set this value to "gzip" or something similar
			RequestOptions::DECODE_CONTENT   => '',
			RequestOptions::FORCE_IP_RESOLVE => ($this->config->get('system', 'ipv4_resolve') ? 'v4' : null),
			RequestOptions::CONNECT_TIMEOUT  => 10,
			RequestOptions::TIMEOUT          => $this->config->get('system', 'curl_timeout', 60),
			// by default we will allow self-signed certs
			// but you can override this
			RequestOptions::VERIFY => (bool)$this->config->get('system', 'verifyssl'),
			RequestOptions::PROXY  => $proxy,
		]);

		$userAgent = FRIENDICA_PLATFORM . " '" .
			FRIENDICA_CODENAME . "' " .
			FRIENDICA_VERSION . '-' .
			DB_UPDATE_VERSION . '; ' .
			$this->baseUrl->get();

		return new HTTPClient($logger, $this->profiler, $this->config, $userAgent, $guzzle);
	}
}
