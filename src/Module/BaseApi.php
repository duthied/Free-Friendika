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
use Friendica\Model\Post;
use Friendica\Network\HTTPException;
use Friendica\Security\BasicAuth;
use Friendica\Security\OAuth;
use Friendica\Util\Arrays;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\HTTPInputData;
use Friendica\Util\XML;

require_once __DIR__ . '/../../include/api.php';

class BaseApi extends BaseModule
{
	const SCOPE_READ   = 'read';
	const SCOPE_WRITE  = 'write';
	const SCOPE_FOLLOW = 'follow';
	const SCOPE_PUSH   = 'push';

	/**
	 * @var array
	 */
	protected static $boundaries = [];

	/**
	 * @var array
	 */
	protected static $request = [];

	public static function delete(array $parameters = [])
	{
		self::checkAllowedScope(self::SCOPE_WRITE);

		if (!DI::app()->isLoggedIn()) {
			throw new HTTPException\ForbiddenException(DI::l10n()->t('Permission denied.'));
		}
	}

	public static function patch(array $parameters = [])
	{
		self::checkAllowedScope(self::SCOPE_WRITE);

		if (!DI::app()->isLoggedIn()) {
			throw new HTTPException\ForbiddenException(DI::l10n()->t('Permission denied.'));
		}
	}

	public static function post(array $parameters = [])
	{
		self::checkAllowedScope(self::SCOPE_WRITE);

		if (!DI::app()->isLoggedIn()) {
			throw new HTTPException\ForbiddenException(DI::l10n()->t('Permission denied.'));
		}
	}

	public static function put(array $parameters = [])
	{
		self::checkAllowedScope(self::SCOPE_WRITE);

		if (!DI::app()->isLoggedIn()) {
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

		self::$request    = $input;
		self::$boundaries = [];

		unset(self::$request['pagename']);

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
	 * Set boundaries for the "link" header
	 * @param array $boundaries
	 * @param int $id
	 * @return array
	 */
	protected static function setBoundaries(int $id)
	{
		if (!isset(self::$boundaries['min'])) {
			self::$boundaries['min'] = $id;
		}

		if (!isset(self::$boundaries['max'])) {
			self::$boundaries['max'] = $id;
		}

		self::$boundaries['min'] = min(self::$boundaries['min'], $id);
		self::$boundaries['max'] = max(self::$boundaries['max'], $id);
	}

	/**
	 * Set the "link" header with "next" and "prev" links
	 * @return void
	 */
	protected static function setLinkHeader()
	{
		if (empty(self::$boundaries)) {
			return;
		}

		$request = self::$request;

		unset($request['min_id']);
		unset($request['max_id']);
		unset($request['since_id']);

		$prev_request = $next_request = $request;

		$prev_request['min_id'] = self::$boundaries['max'];
		$next_request['max_id'] = self::$boundaries['min'];

		$command = DI::baseUrl() . '/' . DI::args()->getCommand();

		$prev = $command . '?' . http_build_query($prev_request);
		$next = $command . '?' . http_build_query($next_request);

		header('Link: <' . $next . '>; rel="next", <' . $prev . '>; rel="prev"');
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
	 * Check if the provided scope does exist.
	 * halts execution on missing scope or when not logged in.
	 *
	 * @param string $scope the requested scope (read, write, follow, push)
	 */
	public static function checkAllowedScope(string $scope)
	{
		$token = self::getCurrentApplication();

		if (empty($token)) {
			Logger::notice('Empty application token');
			DI::mstdnError()->Forbidden();
		}

		if (!isset($token[$scope])) {
			Logger::warning('The requested scope does not exist', ['scope' => $scope, 'application' => $token]);
			DI::mstdnError()->Forbidden();
		}

		if (empty($token[$scope])) {
			Logger::warning('The requested scope is not allowed', ['scope' => $scope, 'application' => $token]);
			DI::mstdnError()->Forbidden();
		}
	}

	public static function checkThrottleLimit()
	{
		$uid = self::getCurrentUserID();

		// Check for throttling (maximum posts per day, week and month)
		$throttle_day = DI::config()->get('system', 'throttle_limit_day');
		if ($throttle_day > 0) {
			$datefrom = date(DateTimeFormat::MYSQL, time() - 24*60*60);

			$condition = ["`gravity` = ? AND `uid` = ? AND `wall` AND `received` > ?", GRAVITY_PARENT, $uid, $datefrom];
			$posts_day = Post::countThread($condition);

			if ($posts_day > $throttle_day) {
				Logger::info('Daily posting limit reached', ['uid' => $uid, 'posts' => $posts_day, 'limit' => $throttle_day]);
				$error = DI::l10n()->t('Too Many Requests');
				$error_description = DI::l10n()->tt("Daily posting limit of %d post reached. The post was rejected.", "Daily posting limit of %d posts reached. The post was rejected.", $throttle_day);
				$errorobj = new \Friendica\Object\Api\Mastodon\Error($error, $error_description);
				System::jsonError(429, $errorobj->toArray());
			}
		}

		$throttle_week = DI::config()->get('system', 'throttle_limit_week');
		if ($throttle_week > 0) {
			$datefrom = date(DateTimeFormat::MYSQL, time() - 24*60*60*7);

			$condition = ["`gravity` = ? AND `uid` = ? AND `wall` AND `received` > ?", GRAVITY_PARENT, $uid, $datefrom];
			$posts_week = Post::countThread($condition);

			if ($posts_week > $throttle_week) {
				Logger::info('Weekly posting limit reached', ['uid' => $uid, 'posts' => $posts_week, 'limit' => $throttle_week]);
				$error = DI::l10n()->t('Too Many Requests');
				$error_description = DI::l10n()->tt("Weekly posting limit of %d post reached. The post was rejected.", "Weekly posting limit of %d posts reached. The post was rejected.", $throttle_week);
				$errorobj = new \Friendica\Object\Api\Mastodon\Error($error, $error_description);
				System::jsonError(429, $errorobj->toArray());
			}
		}

		$throttle_month = DI::config()->get('system', 'throttle_limit_month');
		if ($throttle_month > 0) {
			$datefrom = date(DateTimeFormat::MYSQL, time() - 24*60*60*30);

			$condition = ["`gravity` = ? AND `uid` = ? AND `wall` AND `received` > ?", GRAVITY_PARENT, $uid, $datefrom];
			$posts_month = Post::countThread($condition);

			if ($posts_month > $throttle_month) {
				Logger::info('Monthly posting limit reached', ['uid' => $uid, 'posts' => $posts_month, 'limit' => $throttle_month]);
				$error = DI::l10n()->t('Too Many Requests');
				$error_description = DI::l10n()->t("Monthly posting limit of %d post reached. The post was rejected.", "Monthly posting limit of %d posts reached. The post was rejected.", $throttle_month);
				$errorobj = new \Friendica\Object\Api\Mastodon\Error($error, $error_description);
				System::jsonError(429, $errorobj->toArray());
			}
		}
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
		return api_get_user($contact_id);
	}

	/**
	 * Exit with error code
	 *
	 * @param int $code
	 * @param string $description
	 * @param string $message
	 * @param string|null $format
	 * @return void
	 */
	public static function error(int $code, string $description, string $message, string $format = null)
	{
		$error = [
			'error'   => $message ?: $description,
			'code'    => $code . ' ' . $description,
			'request' => DI::args()->getQueryString()
		];

		header($_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.1' . ' ' . $code . ' ' . $description);

		self::exit('status', ['status' => $error], $format);
	}

	/**
	 * Outputs formatted data according to the data type and then exits the execution.
	 *
	 * @param string $root_element
	 * @param array  $data         An array with a single element containing the returned result
	 * @param string $format       Output format (xml, json, rss, atom)
	 * @return false|string
	 */
	protected static function exit(string $root_element, array $data, string $format = null)
	{
		$format = $format ?? 'json';

		$return = self::formatData($root_element, $format, $data);

		switch ($format) {
			case 'xml':
				header('Content-Type: text/xml');
				break;
			case 'json':
				header('Content-Type: application/json');
				if (!empty($return)) {
					$json = json_encode(end($return));
					if (!empty($_GET['callback'])) {
						$json = $_GET['callback'] . '(' . $json . ')';
					}
					$return = $json;
				}
				break;
			case 'rss':
				header('Content-Type: application/rss+xml');
				$return  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . $return;
				break;
			case 'atom':
				header('Content-Type: application/atom+xml');
				$return = '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . $return;
				break;
		}

		echo $return;
		exit;
	}

	/**
	 * Formats the data according to the data type
	 *
	 * @param string $root_element Name of the root element
	 * @param string $type         Return type (atom, rss, xml, json)
	 * @param array  $data         JSON style array
	 *
	 * @return array|string (string|array) XML data or JSON data
	 */
	public static function formatData($root_element, string $type, array $data)
	{
		switch ($type) {
			case 'atom':
			case 'rss':
			case 'xml':
				$ret = self::createXML($data, $root_element);
				break;
			case 'json':
			default:
				$ret = $data;
				break;
		}
		return $ret;
	}

	/**
	* Callback function to transform the array in an array that can be transformed in a XML file
	*
	* @param mixed  $item Array item value
	* @param string $key  Array key
	*
	* @return boolean
	*/
	public static function reformatXML(&$item, &$key)
	{
		if (is_bool($item)) {
			$item = ($item ? 'true' : 'false');
		}

		if (substr($key, 0, 10) == 'statusnet_') {
			$key = 'statusnet:'.substr($key, 10);
		} elseif (substr($key, 0, 10) == 'friendica_') {
			$key = 'friendica:'.substr($key, 10);
		}
		return true;
	}

	/**
	 * Creates the XML from a JSON style array
	 *
	 * @param array  $data         JSON style array
	 * @param string $root_element Name of the root element
	 *
	 * @return string The XML data
	 */
	public static function createXML(array $data, $root_element)
	{
		$childname = key($data);
		$data2 = array_pop($data);

		$namespaces = ['' => 'http://api.twitter.com',
			'statusnet' => 'http://status.net/schema/api/1/',
			'friendica' => 'http://friendi.ca/schema/api/1/',
			'georss' => 'http://www.georss.org/georss'];

		/// @todo Auto detection of needed namespaces
		if (in_array($root_element, ['ok', 'hash', 'config', 'version', 'ids', 'notes', 'photos'])) {
			$namespaces = [];
		}

		if (is_array($data2)) {
			$key = key($data2);
			Arrays::walkRecursive($data2, ['Friendica\Module\BaseApi', 'reformatXML']);

			if ($key == '0') {
				$data4 = [];
				$i = 1;

				foreach ($data2 as $item) {
					$data4[$i++ . ':' . $childname] = $item;
				}

				$data2 = $data4;
			}
		}

		$data3 = [$root_element => $data2];

		$ret = XML::fromArray($data3, $xml, false, $namespaces);
		return $ret;
	}
}
