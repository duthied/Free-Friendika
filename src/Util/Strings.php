<?php
/**
 * @file src/Util/Strings.php
 */

namespace Friendica\Util;

/**
 * @brief This class contains methods to modify/transform strings.
 */
class Strings
{
    /**
	 * escape text ($str) for XML transport
	 * @param string $str
	 * @return string Escaped text.
	 */
	public static function escape($str)
	{
		$buffer = htmlspecialchars($str, ENT_QUOTES, "UTF-8");
		$buffer = trim($buffer);

		return $buffer;
	}

	/**
	 * undo an escape
	 * @param string $s xml escaped text
	 * @return string unescaped text
	 */
	public static function unescape($s)
	{
		$ret = htmlspecialchars_decode($s, ENT_QUOTES);
		return $ret;
	}

	/**
	 * apply escape() to all values of array $val, recursively
	 * @param array $val
	 * @return array
	 */
	public static function arrayEscape($val)
	{
		if (is_bool($val)) {
			return $val?"true":"false";
		} elseif (is_array($val)) {
			return array_map('XML::arrayEscape', $val);
		}
		return self::escape((string) $val);
	}
}
