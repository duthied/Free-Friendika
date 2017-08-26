<?php

use Friendica\App;
use Friendica\Core\System;

function login_content(App $a) {
	if (x($_SESSION, 'theme')) {
		unset($_SESSION['theme']);
	}

	if (x($_SESSION, 'mobile-theme')) {
		unset($_SESSION['mobile-theme']);
	}

	if (local_user()) {
		goaway(System::baseUrl());
	}

	return login(($a->config['register_policy'] == REGISTER_CLOSED) ? false : true);
}
