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
use Friendica\DI;
use Friendica\Network\HTTPException;
use Friendica\Security\BasicAuth;
use Friendica\Security\OAuth;
use Friendica\Util\HTTPInputData;

require_once __DIR__ . '/../../include/api.php';

class BaseApi extends BaseModule
{
	/** @deprecated Use OAuth class constant */
	const SCOPE_READ   = 'read';
	/** @deprecated Use OAuth class constant */
	const SCOPE_WRITE  = 'write';
	/** @deprecated Use OAuth class constant */
	const SCOPE_FOLLOW = 'follow';
	/** @deprecated Use OAuth class constant */
	const SCOPE_PUSH   = 'push';

	/**
	 * @var string json|xml|rss|atom
	 */
	protected static $format = 'json';

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
		Logger::info('Unimplemented API call', ['method' => $method, 'path' => $path, 'agent' => $_SERVER['HTTP_USER_AGENT'] ?? '', 'request' => HTTPInputData::process()]);
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
	public static function getRequest(array $defaults)
	{
		$httpinput = HTTPInputData::process();
		$input = array_merge($httpinput['variables'], $httpinput['files'], $_REQUEST);

		$request = [];

		foreach ($defaults as $parameter => $defaultvalue) {
			if (is_string($defaultvalue)) {
				$request[$parameter] = $input[$parameter] ?? $defaultvalue;
			} elseif (is_int($defaultvalue)) {
				$request[$parameter] = (int)($input[$parameter] ?? $defaultvalue);
			} elseif (is_float($defaultvalue)) {
				$request[$parameter] = (float)($input[$parameter] ?? $defaultvalue);
			} elseif (is_array($defaultvalue)) {
				$request[$parameter] = $input[$parameter] ?? [];
			} elseif (is_bool($defaultvalue)) {
				$request[$parameter] = in_array(strtolower($input[$parameter] ?? ''), ['true', '1']);
			} else {
				Logger::notice('Unhandled default value type', ['parameter' => $parameter, 'type' => gettype($defaultvalue)]);
			}
		}

		foreach ($input ?? [] as $parameter => $value) {
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
	 * Log in user via OAuth1 or Simple HTTP Auth.
	 *
	 * Simple Auth allow username in form of <pre>user@server</pre>, ignoring server part
	 *
	 * @param string $scope the requested scope (read, write, follow)
	 *
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
		$token = OAuth::getCurrentApplicationToken();
		if (!empty($token)) {
			if (!OAuth::isAllowedScope($scope)) {
				DI::mstdnError()->Forbidden();
			}
			$uid = OAuth::getCurrentUserID();
		}

		if (empty($uid)) {
			// The execution stops here if no one is logged in
			BasicAuth::getCurrentUserID(true);
		}
	}

	/**
	 * Get current application token
	 *
	 * @return array token
	 */
	protected static function getCurrentApplication()
	{
		$token = OAuth::getCurrentApplicationToken();

		if (empty($token)) {
			$token = BasicAuth::getCurrentApplicationToken();
		}

		return $token;
	}

	/**
	 * Get current user id, returns 0 if not logged in
	 *
	 * @return int User ID
	 */
	public static function getCurrentUserID()
	{
		$uid = OAuth::getCurrentUserID();

		if (empty($uid)) {
			$uid = BasicAuth::getCurrentUserID(false);
		}

		return (int)$uid;
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
