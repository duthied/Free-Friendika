<?php
namespace Friendica\Core;

use Friendica\App;

/**
 * @file include/Core/System.php
 *
 * @brief Contains the class with system relevant stuff
 */


/**
 * @brief System methods
 */
class System {

	private static $a;

	/**
	 * @brief Initializes the static class variable
	 */
	private static function init() {
		global $a;

		if (!is_object(self::$a)) {
			self::$a = $a;
		}
	}

	/**
	 * @brief Retrieves the Friendica instance base URL
	 *
	 * @param bool $ssl Whether to append http or https under SSL_POLICY_SELFSIGN
	 * @return string Friendica server base URL
	 */
	public static function baseUrl($ssl = false) {
		self::init();
		return self::$a->get_baseurl($ssl);
	}

	/**
	 * @brief Removes the baseurl from an url. This avoids some mixed content problems.
	 *
	 * @param string $orig_url
	 *
	 * @return string The cleaned url
	 */
	public static function removedBaseUrl($orig_url) {
		self::init();
		return self::$a->remove_baseurl($orig_url);
	}

	/**
	 * @brief Returns a string with a callstack. Can be used for logging.
	 *
	 * @return string
	 */
	public static function callstack($depth = 4) {
		$trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, $depth + 2);

		// We remove the first two items from the list since they contain data that we don't need.
		array_shift($trace);
		array_shift($trace);

		$callstack = array();
		foreach ($trace AS $func) {
			if (!empty($func['class'])) {
				$callstack[] = $func['class'].'::'.$func['function'];
			} else {
				$callstack[] = $func['function'];
			}
		}

		return implode(', ', $callstack);
	}

	/// @todo Move the following functions from boot.php
	/*
	function get_guid($size = 16, $prefix = "")
	function killme()
	function goaway($s)
	function local_user()
	function public_contact()
	function remote_user()
	function notice($s)
	function info($s)
	function is_site_admin()
	function random_digits($digits)
	function get_server()
	function get_temppath()
	function get_cachefile($file, $writemode = true)
	function get_itemcachepath()
	function get_spoolpath()
	function current_load()
	*/
}
