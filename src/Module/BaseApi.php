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

require_once __DIR__ . '/../../include/api.php';

class BaseApi extends BaseModule
{
	/**
	 * @var string json|xml|rss|atom
	 */
	protected static $format = 'json';
	/**
	 * @var bool|int
	 */
	protected static $current_user_id;

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

	public static function unsupported(string $method = 'all')
	{
		$path = DI::args()->getQueryString();
		Logger::info('Unimplemented API call', ['method' => $method, 'path' => $path, 'agent' => $_SERVER['HTTP_USER_AGENT'] ?? '', 'request' => $_REQUEST ?? []]);
		$error = DI::l10n()->t('API endpoint %s %s is not implemented', strtoupper($method), $path);
		$error_description = DI::l10n()->t('The API endpoint is currently not implemented but might be in the future.');;
		$errorobj = new \Friendica\Object\Api\Mastodon\Error($error, $error_description);
		System::jsonError(501, $errorobj->toArray());
	}

	/**
	 * Log in user via OAuth1 or Simple HTTP Auth.
	 *
	 * Simple Auth allow username in form of <pre>user@server</pre>, ignoring server part
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
	protected static function login()
	{
		if (empty(self::$current_user_id)) {
			self::$current_user_id = self::getUserByBearer();
		}

		if (empty(self::$current_user_id)) {
			api_login(DI::app());
		}

		self::$current_user_id = api_user();

		return (bool)self::$current_user_id;
	}

	/**
	 * Get current user id, returns 0 if not logged in
	 *
	 * @return int User ID
	 */
	protected static function getCurrentUserID()
	{
		if (empty(self::$current_user_id)) {
			self::$current_user_id = self::getUserByBearer();
		}

		if (empty(self::$current_user_id)) {
			api_login(DI::app(), false);

			self::$current_user_id = api_user();
		}

		return (int)self::$current_user_id;
	}

	private static function getUserByBearer()
	{
		$authorization = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
		$authorization = $_SERVER['AUTHORIZATION'] ?? $authorization;

		if (substr($authorization, 0, 7) != 'Bearer ') {
			return 0;
		}

		$bearer = trim(substr($authorization, 7));
		$condition = ['access_token' => $bearer];
		$token = DBA::selectFirst('application-token', ['uid'], $condition);
		if (!DBA::isResult($token)) {
			Logger::warning('Token not found', $condition);
			return 0;
		}
		Logger::info('Token found', $token);
		return $token['uid'];
	}

	public static function getApplication()
	{
		$redirect_uri  = !isset($_REQUEST['redirect_uri']) ? '' : $_REQUEST['redirect_uri'];
		$client_id     = !isset($_REQUEST['client_id']) ? '' : $_REQUEST['client_id'];
		$client_secret = !isset($_REQUEST['client_secret']) ? '' : $_REQUEST['client_secret'];

		if ((empty($redirect_uri) && empty($client_secret)) || empty($client_id)) {
			Logger::warning('Incomplete request', ['request' => $_REQUEST]);
			return [];
		}

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

	public static function getTokenForUser(array $application, int $uid)
	{
		$code         = bin2hex(random_bytes(32));
		$access_token = bin2hex(random_bytes(32));

		$fields = ['application-id' => $application['id'], 'uid' => $uid, 'code' => $code, 'access_token' => $access_token, 'created_at' => DateTimeFormat::utcNow(DateTimeFormat::MYSQL)];
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
