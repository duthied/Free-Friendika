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

namespace Friendica\Core;

use Friendica\DI;
use Friendica\Network\HTTPException\InternalServerErrorException;
use Friendica\Util\XML;

/**
 * Contains the class with system relevant stuff
 */
class System
{
	/**
	 * Returns a string with a callstack. Can be used for logging.
	 *
	 * @param integer $depth  How many calls to include in the stacks after filtering
	 * @param int     $offset How many calls to shave off the top of the stack, for example if
	 *                        this is called from a centralized method that isn't relevant to the callstack
	 * @return string
	 */
	public static function callstack(int $depth = 4, int $offset = 0)
	{
		$trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);

		// We remove at least the first two items from the list since they contain data that we don't need.
		$trace = array_slice($trace, 2 + $offset);

		$callstack = [];
		$previous = ['class' => '', 'function' => '', 'database' => false];

		// The ignore list contains all functions that are only wrapper functions
		$ignore = ['call_user_func_array'];

		while ($func = array_pop($trace)) {
			if (!empty($func['class'])) {
				if (in_array($previous['function'], ['insert', 'fetch', 'toArray', 'exists', 'count', 'selectFirst', 'selectToArray',
					'select', 'update', 'delete', 'selectFirstForUser', 'selectForUser'])
					&& (substr($previous['class'], 0, 15) === 'Friendica\Model')) {
					continue;
				}

				// Don't show multiple calls from the Database classes to show the essential parts of the callstack
				$func['database'] = in_array($func['class'], ['Friendica\Database\DBA', 'Friendica\Database\Database']);
				if (!$previous['database'] || !$func['database']) {	
					$classparts = explode("\\", $func['class']);
					$callstack[] = array_pop($classparts).'::'.$func['function'];
					$previous = $func;
				}
			} elseif (!in_array($func['function'], $ignore)) {
				$func['database'] = ($func['function'] == 'q');
				$callstack[] = $func['function'];
				$func['class'] = '';
				$previous = $func;
			}
		}

		$callstack2 = [];
		while ((count($callstack2) < $depth) && (count($callstack) > 0)) {
			$callstack2[] = array_pop($callstack);
		}

		return implode(', ', $callstack2);
	}

	/**
	 * Generic XML return
	 * Outputs a basic dfrn XML status structure to STDOUT, with a <status> variable
	 * of $st and an optional text <message> of $message and terminates the current process.
	 *
	 * @param        $st
	 * @param string $message
	 * @throws \Exception
	 */
	public static function xmlExit($st, $message = '')
	{
		$result = ['status' => $st];

		if ($message != '') {
			$result['message'] = $message;
		}

		if ($st) {
			Logger::log('xml_status returning non_zero: ' . $st . " message=" . $message);
		}

		header("Content-type: text/xml");

		$xmldata = ["result" => $result];

		echo XML::fromArray($xmldata, $xml);

		exit();
	}

	/**
	 * Send HTTP status header and exit.
	 *
	 * @param integer $val     HTTP status result value
	 * @param string  $message Error message. Optional.
	 * @param string  $content Response body. Optional.
	 * @throws \Exception
	 */
	public static function httpExit($val, $message = '', $content = '')
	{
		Logger::log('http_status_exit ' . $val);
		header($_SERVER["SERVER_PROTOCOL"] . ' ' . $val . ' ' . $message);

		echo $content;

		exit();
	}

	public static function jsonError($httpCode, $data, $content_type = 'application/json')
	{
		header($_SERVER["SERVER_PROTOCOL"] . ' ' . $httpCode);
		self::jsonExit($data, $content_type);
	}

	/**
	 * Encodes content to json.
	 *
	 * This function encodes an array to json format
	 * and adds an application/json HTTP header to the output.
	 * After finishing the process is getting killed.
	 *
	 * @param mixed   $x The input content.
	 * @param string  $content_type Type of the input (Default: 'application/json').
	 * @param integer $options JSON options
	 */
	public static function jsonExit($x, $content_type = 'application/json', int $options = 0) {
		header("Content-type: $content_type");
		echo json_encode($x, $options);
		exit();
	}

	/**
	 * Generates a random string in the UUID format
	 *
	 * @param bool|string $prefix A given prefix (default is empty)
	 * @return string a generated UUID
	 * @throws \Exception
	 */
	public static function createUUID($prefix = '')
	{
		$guid = System::createGUID(32, $prefix);
		return substr($guid, 0, 8) . '-' . substr($guid, 8, 4) . '-' . substr($guid, 12, 4) . '-' . substr($guid, 16, 4) . '-' . substr($guid, 20, 12);
	}

	/**
	 * Generates a GUID with the given parameters
	 *
	 * @param int         $size   The size of the GUID (default is 16)
	 * @param bool|string $prefix A given prefix (default is empty)
	 * @return string a generated GUID
	 * @throws \Exception
	 */
	public static function createGUID($size = 16, $prefix = '')
	{
		if (is_bool($prefix) && !$prefix) {
			$prefix = '';
		} elseif (empty($prefix)) {
			$prefix = hash('crc32', DI::baseUrl()->getHostname());
		}

		while (strlen($prefix) < ($size - 13)) {
			$prefix .= mt_rand();
		}

		if ($size >= 24) {
			$prefix = substr($prefix, 0, $size - 22);
			return str_replace('.', '', uniqid($prefix, true));
		} else {
			$prefix = substr($prefix, 0, max($size - 13, 0));
			return uniqid($prefix);
		}
	}

	/**
	 * Returns the current Load of the System
	 *
	 * @return integer
	 */
	public static function currentLoad()
	{
		if (!function_exists('sys_getloadavg')) {
			return false;
		}

		$load_arr = sys_getloadavg();

		if (!is_array($load_arr)) {
			return false;
		}

		return max($load_arr[0], $load_arr[1]);
	}

	/**
	 * Redirects to an external URL (fully qualified URL)
	 * If you want to route relative to the current Friendica base, use App->internalRedirect()
	 *
	 * @param string $url  The new Location to redirect
	 * @param int    $code The redirection code, which is used (Default is 302)
	 *
	 * @throws InternalServerErrorException If the URL is not fully qualified
	 */
	public static function externalRedirect($url, $code = 302)
	{
		if (empty(parse_url($url, PHP_URL_SCHEME))) {
			throw new InternalServerErrorException("'$url' is not a fully qualified URL, please use App->internalRedirect() instead");
		}

		switch ($code) {
			case 302:
				// this is the default code for a REDIRECT
				// We don't need a extra header here
				break;
			case 301:
				header('HTTP/1.1 301 Moved Permanently');
				break;
			case 307:
				header('HTTP/1.1 307 Temporary Redirect');
				break;
		}

		header("Location: $url");
		exit();
	}

	/**
	 * Returns the system user that is executing the script
	 *
	 * This mostly returns something like "www-data".
	 *
	 * @return string system username
	 */
	public static function getUser()
	{
		if (!function_exists('posix_getpwuid') || !function_exists('posix_geteuid')) {
			return '';
		}

		$processUser = posix_getpwuid(posix_geteuid());
		return $processUser['name'];
	}

	/**
	 * Checks if a given directory is usable for the system
	 *
	 * @param      $directory
	 * @param bool $check_writable
	 *
	 * @return boolean the directory is usable
	 */
	public static function isDirectoryUsable($directory, $check_writable = true)
	{
		if ($directory == '') {
			Logger::log('Directory is empty. This shouldn\'t happen.', Logger::DEBUG);
			return false;
		}

		if (!file_exists($directory)) {
			Logger::log('Path "' . $directory . '" does not exist for user ' . static::getUser(), Logger::DEBUG);
			return false;
		}

		if (is_file($directory)) {
			Logger::log('Path "' . $directory . '" is a file for user ' . static::getUser(), Logger::DEBUG);
			return false;
		}

		if (!is_dir($directory)) {
			Logger::log('Path "' . $directory . '" is not a directory for user ' . static::getUser(), Logger::DEBUG);
			return false;
		}

		if ($check_writable && !is_writable($directory)) {
			Logger::log('Path "' . $directory . '" is not writable for user ' . static::getUser(), Logger::DEBUG);
			return false;
		}

		return true;
	}

	/**
	 * Exit method used by asynchronous update modules
	 *
	 * @param string $o
	 */
	public static function htmlUpdateExit($o)
	{
		header("Content-type: text/html");
		echo "<!DOCTYPE html><html><body>\r\n";
		// We can remove this hack once Internet Explorer recognises HTML5 natively
		echo "<section>";
		// reportedly some versions of MSIE don't handle tabs in XMLHttpRequest documents very well
		echo str_replace("\t", "       ", $o);
		echo "</section>";
		echo "</body></html>\r\n";
		exit();
	}

	/// @todo Move the following functions from boot.php
	/*
	function local_user()
	function public_contact()
	function remote_user()
	function notice($s)
	function info($s)
	function is_site_admin()
	function get_temppath()
	function get_cachefile($file, $writemode = true)
	function get_itemcachepath()
	function get_spoolpath()
	*/
}
