<?php

use Friendica\App;
use Friendica\Core\Config;
use Friendica\Core\PConfig;
use Friendica\Core\System;

require_once('view/theme/frio/php/Image.php');

function theme_post(App $a) {
	if (!local_user()) {
		return;
	}

	if (isset($_POST['frio-settings-submit'])) {
		PConfig::set(local_user(), 'frio', 'schema',           $_POST["frio_schema"]);
		PConfig::set(local_user(), 'frio', 'nav_bg',           $_POST["frio_nav_bg"]);
		PConfig::set(local_user(), 'frio', 'nav_icon_color',   $_POST["frio_nav_icon_color"]);
		PConfig::set(local_user(), 'frio', 'link_color',       $_POST["frio_link_color"]);
		PConfig::set(local_user(), 'frio', 'background_color', $_POST["frio_background_color"]);
		PConfig::set(local_user(), 'frio', 'contentbg_transp', $_POST["frio_contentbg_transp"]);
		PConfig::set(local_user(), 'frio', 'background_image', $_POST["frio_background_image"]);
		PConfig::set(local_user(), 'frio', 'bg_image_option',  $_POST["frio_bg_image_option"]);
		PConfig::set(local_user(), 'frio', 'css_modified',     time());
	}
}

function theme_admin_post(App $a) {
	if (!local_user()) {
		return;
	}

	if (isset($_POST['frio-settings-submit'])) {
		Config::set('frio', 'schema',           $_POST["frio_schema"]);
		Config::set('frio', 'nav_bg',           $_POST["frio_nav_bg"]);
		Config::set('frio', 'nav_icon_color',   $_POST["frio_nav_icon_color"]);
		Config::set('frio', 'link_color',       $_POST["frio_link_color"]);
		Config::set('frio', 'background_color', $_POST["frio_background_color"]);
		Config::set('frio', 'contentbg_transp', $_POST["frio_contentbg_transp"]);
		Config::set('frio', 'background_image', $_POST["frio_background_image"]);
		Config::set('frio', 'bg_image_option',  $_POST["frio_bg_image_option"]);
		Config::set('frio', 'css_modified',     time());
	}
}

function theme_content(App $a) {
	if (!local_user()) {
		return;
	}
	$arr = array();

	$arr["schema"]           = PConfig::get(local_user(), 'frio', 'schema');
	$arr["nav_bg"]           = PConfig::get(local_user(), 'frio', 'nav_bg');
	$arr["nav_icon_color"]   = PConfig::get(local_user(), 'frio', 'nav_icon_color');
	$arr["link_color"]       = PConfig::get(local_user(), 'frio', 'link_color');
	$arr["bgcolor"]          = PConfig::get(local_user(), 'frio', 'background_color');
	$arr["contentbg_transp"] = PConfig::get(local_user(), 'frio', 'contentbg_transp');
	$arr["background_image"] = PConfig::get(local_user(), 'frio', 'background_image');
	$arr["bg_image_option"]  = PConfig::get(local_user(), 'frio', 'bg_image_option');

	return frio_form($arr);
}

function theme_admin(App $a) {
	if (!local_user()) {
		return;
	}
	$arr = array();

	$arr["schema"]           = Config::get('frio', 'schema');
	$arr["nav_bg"]           = Config::get('frio', 'nav_bg');
	$arr["nav_icon_color"]   = Config::get('frio', 'nav_icon_color');
	$arr["link_color"]       = Config::get('frio', 'link_color');
	$arr["bgcolor"]          = Config::get('frio', 'background_color');
	$arr["contentbg_transp"] = Config::get('frio', 'contentbg_transp');
	$arr["background_image"] = Config::get('frio', 'background_image');
	$arr["bg_image_option"]  = Config::get('frio', 'bg_image_option');

	return frio_form($arr);
}

function frio_form($arr) {
	require_once("view/theme/frio/php/schema.php");

	$scheme_info = get_schema_info($arr["schema"]);
	$disable = $scheme_info["overwrites"];
	if (!is_array($disable)) {
		$disable = array();
	}

	$scheme_choices = array();
	$scheme_choices["---"] = t("Default");
	$files = glob('view/theme/frio/schema/*.php');
	if ($files) {
		foreach ($files as $file) {
			$f = basename($file, ".php");
			if ($f != 'default') {
				$scheme_name = $f;
				$scheme_choices[$f] = $scheme_name;
			}
		}
	}

	$background_image_help = "<strong>" . t("Note"). ": </strong>".t("Check image permissions if all users are allowed to visit the image");

	$t = get_markup_template('theme_settings.tpl');
	$o .= replace_macros($t, array(
		'$submit'           => t('Submit'),
		'$baseurl'          => System::baseUrl(),
		'$title'            => t("Theme settings"),
		'$schema'           => array('frio_schema', t("Select scheme"), $arr["schema"], '', $scheme_choices),
		'$nav_bg'           => array_key_exists("nav_bg", $disable) ? "" : array('frio_nav_bg', t('Navigation bar background color'), $arr['nav_bg']),
		'$nav_icon_color'   => array_key_exists("nav_icon_color", $disable) ? "" : array('frio_nav_icon_color', t('Navigation bar icon color '), $arr['nav_icon_color']),
		'$link_color'       => array_key_exists("link_color", $disable) ? "" : array('frio_link_color', t('Link color'), $arr['link_color'], '', $link_colors),
		'$bgcolor'          => array_key_exists("bgcolor", $disable) ? "" : array('frio_background_color', t('Set the background color'), $arr['bgcolor']),
		'$contentbg_transp' => array_key_exists("contentbg_transp", $disable) ? "" : array('frio_contentbg_transp', t("Content background transparency"), ((isset($arr["contentbg_transp"]) && $arr["contentbg_transp"] != "") ? $arr["contentbg_transp"] : 100)),
		'$background_image' => array_key_exists("background_image", $disable ) ? "" : array('frio_background_image', t('Set the background image'), $arr['background_image'], $background_image_help),
		'$bg_image_options' => Image::get_options($arr),
	));

	return $o;
}
