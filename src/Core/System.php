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

use Exception;
use Friendica\DI;
use Friendica\Network\HTTPException\FoundException;
use Friendica\Network\HTTPException\MovedPermanentlyException;
use Friendica\Network\HTTPException\TemporaryRedirectException;
use Friendica\Util\BasePath;
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
			Logger::notice('xml_status returning non_zero: ' . $st . " message=" . $message);
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
		if ($val >= 400) {
			Logger::debug('Exit with error', ['code' => $val, 'message' => $message, 'callstack' => System::callstack(20), 'method' => $_SERVER['REQUEST_METHOD'], 'agent' => $_SERVER['HTTP_USER_AGENT'] ?? '']);
		}
		header($_SERVER["SERVER_PROTOCOL"] . ' ' . $val . ' ' . $message);

		echo $content;

		exit();
	}

	public static function jsonError($httpCode, $data, $content_type = 'application/json')
	{
		if ($httpCode >= 400) {
			Logger::debug('Exit with error', ['code' => $httpCode, 'content_type' => $content_type, 'callstack' => System::callstack(20), 'method' => $_SERVER['REQUEST_METHOD'], 'agent' => $_SERVER['HTTP_USER_AGENT'] ?? '']);
		}
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
	 */
	public static function externalRedirect($url, $code = 302)
	{
		if (empty(parse_url($url, PHP_URL_SCHEME))) {
			Logger::warning('No fully qualified URL provided', ['url' => $url, 'callstack' => self::callstack(20)]);
			DI::baseUrl()->redirect($url);
		}

		header("Location: $url");

		switch ($code) {
			case 302:
				throw new FoundException();
			case 301:
				throw new MovedPermanentlyException();
			case 307:
				throw new TemporaryRedirectException();
		}

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
			Logger::info('Directory is empty. This shouldn\'t happen.');
			return false;
		}

		if (!file_exists($directory)) {
			Logger::info('Path "' . $directory . '" does not exist for user ' . static::getUser());
			return false;
		}

		if (is_file($directory)) {
			Logger::info('Path "' . $directory . '" is a file for user ' . static::getUser());
			return false;
		}

		if (!is_dir($directory)) {
			Logger::info('Path "' . $directory . '" is not a directory for user ' . static::getUser());
			return false;
		}

		if ($check_writable && !is_writable($directory)) {
			Logger::info('Path "' . $directory . '" is not writable for user ' . static::getUser());
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

	/**
	 * Fetch the temp path of the system
	 *
	 * @return string Path for temp files
	 */
	public static function getTempPath()
	{
		$temppath = DI::config()->get("system", "temppath");

		if (($temppath != "") && System::isDirectoryUsable($temppath)) {
			// We have a temp path and it is usable
			return BasePath::getRealPath($temppath);
		}

		// We don't have a working preconfigured temp path, so we take the system path.
		$temppath = sys_get_temp_dir();

		// Check if it is usable
		if (($temppath != "") && System::isDirectoryUsable($temppath)) {
			// Always store the real path, not the path through symlinks
			$temppath = BasePath::getRealPath($temppath);

			// To avoid any interferences with other systems we create our own directory
			$new_temppath = $temppath . "/" . DI::baseUrl()->getHostname();
			if (!is_dir($new_temppath)) {
				/// @TODO There is a mkdir()+chmod() upwards, maybe generalize this (+ configurable) into a function/method?
				mkdir($new_temppath);
			}

			if (System::isDirectoryUsable($new_temppath)) {
				// The new path is usable, we are happy
				DI::config()->set("system", "temppath", $new_temppath);
				return $new_temppath;
			} else {
				// We can't create a subdirectory, strange.
				// But the directory seems to work, so we use it but don't store it.
				return $temppath;
			}
		}

		// Reaching this point means that the operating system is configured badly.
		return '';
	}

	/**
	 * Returns the path where spool files are stored
	 *
	 * @return string Spool path
	 */
	public static function getSpoolPath()
	{
		$spoolpath = DI::config()->get('system', 'spoolpath');
		if (($spoolpath != "") && System::isDirectoryUsable($spoolpath)) {
			// We have a spool path and it is usable
			return $spoolpath;
		}

		// We don't have a working preconfigured spool path, so we take the temp path.
		$temppath = self::getTempPath();

		if ($temppath != "") {
			// To avoid any interferences with other systems we create our own directory
			$spoolpath = $temppath . "/spool";
			if (!is_dir($spoolpath)) {
				mkdir($spoolpath);
			}

			if (System::isDirectoryUsable($spoolpath)) {
				// The new path is usable, we are happy
				DI::config()->set("system", "spoolpath", $spoolpath);
				return $spoolpath;
			} else {
				// We can't create a subdirectory, strange.
				// But the directory seems to work, so we use it but don't store it.
				return $temppath;
			}
		}

		// Reaching this point means that the operating system is configured badly.
		return "";
	}

	/// @todo Move the following functions from boot.php
	/*
	function local_user()
	function public_contact()
	function remote_user()
	function notice($s)
	function info($s)
	function is_site_admin()
	*/
}
