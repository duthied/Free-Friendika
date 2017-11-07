<?php

/**
 * Theme settings
 */

use Friendica\App;
use Friendica\Core\Config;
use Friendica\Core\PConfig;
use Friendica\Core\System;

function theme_content(App $a) {
	if (!local_user()) {
		return;
	}

	$colorset = PConfig::get( local_user(), 'duepuntozero', 'colorset');
	$user = true;

	return clean_form($a, $colorset, $user);
}

function theme_post(App $a) {
	if (! local_user()) {
		return;
	}

	if (isset($_POST['duepuntozero-settings-submit'])){
		PConfig::set(local_user(), 'duepuntozero', 'colorset', $_POST['duepuntozero_colorset']);
	}
}

function theme_admin(App $a) {
	$colorset = Config::get( 'duepuntozero', 'colorset');
	$user = false;

	return clean_form($a, $colorset, $user);
}

function theme_admin_post(App $a) {
	if (isset($_POST['duepuntozero-settings-submit'])){
		Config::set('duepuntozero', 'colorset', $_POST['duepuntozero_colorset']);
	}
}

/// @TODO $a is no longer used
function clean_form(App $a, &$colorset, $user) {
	$colorset = array(
		'default'     =>t('default'),
		'greenzero'   =>t('greenzero'),
		'purplezero'  =>t('purplezero'),
		'easterbunny' =>t('easterbunny'),
		'darkzero'    =>t('darkzero'),
		'comix'       =>t('comix'),
		'slackr'      =>t('slackr'),
	);

	if ($user) {
		$color = PConfig::get(local_user(), 'duepuntozero', 'colorset');
	} else {
		$color = Config::get( 'duepuntozero', 'colorset');
	}

	$t = get_markup_template("theme_settings.tpl" );
	/// @TODO No need for adding string here, $o is not defined
	$o .= replace_macros($t, array(
		'$submit'   => t('Submit'),
		'$baseurl'  => System::baseUrl(),
		'$title'    => t("Theme settings"),
		'$colorset' => array('duepuntozero_colorset', t('Variations'), $color, '', $colorset),
	));

	return $o;
}
