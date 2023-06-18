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

namespace Friendica\Module;

use Exception;
use Friendica\App;
use Friendica\BaseModule;
use Friendica\Core\L10n;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Core\System;
use Friendica\Database\Database;
use Friendica\Model\Contact;
use Friendica\Model\GServer;
use Friendica\Model\User;
use Friendica\Network\HTTPClient\Capability\ICanSendHttpRequests;
use Friendica\Network\HTTPClient\Client\HttpClientOptions;
use Friendica\Util\HTTPSignature;
use Friendica\Util\Profiler;
use Friendica\Util\Strings;
use GuzzleHttp\Psr7\Uri;
use Psr\Log\LoggerInterface;

/**
 * Magic Auth (remote authentication) module.
 *
 * Ported from Hubzilla: https://framagit.org/hubzilla/core/blob/master/Zotlabs/Module/Magic.php
 */
class Magic extends BaseModule
{
	/** @var App */
	protected $app;
	/** @var Database */
	protected $dba;
	/** @var ICanSendHttpRequests */
	protected $httpClient;
	/** @var IHandleUserSessions */
	protected $userSession;

	public function __construct(App $app, L10n $l10n, App\BaseURL $baseUrl, App\Arguments $args, LoggerInterface $logger, Profiler $profiler, Response $response, Database $dba, ICanSendHttpRequests $httpClient, IHandleUserSessions $userSession, $server, array $parameters = [])
	{
		parent::__construct($l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		$this->app         = $app;
		$this->dba         = $dba;
		$this->httpClient  = $httpClient;
		$this->userSession = $userSession;
	}

	protected function rawContent(array $request = [])
	{
		if ($_SERVER['REQUEST_METHOD'] == 'HEAD') {
			$this->logger->debug('Got a HEAD request');
			System::exit();
		}

		$this->logger->debug('Invoked', ['request' => $request]);

		$addr  = $request['addr'] ?? '';
		$dest  = $request['dest'] ?? '';
		$bdest = $request['bdest'] ?? '';
		$owa   = intval($request['owa'] ?? 0);

		// bdest is preferred as it is hex-encoded and can survive url rewrite and argument parsing
		if (!empty($bdest)) {
			$dest = hex2bin($bdest);
			$this->logger->debug('bdest detected', ['dest' => $dest]);
		}

		$target = $dest ?: $addr;

		if ($addr ?: $dest) {
			$contact = Contact::getByURL($addr ?: $dest);
		}

		if (empty($contact)) {
			if (!$owa) {
				$this->logger->info('No contact record found, no oWA, redirecting to destination.', ['request' => $request, 'server' => $_SERVER, 'dest' => $dest]);
				$this->app->redirect($dest);
			}
		} else {
			// Redirect if the contact is already authenticated on this site.
			if ($this->app->getContactId() && strpos($contact['nurl'], Strings::normaliseLink($this->baseUrl)) !== false) {
				$this->logger->info('Contact is already authenticated, redirecting to destination.', ['dest' => $dest]);
				System::externalRedirect($dest);
			}

			$this->logger->debug('Contact found', ['url' => $contact['url']]);
		}

		if (!$this->userSession->getLocalUserId() || !$owa) {
			$this->logger->notice('Not logged in or not OWA, redirecting to destination.', ['uid' => $this->userSession->getLocalUserId(), 'owa' => $owa, 'dest' => $dest]);
			$this->app->redirect($dest);
		}

		// OpenWebAuth
		$owner = User::getOwnerDataById($this->userSession->getLocalUserId());

		if (!empty($contact['gsid'])) {
			$gserver = $this->dba->selectFirst('gserver', ['url'], ['id' => $contact['gsid']]);
			if (empty($gserver)) {
				$this->logger->notice('Server not found, redirecting to destination.', ['gsid' => $contact['gsid'], 'dest' => $dest]);
				System::externalRedirect($dest);
			}

			$basepath = $gserver['url'];
		} elseif (GServer::check($target)) {
			$basepath = (string)GServer::cleanUri(new Uri($target));
		} else {
			$this->logger->notice('The target is not a server path, redirecting to destination.', ['target' => $target]);
			System::externalRedirect($dest);
		}

		$header = [
			'Accept'          => 'application/x-dfrn+json, application/x-zot+json',
			'X-Open-Web-Auth' => Strings::getRandomHex()
		];

		// Create a header that is signed with the local users private key.
		$header = HTTPSignature::createSig(
			$header,
			$owner['prvkey'],
			'acct:' . $owner['addr']
		);

		$this->logger->info('Fetch from remote system', ['basepath' => $basepath, 'headers' => $header]);

		// Try to get an authentication token from the other instance.
		try {
			$curlResult = $this->httpClient->request('get', $basepath . '/owa', [HttpClientOptions::HEADERS => $header]);
		} catch (Exception $exception) {
			$this->logger->notice('URL is invalid, redirecting to destination.', ['url' => $basepath, 'error' => $exception, 'dest' => $dest]);
			System::externalRedirect($dest);
		}
		if (!$curlResult->isSuccess()) {
			$this->logger->notice('OWA request failed, redirecting to destination.', ['returncode' => $curlResult->getReturnCode(), 'dest' => $dest]);
			System::externalRedirect($dest);
		}

		$j = json_decode($curlResult->getBody(), true);
		if (empty($j) || !$j['success']) {
			$this->logger->notice('Invalid JSON, redirecting to destination.', ['json' => $j, 'dest' => $dest]);
			$this->app->redirect($dest);
		}

		if ($j['encrypted_token']) {
			// The token is encrypted. If the local user is really the one the other instance
			// thinks they is, the token can be decrypted with the local users public key.
			$token = '';
			openssl_private_decrypt(Strings::base64UrlDecode($j['encrypted_token']), $token, $owner['prvkey']);
		} else {
			$token = $j['token'];
		}
		$args = (strpbrk($dest, '?&') ? '&' : '?') . 'owt=' . $token;

		$this->logger->debug('Redirecting', ['path' => $dest . $args]);
		System::externalRedirect($dest . $args);
	}
}
