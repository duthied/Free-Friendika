<?php
/**
 * @copyright Copyright (C) 2020, Friendica
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

use DateTime;
use DateTimeZone;
use Friendica\Core\Renderer;
use Friendica\Database\DBA;
use Friendica\DI;

/**
 * Temporal class
 */
class Temporal
{
	/**
	 * Two-level sort for timezones.
	 *
	 * @param string $a
	 * @param string $b
	 * @return int
	 */
	private static function timezoneCompareCallback($a, $b)
	{
		if (strstr($a, '/') && strstr($b, '/')) {
			if (DI::l10n()->t($a) == DI::l10n()->t($b)) {
				return 0;
			}
			return (DI::l10n()->t($a) < DI::l10n()->t($b)) ? -1 : 1;
		}

		if (strstr($a, '/')) {
			return -1;
		} elseif (strstr($b, '/')) {
			return 1;
		} elseif (DI::l10n()->t($a) == DI::l10n()->t($b)) {
			return 0;
		}

		return (DI::l10n()->t($a) < DI::l10n()->t($b)) ? -1 : 1;
	}

	/**
	 * Emit a timezone selector grouped (primarily) by continent
	 *
	 * @param string $current Timezone
	 * @return string Parsed HTML output
	 */
	public static function getTimezoneSelect($current = 'America/Los_Angeles')
	{
		$timezone_identifiers = DateTimeZone::listIdentifiers();

		$o = '<select id="timezone_select" name="timezone">';

		usort($timezone_identifiers, [__CLASS__, 'timezoneCompareCallback']);
		$continent = '';
		foreach ($timezone_identifiers as $value) {
			$ex = explode("/", $value);
			if (count($ex) > 1) {
				if ($ex[0] != $continent) {
					if ($continent != '') {
						$o .= '</optgroup>';
					}
					$continent = $ex[0];
					$o .= '<optgroup label="' . DI::l10n()->t($continent) . '">';
				}
				if (count($ex) > 2) {
					$city = substr($value, strpos($value, '/') + 1);
				} else {
					$city = $ex[1];
				}
			} else {
				$city = $ex[0];
				if ($continent != DI::l10n()->t('Miscellaneous')) {
					$o .= '</optgroup>';
					$continent = DI::l10n()->t('Miscellaneous');
					$o .= '<optgroup label="' . DI::l10n()->t($continent) . '">';
				}
			}
			$city = str_replace('_', ' ', DI::l10n()->t($city));
			$selected = (($value == $current) ? " selected=\"selected\" " : "");
			$o .= "<option value=\"$value\" $selected >$city</option>";
		}
		$o .= '</optgroup></select>';
		return $o;
	}

	/**
	 * Generating a Timezone selector
	 *
	 * Return a select using 'field_select_raw' template, with timezones
	 * grouped (primarily) by continent
	 * arguments follow convention as other field_* template array:
	 * 'name', 'label', $value, 'help'
	 *
	 * @param string $name    Name of the selector
	 * @param string $label   Label for the selector
	 * @param string $current Timezone
	 * @param string $help    Help text
	 *
	 * @return string Parsed HTML
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function getTimezoneField($name = 'timezone', $label = '', $current = 'America/Los_Angeles', $help = '')
	{
		$options = self::getTimezoneSelect($current);
		$options = str_replace('<select id="timezone_select" name="timezone">', '', $options);
		$options = str_replace('</select>', '', $options);

		$tpl = Renderer::getMarkupTemplate('field_select_raw.tpl');
		return Renderer::replaceMacros($tpl, [
			'$field' => [$name, $label, $current, $help, $options],
		]);
	}

	/**
	 * Wrapper for date selector, tailored for use in birthday fields.
	 *
	 * @param string $dob Date of Birth
	 * @param string $timezone
	 * @return string Formatted HTML
	 * @throws \Exception
	 */
	public static function getDateofBirthField(string $dob, string $timezone = 'UTC')
	{
		list($year, $month, $day) = sscanf($dob, '%4d-%2d-%2d');

		if ($dob < '0000-01-01') {
			$value = '';
			$age = 0;
		} elseif ($dob < '0001-00-00') {
			$value = substr($dob, 5);
			$age = 0;
		} else {
			$value = DateTimeFormat::utc($dob, 'Y-m-d');
			$age = self::getAgeByTimezone($value, $timezone);
		}

		$tpl = Renderer::getMarkupTemplate("field_input.tpl");
		$o = Renderer::replaceMacros($tpl,
			[
			'$field' => [
				'dob',
				DI::l10n()->t('Birthday:'),
				$value,
				intval($age) > 0 ? DI::l10n()->t('Age: ') . DI::l10n()->tt('%d year old', '%d years old', $age) : '',
				'',
				'placeholder="' . DI::l10n()->t('YYYY-MM-DD or MM-DD') . '"'
			]
		]);

		return $o;
	}

	/**
	 * Returns a date selector
	 *
	 * @param DateTime $min     Minimum date
	 * @param DateTime $max     Maximum date
	 * @param DateTime $default Default date
	 * @param string   $id      ID and name of datetimepicker (defaults to "datetimepicker")
	 *
	 * @return string Parsed HTML output.
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function getDateField($min, $max, $default, $id = 'datepicker')
	{
		return self::getDateTimeField($min, $max, $default, '', $id, true, false, '', '');
	}

	/**
	 * Returns a time selector
	 *
	 * @param string $h  Already selected hour
	 * @param string $m  Already selected minute
	 * @param string $id ID and name of datetimepicker (defaults to "timepicker")
	 *
	 * @return string Parsed HTML output.
	 * @throws \Exception
	 */
	public static function getTimeField($h, $m, $id = 'timepicker')
	{
		return self::getDateTimeField(new DateTime(), new DateTime(), new DateTime("$h:$m"), '', $id, false, true);
	}

	/**
	 * Returns a datetime selector.
	 *
	 * @param DateTime $minDate     Minimum date
	 * @param DateTime $maxDate     Maximum date
	 * @param DateTime $defaultDate Default date
	 * @param          $label
	 * @param string   $id          Id and name of datetimepicker (defaults to "datetimepicker")
	 * @param bool     $pickdate    true to show date picker (default)
	 * @param bool     $picktime    true to show time picker (default)
	 * @param string   $minfrom     set minimum date from picker with id $minfrom (none by default)
	 * @param string   $maxfrom     set maximum date from picker with id $maxfrom (none by default)
	 * @param bool     $required    default false
	 *
	 * @return string Parsed HTML output.
	 *
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @todo  Once browser support is better this could probably be replaced with
	 * native HTML5 date picker.
	 */
	public static function getDateTimeField(
		DateTime $minDate,
		DateTime $maxDate,
		DateTime $defaultDate,
		$label,
		$id       = 'datetimepicker',
		$pickdate = true,
		$picktime = true,
		$minfrom  = '',
		$maxfrom  = '',
		$required = false)
	{
		// First day of the week (0 = Sunday)
		$firstDay = DI::pConfig()->get(local_user(), 'system', 'first_day_of_week', 0);

		$lang = substr(DI::l10n()->getCurrentLang(), 0, 2);

		// Check if the detected language is supported by the picker
		if (!in_array($lang,
				["ar", "ro", "id", "bg", "fa", "ru", "uk", "en", "el", "de", "nl", "tr", "fr", "es", "th", "pl", "pt", "ch", "se", "kr",
				"it", "da", "no", "ja", "vi", "sl", "cs", "hu"])) {
			$lang = 'en';
		}

		$o = '';
		$dateformat = '';

		if ($pickdate) {
			$dateformat .= 'Y-m-d';
		}

		if ($pickdate && $picktime) {
			$dateformat .= ' ';
		}

		if ($picktime) {
			$dateformat .= 'H:i';
		}

		$input_text = $defaultDate ? date($dateformat, $defaultDate->getTimestamp()) : '';

		$readable_format = str_replace(['Y', 'm', 'd', 'H', 'i'], ['yyyy', 'mm', 'dd', 'HH', 'MM'], $dateformat);

		$tpl = Renderer::getMarkupTemplate('field_datetime.tpl');
		$o .= Renderer::replaceMacros($tpl, [
			'$field' => [
				$id,
				$label,
				$input_text,
				'',
				$required ? '*' : '',
				'placeholder="' . $readable_format . '"'
			],
			'$datetimepicker' => [
				'minDate' => $minDate,
				'maxDate' => $maxDate,
				'defaultDate' => $defaultDate,
				'dateformat' => $dateformat,
				'firstDay' => $firstDay,
				'lang' => $lang,
				'minfrom' => $minfrom,
				'maxfrom' => $maxfrom,
			]
		]);

		return $o;
	}

	/**
	 * Returns a relative date string.
	 *
	 * Implements "3 seconds ago" etc.
	 * Based on $posted_date, (UTC).
	 * Results relative to current timezone.
	 * Limited to range of timestamps.
	 *
	 * @param string $posted_date MySQL-formatted date string (YYYY-MM-DD HH:MM:SS)
	 * @param string $format (optional) Parsed with sprintf()
	 *    <tt>%1$d %2$s ago</tt>, e.g. 22 hours ago, 1 minute ago
	 *
	 * @return string with relative date
	 */
	public static function getRelativeDate($posted_date, $format = null)
	{
		$localtime = $posted_date . ' UTC';

		$abs = strtotime($localtime);

		if (is_null($posted_date) || $posted_date <= DBA::NULL_DATETIME || $abs === false) {
			return DI::l10n()->t('never');
		}

		$isfuture = false;
		$etime = time() - $abs;

		if ($etime < 1 && $etime >= 0) {
			return DI::l10n()->t('less than a second ago');
		}

		if ($etime < 0){
			$etime = -$etime;
			$isfuture = true;
		}

		$a = [12 * 30 * 24 * 60 * 60 => [DI::l10n()->t('year'), DI::l10n()->t('years')],
			30 * 24 * 60 * 60 => [DI::l10n()->t('month'), DI::l10n()->t('months')],
			7 * 24 * 60 * 60 => [DI::l10n()->t('week'), DI::l10n()->t('weeks')],
			24 * 60 * 60 => [DI::l10n()->t('day'), DI::l10n()->t('days')],
			60 * 60 => [DI::l10n()->t('hour'), DI::l10n()->t('hours')],
			60 => [DI::l10n()->t('minute'), DI::l10n()->t('minutes')],
			1 => [DI::l10n()->t('second'), DI::l10n()->t('seconds')]
		];

		foreach ($a as $secs => $str) {
			$d = $etime / $secs;
			if ($d >= 1) {
				$r = round($d);
				// translators - e.g. 22 hours ago, 1 minute ago
				if (!$format) {
					if($isfuture){
						$format = DI::l10n()->t('in %1$d %2$s');
					}
					else {
						$format = DI::l10n()->t('%1$d %2$s ago');
					}
				}

				return sprintf($format, $r, (($r == 1) ? $str[0] : $str[1]));
			}
		}
	}

	/**
	 * Returns timezone correct age in years.
	 *
	 * Returns the age in years, given a date of birth and the timezone of the person
	 * whose date of birth is provided.
	 *
	 * @param string $dob       Date of Birth
	 * @param string $owner_tz  (optional) Timezone of the person of interest
	 *
	 * @return int Age in years
	 * @throws \Exception
	 */
	public static function getAgeByTimezone($dob, $owner_tz = '')
	{
		if (!intval($dob)) {
			return 0;
		}

		if (!$owner_tz) {
			$owner_tz = date_default_timezone_get();
		}

		$birthdate = new DateTime($dob . ' 00:00:00', new DateTimeZone($owner_tz));
		$currentDate = new DateTime('now', new DateTimeZone('UTC'));

		$interval = $birthdate->diff($currentDate);

		return $interval->format('%y');
	}

	/**
	 * Get days of a month in a given year.
	 *
	 * Returns number of days in the month of the given year.
	 * $m = 1 is 'January' to match human usage.
	 *
	 * @param int $y Year
	 * @param int $m Month (1=January, 12=December)
	 *
	 * @return int Number of days in the given month
	 */
	public static function getDaysInMonth($y, $m)
	{
		return date('t', mktime(0, 0, 0, $m, 1, $y));
	}

	/**
	 * Returns the first day in month for a given month, year.
	 *
	 * Months start at 1.
	 *
	 * @param int $y Year
	 * @param int $m Month (1=January, 12=December)
	 *
	 * @return string day 0 = Sunday through 6 = Saturday
	 * @throws \Exception
	 */
	private static function getFirstDayInMonth($y, $m)
	{
		$d = sprintf('%04d-%02d-01 00:00', intval($y), intval($m));

		return DateTimeFormat::utc($d, 'w');
	}

	/**
	 * Output a calendar for the given month, year.
	 *
	 * If $links are provided (array), e.g. $links[12] => 'http://mylink' ,
	 * date 12 will be linked appropriately. Today's date is also noted by
	 * altering td class.
	 * Months count from 1.
	 *
	 * @param int    $y     Year
	 * @param int    $m     Month
	 * @param array  $links (default null)
	 * @param string $class
	 *
	 * @return string
	 *
	 * @throws \Exception
	 * @todo  Provide (prev, next) links, define class variations for different size calendars
	 */
	public static function getCalendarTable($y = 0, $m = 0, $links = null, $class = '')
	{
		// month table - start at 1 to match human usage.
		$mtab = [' ',
			'January', 'February', 'March',
			'April', 'May', 'June',
			'July', 'August', 'September',
			'October', 'November', 'December'
		];

		$thisyear = DateTimeFormat::localNow('Y');
		$thismonth = DateTimeFormat::localNow('m');
		if (!$y) {
			$y = $thisyear;
		}

		if (!$m) {
			$m = intval($thismonth);
		}

		$dn = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
		$f = self::getFirstDayInMonth($y, $m);
		$l = self::getDaysInMonth($y, $m);
		$d = 1;
		$dow = 0;
		$started = false;

		if (($y == $thisyear) && ($m == $thismonth)) {
			$tddate = intval(DateTimeFormat::localNow('j'));
		}

		$str_month = DI::l10n()->getDay($mtab[$m]);
		$o = '<table class="calendar' . $class . '">';
		$o .= "<caption>$str_month $y</caption><tr>";
		for ($a = 0; $a < 7; $a ++) {
			$o .= '<th>' . mb_substr(DI::l10n()->getDay($dn[$a]), 0, 3, 'UTF-8') . '</th>';
		}

		$o .= '</tr><tr>';

		while ($d <= $l) {
			if (($dow == $f) && (!$started)) {
				$started = true;
			}

			$today = (((isset($tddate)) && ($tddate == $d)) ? "class=\"today\" " : '');
			$o .= "<td $today>";
			$day = str_replace(' ', '&nbsp;', sprintf('%2.2d', $d));
			if ($started) {
				if (isset($links[$d])) {
					$o .= "<a href=\"{$links[$d]}\">$day</a>";
				} else {
					$o .= $day;
				}

				$d ++;
			} else {
				$o .= '&nbsp;';
			}

			$o .= '</td>';
			$dow ++;
			if (($dow == 7) && ($d <= $l)) {
				$dow = 0;
				$o .= '</tr><tr>';
			}
		}

		if ($dow) {
			for ($a = $dow; $a < 7; $a ++) {
				$o .= '<td>&nbsp;</td>';
			}
		}

		$o .= '</tr></table>' . "\r\n";

		return $o;
	}
}
