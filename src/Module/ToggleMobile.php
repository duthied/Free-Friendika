<?php

namespace Friendica\Module;

use Friendica\BaseModule;

/**
 * Toggles the mobile view (on/off)
 */
class ToggleMobile extends BaseModule
{
	public static function content(array $parameters = [])
	{
		$a = self::getApp();

		if (isset($_GET['off'])) {
			$_SESSION['show-mobile'] = false;
		} else {
			$_SESSION['show-mobile'] = true;
		}

		if (isset($_GET['address'])) {
			$address = $_GET['address'];
		} else {
			$address = '';
		}

		$a->redirect($address);
	}
}
