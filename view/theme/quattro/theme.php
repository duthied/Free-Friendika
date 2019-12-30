<?php
/**
 * Name: Quattro
 * Version: 0.6
 * Author: Fabio <http://kirgroup.com/profile/fabrixxm>
 * Maintainer: Fabio <http://kirgroup.com/profile/fabrixxm>
 * Maintainer: Tobias <https://diekershoff.homeunix.net/friendica/profile/tobias>
 */

use Friendica\App;
use Friendica\Core\System;
use Friendica\DI;

function quattro_init(App $a) {
	DI::page()['htmlhead'] .= '<script src="'.System::baseUrl().'/view/theme/quattro/tinycon.min.js"></script>';
	DI::page()['htmlhead'] .= '<script src="'.System::baseUrl().'/view/theme/quattro/js/quattro.js"></script>';;
}
