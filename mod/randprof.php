<?php

use Friendica\App;
use Friendica\Core\System;
use Friendica\Model\GlobalContact;

function randprof_init(App $a) {
	$x = GlobalContact::getRandomUrl();

	if ($x) {
		goaway(zrl($x));
	}

	goaway(System::baseUrl() . '/profile');
}
