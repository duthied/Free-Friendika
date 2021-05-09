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

namespace Friendica\Util;

use Friendica\Core\Logger;
use DateTime;
use DateTimeZone;
use Exception;

/**
 * Temporal class
 */
class DateTimeFormat
{
	const ATOM = 'Y-m-d\TH:i:s\Z';
	const MYSQL = 'Y-m-d H:i:s';
	const HTTP = 'D, d M Y H:i:s \G\M\T';

	/**
	 * convert() shorthand for UTC.
	 *
	 * @param string $time   A date/time string
	 * @param string $format DateTime format string or Temporal constant
	 * @return string
	 * @throws Exception
	 */
	public static function utc($time, $format = self::MYSQL)
	{
		return self::convert($time, 'UTC', 'UTC', $format);
	}

	/**
	 * convert() shorthand for local.
	 *
	 * @param string $time   A date/time string
	 * @param string $format DateTime format string or Temporal constant
	 * @return string
	 * @throws Exception
	 */
	public static function local($time, $format = self::MYSQL)
	{
		return self::convert($time, date_default_timezone_get(), 'UTC', $format);
	}

	/**
	 * convert() shorthand for timezoned now.
	 *
	 * @param        $timezone
	 * @param string $format DateTime format string or Temporal constant
	 * @return string
	 * @throws Exception
	 */
	public static function timezoneNow($timezone, $format = self::MYSQL)
	{
		return self::convert('now', $timezone, 'UTC', $format);
	}

	/**
	 * convert() shorthand for local now.
	 *
	 * @param string $format DateTime format string or Temporal constant
	 * @return string
	 * @throws Exception
	 */
	public static function localNow($format = self::MYSQL)
	{
		return self::local('now', $format);
	}

	/**
	 * convert() shorthand for UTC now.
	 *
	 * @param string $format DateTime format string or Temporal constant
	 * @return string
	 * @throws Exception
	 */
	public static function utcNow($format = self::MYSQL)
	{
		return self::utc('now', $format);
	}

	/**
	 * General purpose date parse/convert/format function.
	 *
	 * @param string $s       Some parseable date/time string
	 * @param string $tz_to   Destination timezone
	 * @param string $tz_from Source timezone
	 * @param string $format  Output format recognised from php's DateTime class
	 *                        http://www.php.net/manual/en/datetime.format.php
	 *
	 * @return string Formatted date according to given format
	 * @throws Exception
	 */
	public static function convert($s = 'now', $tz_to = 'UTC', $tz_from = 'UTC', $format = self::MYSQL)
	{
		// Defaults to UTC if nothing is set, but throws an exception if set to empty string.
		// Provide some sane defaults regardless.
		if ($tz_from === '') {
			$tz_from = 'UTC';
		}

		if ($tz_to === '') {
			$tz_to = 'UTC';
		}

		if (($s === '') || (!is_string($s))) {
			$s = 'now';
		}

		/*
		 * Slight hackish adjustment so that 'zero' datetime actually returns what is intended
		 * otherwise we end up with -0001-11-30 ...
		 * add 32 days so that we at least get year 00, and then hack around the fact that
		 * months and days always start with 1.
		 */
		if (substr($s, 0, 10) <= '0001-01-01') {
			if ($s < '0000-00-00') {
				$s = '0000-00-00';
			}
			$d = new DateTime($s . ' + 32 days', new DateTimeZone('UTC'));
			return str_replace('1', '0', $d->format($format));
		}

		try {
			$from_obj = new DateTimeZone($tz_from);
		} catch (Exception $e) {
			$from_obj = new DateTimeZone('UTC');
		}

		try {
			$d = new DateTime($s, $from_obj);
		} catch (Exception $e) {
			Logger::log('DateTimeFormat::convert: exception: ' . $e->getMessage());
			$d = new DateTime('now', $from_obj);
		}

		try {
			$to_obj = new DateTimeZone($tz_to);
		} catch (Exception $e) {
			$to_obj = new DateTimeZone('UTC');
		}

		$d->setTimeZone($to_obj);

		return $d->format($format);
	}

	/**
	 * Checks, if the given string is a date with the pattern YYYY-MM
	 *
	 * @param string $dateString The given date
	 *
	 * @return boolean True, if the date is a valid pattern
	 */
	public function isYearMonth(string $dateString)
	{
		// Check format (2019-01, 2019-1, 2019-10)
		if (!preg_match('/^([12]\d{3}-(1[0-2]|0[1-9]|\d))$/', $dateString)) {
			return false;
		}

		$date = DateTime::createFromFormat('Y-m', $dateString);

		if (!$date) {
			return false;
		}

		try {
			$now = new DateTime();
		} catch (\Throwable $t) {
			return false;
		}

		if ($date > $now) {
			return false;
		}

		return true;
	}

	/**
	 * Checks, if the given string is a date with the pattern YYYY-MM-DD
	 *
	 * @param string $dateString The given date
	 *
	 * @return boolean True, if the date is a valid pattern
	 */
	public function isYearMonthDay(string $dateString)
	{
		$date = DateTime::createFromFormat('Y-m-d', $dateString);
		if (!$date) {
			return false;
		}

		if (DateTime::getLastErrors()['error_count'] || DateTime::getLastErrors()['warning_count']) {
			return false;
		}

		return true;
	}
}
