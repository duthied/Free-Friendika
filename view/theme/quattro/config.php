<?php
/**
 * Theme settings
 */

use Friendica\App;
use Friendica\Core\Renderer;
use Friendica\DI;

function theme_content(App $a) {
	if (!local_user()) {
		return;
	}

	$align = DI::pConfig()->get(local_user(), 'quattro', 'align' );
	$color = DI::pConfig()->get(local_user(), 'quattro', 'color' );
	$tfs = DI::pConfig()->get(local_user(),"quattro","tfs");
	$pfs = DI::pConfig()->get(local_user(),"quattro","pfs");

	return quattro_form($a,$align, $color, $tfs, $pfs);
}

function theme_post(App $a) {
	if (! local_user()) {
		return;
	}

	if (isset($_POST['quattro-settings-submit'])){
		DI::pConfig()->set(local_user(), 'quattro', 'align', $_POST['quattro_align']);
		DI::pConfig()->set(local_user(), 'quattro', 'color', $_POST['quattro_color']);
		DI::pConfig()->set(local_user(), 'quattro', 'tfs', $_POST['quattro_tfs']);
		DI::pConfig()->set(local_user(), 'quattro', 'pfs', $_POST['quattro_pfs']);
	}
}

function theme_admin(App $a) {
	$align = DI::config()->get('quattro', 'align' );
	$color = DI::config()->get('quattro', 'color' );
	$tfs = DI::config()->get("quattro","tfs");
	$pfs = DI::config()->get("quattro","pfs");

	return quattro_form($a,$align, $color, $tfs, $pfs);
}

function theme_admin_post(App $a) {
	if (isset($_POST['quattro-settings-submit'])){
		DI::config()->set('quattro', 'align', $_POST['quattro_align']);
		DI::config()->set('quattro', 'color', $_POST['quattro_color']);
		DI::config()->set('quattro', 'tfs', $_POST['quattro_tfs']);
		DI::config()->set('quattro', 'pfs', $_POST['quattro_pfs']);
	}
}

/// @TODO $a is no longer used here
function quattro_form(App $a, $align, $color, $tfs, $pfs) {
	$colors = [
		"dark"  => "Quattro",
		"lilac" => "Lilac",
		"green" => "Green",
	];

	if ($tfs === false) {
		$tfs = "20";
	}
	if ($pfs === false) {
		$pfs = "12";
	}

	$t = Renderer::getMarkupTemplate("theme_settings.tpl" );
	$o = Renderer::replaceMacros($t, [
		'$submit'  => DI::l10n()->t('Submit'),
		'$title'   => DI::l10n()->t("Theme settings"),
		'$align'   => ['quattro_align', DI::l10n()->t('Alignment'), $align, '', ['left' => DI::l10n()->t('Left'), 'center' => DI::l10n()->t('Center')]],
		'$color'   => ['quattro_color', DI::l10n()->t('Color scheme'), $color, '', $colors],
		'$pfs'     => ['quattro_pfs', DI::l10n()->t('Posts font size'), $pfs],
		'$tfs'     => ['quattro_tfs', DI::l10n()->t('Textareas font size'), $tfs],
	]);
	return $o;
}
