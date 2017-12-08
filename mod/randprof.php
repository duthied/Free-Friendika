<?php

use Friendica\App;
use Friendica\Core\System;
use Friendica\Model\GContact;

function randprof_init(App $a) {
	$x = GContact::getRandomUrl();

	if ($x) {
		goaway(zrl($x));
	}

	goaway(System::baseUrl() . '/profile');
}
