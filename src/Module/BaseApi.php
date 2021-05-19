<?php
/**
 * @copyright Copyright (C) 2010-2021, the Friendica project
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

use Friendica\BaseModule;
use Friendica\Core\Logger;
use Friendica\Core\System;
use Friendica\Database\Database;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Network\HTTPException;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Network;

require_once __DIR__ . '/../../include/api.php';

class BaseApi extends BaseModule
{
	const SCOPE_READ   = 'read';
	const SCOPE_WRITE  = 'write';
	const SCOPE_FOLLOW = 'follow';
	const SCOPE_PUSH   = 'push';

	/**
	 * @var string json|xml|rss|atom
	 */
	protected static $format = 'json';
	/**
	 * @var bool|int
	 */
	protected static $current_user_id;
	/**
	 * @var array
	 */
	protected static $current_token = [];

	public static function init(array $parameters = [])
	{
		$arguments = DI::args();

		if (substr($arguments->getCommand(), -4) === '.xml') {
			self::$format = 'xml';
		}
		if (substr($arguments->getCommand(), -4) === '.rss') {
			self::$format = 'rss';
		}
		if (substr($arguments->getCommand(), -4) === '.atom') {
			self::$format = 'atom';
		}
	}

	public static function delete(array $parameters = [])
	{
		if (!api_user()) {
			throw new HTTPException\UnauthorizedException(DI::l10n()->t('Permission denied.'));
		}

		$a = DI::app();

		if (!empty($a->user['uid']) && $a->user['uid'] != api_user()) {
			throw new HTTPException\ForbiddenException(DI::l10n()->t('Permission denied.'));
		}
	}

	public static function patch(array $parameters = [])
	{
		if (!api_user()) {
			throw new HTTPException\UnauthorizedException(DI::l10n()->t('Permission denied.'));
		}

		$a = DI::app();

		if (!empty($a->user['uid']) && $a->user['uid'] != api_user()) {
			throw new HTTPException\ForbiddenException(DI::l10n()->t('Permission denied.'));
		}
	}

	public static function post(array $parameters = [])
	{
		if (!api_user()) {
			throw new HTTPException\UnauthorizedException(DI::l10n()->t('Permission denied.'));
		}

		$a = DI::app();

		if (!empty($a->user['uid']) && $a->user['uid'] != api_user()) {
			throw new HTTPException\ForbiddenException(DI::l10n()->t('Permission denied.'));
		}
	}

	public static function put(array $parameters = [])
	{
		if (!api_user()) {
			throw new HTTPException\UnauthorizedException(DI::l10n()->t('Permission denied.'));
		}

		$a = DI::app();

		if (!empty($a->user['uid']) && $a->user['uid'] != api_user()) {
			throw new HTTPException\ForbiddenException(DI::l10n()->t('Permission denied.'));
		}
	}

	/**
	 * Quit execution with the message that the endpoint isn't implemented
	 *
	 * @param string $method
	 * @return void
	 */
	public static function unsupported(string $method = 'all')
	{
		$path = DI::args()->getQueryString();
		Logger::info('Unimplemented API call', ['method' => $method, 'path' => $path, 'agent' => $_SERVER['HTTP_USER_AGENT'] ?? '', 'request' => $_REQUEST ?? []]);
		$error = DI::l10n()->t('API endpoint %s %s is not implemented', strtoupper($method), $path);
		$error_description = DI::l10n()->t('The API endpoint is currently not implemented but might be in the future.');
		$errorobj = new \Friendica\Object\Api\Mastodon\Error($error, $error_description);
		System::jsonError(501, $errorobj->toArray());
	}

	/**
	 * Processes data from GET requests and sets defaults
	 *
	 * @return array request data
	 */
	public static function getRequest(array $defaults) {
		$request = [];

		foreach ($defaults as $parameter => $defaultvalue) {
			if (is_string($defaultvalue)) {
				$request[$parameter] = $_REQUEST[$parameter] ?? $defaultvalue;
			} elseif (is_int($defaultvalue)) {
				$request[$parameter] = (int)($_REQUEST[$parameter] ?? $defaultvalue);
			} elseif (is_float($defaultvalue)) {
				$request[$parameter] = (float)($_REQUEST[$parameter] ?? $defaultvalue);
			} elseif (is_array($defaultvalue)) {
				$request[$parameter] = $_REQUEST[$parameter] ?? [];
			} elseif (is_bool($defaultvalue)) {
				$request[$parameter] = in_array(strtolower($_REQUEST[$parameter] ?? ''), ['true', '1']);
			} else {
				Logger::notice('Unhandled default value type', ['parameter' => $parameter, 'type' => gettype($defaultvalue)]);
			}
		}

		foreach ($_REQUEST ?? [] as $parameter => $value) {
			if ($parameter == 'pagename') {
				continue;
			}
			if (!in_array($parameter, array_keys($defaults))) {
				Logger::notice('Unhandled request field', ['parameter' => $parameter, 'value' => $value, 'command' => DI::args()->getCommand()]);
			}
		}

		Logger::debug('Got request parameters', ['request' => $request, 'command' => DI::args()->getCommand()]);
		return $request;
	}

	/**
	 * Get post data that is transmitted as JSON
	 *
	 * @return array request data
	 */
	public static function getJsonPostData()
	{
		$postdata = Network::postdata();
		if (empty($postdata)) {
			return [];
		}

		return json_decode($postdata, true);
	}

	/**
	 * Get request data for put requests
	 *
	 * @return array request data
	 */
	public static function getPutData()
	{
		$rawdata = Network::postdata();
		if (empty($rawdata)) {
			return [];
		}

		$putdata = [];

		foreach (explode('&', $rawdata) as $value) {
			$data = explode('=', $value);
			if (count($data) == 2) {
				$putdata[$data[0]] = urldecode($data[1]);
			}
		}

		return $putdata;
	}

	/**
	 * Log in user via OAuth1 or Simple HTTP Auth.
	 *
	 * Simple Auth allow username in form of <pre>user@server</pre>, ignoring server part
	 *
	 * @param string $scope the requested scope (read, write, follow)
	 *
	 * @return bool Was a user authenticated?
	 * @throws HTTPException\ForbiddenException
	 * @throws HTTPException\UnauthorizedException
	 * @throws HTTPException\InternalServerErrorException
	 * @hook  'authenticate'
	 *               array $addon_auth
	 *               'username' => username from login form
	 *               'password' => password from login form
	 *               'authenticated' => return status,
	 *               'user_record' => return authenticated user record
	 */
	protected static function login(string $scope)
	{
		if (empty(self::$current_user_id)) {
			self::$current_token = self::getTokenByBearer();
			if (!empty(self::$current_token['uid'])) {
				self::$current_user_id = self::$current_token['uid'];
			} else {
				self::$current_user_id = 0;
			}
		}

		if (!empty($scope) && !empty(self::$current_token)) {
			if (empty(self::$current_token[$scope])) {
				Logger::warning('The requested scope is not allowed', ['scope' => $scope, 'application' => self::$current_token]);
				DI::mstdnError()->Forbidden();
			}
		}

		if (empty(self::$current_user_id)) {
			// The execution stops here if no one is logged in
			api_login(DI::app());
		}

		self::$current_user_id = api_user();

		return (bool)self::$current_user_id;
	}

	/**
	 * Get current application
	 *
	 * @return array token
	 */
	protected static function getCurrentApplication()
	{
		return self::$current_token;
	}

	/**
	 * Get current user id, returns 0 if not logged in
	 *
	 * @return int User ID
	 */
	protected static function getCurrentUserID()
	{
		if (empty(self::$current_user_id)) {
			self::$current_token = self::getTokenByBearer();
			if (!empty(self::$current_token['uid'])) {
				self::$current_user_id = self::$current_token['uid'];
			} else {
				self::$current_user_id = 0;
			}

		}

		if (empty(self::$current_user_id)) {
			// Fetch the user id if logged in - but don't fail if not
			api_login(DI::app(), false);

			self::$current_user_id = api_user();
		}

		return (int)self::$current_user_id;
	}

	/**
	 * Get the user token via the Bearer token
	 *
	 * @return array User Token
	 */
	private static function getTokenByBearer()
	{
		$authorization = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

		if (substr($authorization, 0, 7) != 'Bearer ') {
			return [];
		}

		$bearer = trim(substr($authorization, 7));
		$condition = ['access_token' => $bearer];
		$token = DBA::selectFirst('application-view', ['uid', 'id', 'name', 'website', 'created_at', 'read', 'write', 'follow', 'push'], $condition);
		if (!DBA::isResult($token)) {
			Logger::warning('Token not found', $condition);
			return [];
		}
		Logger::debug('Token found', $token);
		return $token;
	}

	/**
	 * Get the application record via the proved request header fields
	 *
	 * @param string $client_id
	 * @param string $client_secret
	 * @param string $redirect_uri
	 * @return array application record
	 */
	public static function getApplication(string $client_id, string $client_secret, string $redirect_uri)
	{
		$condition = ['client_id' => $client_id];
		if (!empty($client_secret)) {
			$condition['client_secret'] = $client_secret;
		}
		if (!empty($redirect_uri)) {
			$condition['redirect_uri'] = $redirect_uri;
		}

		$application = DBA::selectFirst('application', [], $condition);
		if (!DBA::isResult($application)) {
			Logger::warning('Application not found', $condition);
			return [];
		}
		return $application;
	}

	/**
	 * Check if an token for the application and user exists
	 *
	 * @param array $application
	 * @param integer $uid
	 * @return boolean
	 */
	public static function existsTokenForUser(array $application, int $uid)
	{
		return DBA::exists('application-token', ['application-id' => $application['id'], 'uid' => $uid]);
	}

	/**
	 * Fetch the token for the given application and user
	 *
	 * @param array $application
	 * @param integer $uid
	 * @return array application record
	 */
	public static function getTokenForUser(array $application, int $uid)
	{
		return DBA::selectFirst('application-token', [], ['application-id' => $application['id'], 'uid' => $uid]);
	}

	/**
	 * Create and fetch an token for the application and user
	 *
	 * @param array   $application
	 * @param integer $uid
	 * @param string  $scope
	 * @return array application record
	 */
	public static function createTokenForUser(array $application, int $uid, string $scope)
	{
		$code         = bin2hex(random_bytes(32));
		$access_token = bin2hex(random_bytes(32));

		$fields = ['application-id' => $application['id'], 'uid' => $uid, 'code' => $code, 'access_token' => $access_token, 'scopes' => $scope,
			'read' => (stripos($scope, self::SCOPE_READ) !== false),
			'write' => (stripos($scope, self::SCOPE_WRITE) !== false),
			'follow' => (stripos($scope, self::SCOPE_FOLLOW) !== false),
			'push' => (stripos($scope, self::SCOPE_PUSH) !== false),
			 'created_at' => DateTimeFormat::utcNow(DateTimeFormat::MYSQL)];

		foreach ([self::SCOPE_READ, self::SCOPE_WRITE, self::SCOPE_WRITE, self::SCOPE_PUSH] as $scope) {
			if ($fields[$scope] && !$application[$scope]) {
				Logger::warning('Requested token scope is not allowed for the application', ['token' => $fields, 'application' => $application]);
			}
		}
	
		if (!DBA::insert('application-token', $fields, Database::INSERT_UPDATE)) {
			return [];
		}

		return DBA::selectFirst('application-token', [], ['application-id' => $application['id'], 'uid' => $uid]);
	}

	/**
	 * Get user info array.
	 *
	 * @param int|string $contact_id Contact ID or URL
	 * @return array|bool
	 * @throws HTTPException\BadRequestException
	 * @throws HTTPException\InternalServerErrorException
	 * @throws HTTPException\UnauthorizedException
	 * @throws \ImagickException
	 */
	protected static function getUser($contact_id = null)
	{
		return api_get_user(DI::app(), $contact_id);
	}

	/**
	 * Formats the data according to the data type
	 *
	 * @param string $root_element
	 * @param array $data An array with a single element containing the returned result
	 * @return false|string
	 */
	protected static function format(string $root_element, array $data)
	{
		$return = api_format_data($root_element, self::$format, $data);

		switch (self::$format) {
			case "xml":
				header("Content-Type: text/xml");
				break;
			case "json":
				header("Content-Type: application/json");
				if (!empty($return)) {
					$json = json_encode(end($return));
					if (!empty($_GET['callback'])) {
						$json = $_GET['callback'] . "(" . $json . ")";
					}
					$return = $json;
				}
				break;
			case "rss":
				header("Content-Type: application/rss+xml");
				$return  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . $return;
				break;
			case "atom":
				header("Content-Type: application/atom+xml");
				$return = '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . $return;
				break;
		}

		return $return;
	}

	/**
	 * Creates the XML from a JSON style array
	 *
	 * @param $data
	 * @param $root_element
	 * @return string
	 */
	protected static function createXml($data, $root_element)
	{
		return api_create_xml($data, $root_element);
	}
}
