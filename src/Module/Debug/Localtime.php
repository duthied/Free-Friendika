<?php

namespace Friendica\Module\Debug;

use Friendica\BaseModule;
use Friendica\Core\Installer;
use Friendica\Core\L10n;
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
