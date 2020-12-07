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

namespace Friendica\Module\Debug;

use Friendica\BaseModule;
use Friendica\Core\Installer;
use Friendica\DI;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Temporal;

class Localtime extends BaseModule
{
	public static function post(array $parameters = [])
	{
		$time = ($_REQUEST['time'] ?? '') ?: 'now';

		$bd_format = DI::l10n()->t('l F d, Y \@ g:i A');

		if (!empty($_POST['timezone'])) {
			DI::app()->data['mod-localtime'] = DateTimeFormat::convert($time, $_POST['timezone'], 'UTC', $bd_format);
		}
	}

	public static function content(array $parameters = [])
	{
		$app = DI::app();

		$time = ($_REQUEST['time'] ?? '') ?: 'now';

		$output  = '<h3>' . DI::l10n()->t('Time Conversion') . '</h3>';
		$output .= '<p>' . DI::l10n()->t('Friendica provides this service for sharing events with other networks and friends in unknown timezones.') . '</p>';
		$output .= '<p>' . DI::l10n()->t('UTC time: %s', $time) . '</p>';

		if (!empty($_REQUEST['timezone'])) {
			$output .= '<p>' . DI::l10n()->t('Current timezone: %s', $_REQUEST['timezone']) . '</p>';
		}

		if (!empty($app->data['mod-localtime'])) {
			$output .= '<p>' . DI::l10n()->t('Converted localtime: %s', $app->data['mod-localtime']) . '</p>';
		}

		$output .= '<form action ="' . DI::baseUrl()->get() . '/localtime?time=' . $time . '" method="post" >';
		$output .= '<p>' . DI::l10n()->t('Please select your timezone:') . '</p>';
		$output .= Temporal::getTimezoneSelect(($_REQUEST['timezone'] ?? '') ?: Installer::DEFAULT_TZ);
		$output .= '<input type="submit" name="submit" value="' . DI::l10n()->t('Submit') . '" /></form>';

		return $output;
	}
}
