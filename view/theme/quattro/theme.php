<?php
/**
 * Name: Quattro
 * Version: 0.6
 * Author: Fabio <http://kirgroup.com/profile/fabrixxm>
 * Maintainer: Fabio <http://kirgroup.com/profile/fabrixxm>
 * Maintainer: Tobias <https://diekershoff.homeunix.net/friendica/profile/tobias>
 */

use Friendica\App;
use Friendica\DI;

function quattro_init(App $a) {
	DI::page()['htmlhead'] .= '<script src="'.DI::baseUrl().'/view/theme/quattro/tinycon.min.js"></script>';
	DI::page()['htmlhead'] .= '<script src="'.DI::baseUrl().'/view/theme/quattro/js/quattro.js"></script>';;
}

/**
 * @param int|null $uid
 * @return null
 * @see \Friendica\Core\Theme::getBackgroundColor()
 * @TODO Implement this function
 */
function quattro_get_background_color(int $uid = null)
{
	return null;
}

/**
 * @param int|null $uid
 * @return null
 * @see \Friendica\Core\Theme::getThemeColor()
 * @TODO Implement this function
 */
function quattro_get_theme_color(int $uid = null)
{
	return null;
}
