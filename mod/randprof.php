<?php

if(! function_exists('randprof_init')) {
function randprof_init(&$a) {
	require_once('include/Contact.php');
	$x = random_profile();
	if($x)
		goaway(zrl($x));
	goaway($a->get_baseurl() . '/profile');
}
}
