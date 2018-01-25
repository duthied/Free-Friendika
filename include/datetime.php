<?php
/**
 * @file include/datetime.php
 * @brief Some functions for date and time related tasks.
 */

use Friendica\Model\Contact;
use Friendica\Util\Temporal;

function select_timezone($current = 'America/Los_Angeles') {
	return Temporal::getTimezoneSelect($current);
}

function field_timezone($name='timezone', $label='', $current = 'America/Los_Angeles', $help = ''){
	return Temporal::getTimezoneField($name, $label, $current, $help);
}

function datetime_convert($from = 'UTC', $to = 'UTC', $s = 'now', $fmt = "Y-m-d H:i:s") {
	return Temporal::convert($s, $to, $from, $fmt);
}

function dob($dob) {
	return Temporal::getDateofBirthField($dob);
}

function datesel($min, $max, $default, $id = 'datepicker') {
	return Temporal::getDateField($min, $max, $default, $id);
}

function timesel($h, $m, $id = 'timepicker') {
	return Temporal::getTimeField($h, $m, $id);
}

function datetimesel($min, $max, $default, $label, $id = 'datetimepicker', $pickdate = true, $picktime = true, $minfrom = '', $maxfrom = '', $required = false) {
	return Temporal::getDateTimeField($min, $max, $default, $label, $id, $pickdate, $picktime, $minfrom, $maxfrom, $required);
}

function relative_date($posted_date, $format = null) {
	return Temporal::getRelativeDate($posted_date, $format);
}

function age($dob, $owner_tz = '', $viewer_tz = '') {
	return Temporal::getAgeByTimezone($dob, $owner_tz, $viewer_tz);
}

function get_dim($y, $m) {
	return Temporal::getDaysInMonth($y, $m);
}

function get_first_dim($y,$m) {
	return Temporal::getFirstDayInMonth($y, $m);
}

function cal($y = 0, $m = 0, $links = null, $class = '')
{
	return Temporal::getCalendarTable($y, $m, $links, $class);
}

function update_contact_birthdays() {
	return Contact::updateBirthdays();
}
