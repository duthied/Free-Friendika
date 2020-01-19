<?php
/**
 * Theme settings
 */

use Friendica\App;
use Friendica\Core\Renderer;
use Friendica\DI;

function theme_content(App $a)
{
	if (!local_user()) {
		return;
	}

	$colorset = DI::pConfig()->get(local_user(), 'duepuntozero', 'colorset');
	$user = true;

	return clean_form($a, $colorset, $user);
}

function theme_post(App $a)
{
	if (! local_user()) {
		return;
	}

	if (isset($_POST['duepuntozero-settings-submit'])) {
		DI::pConfig()->set(local_user(), 'duepuntozero', 'colorset', $_POST['duepuntozero_colorset']);
	}
}

function theme_admin(App $a)
{
	$colorset = DI::config()->get('duepuntozero', 'colorset');
	$user = false;

	return clean_form($a, $colorset, $user);
}

function theme_admin_post(App $a)
{
	if (isset($_POST['duepuntozero-settings-submit'])) {
		DI::config()->set('duepuntozero', 'colorset', $_POST['duepuntozero_colorset']);
	}
}

/// @TODO $a is no longer used
function clean_form(App $a, &$colorset, $user)
{
	$colorset = [
		'default'     => DI::l10n()->t('default'),
		'greenzero'   => DI::l10n()->t('greenzero'),
		'purplezero'  => DI::l10n()->t('purplezero'),
		'easterbunny' => DI::l10n()->t('easterbunny'),
		'darkzero'    => DI::l10n()->t('darkzero'),
		'comix'       => DI::l10n()->t('comix'),
		'slackr'      => DI::l10n()->t('slackr'),
	];

	if ($user) {
		$color = DI::pConfig()->get(local_user(), 'duepuntozero', 'colorset');
	} else {
		$color = DI::config()->get('duepuntozero', 'colorset');
	}

	$t = Renderer::getMarkupTemplate("theme_settings.tpl");
	$o = Renderer::replaceMacros($t, [
		'$submit'   => DI::l10n()->t('Submit'),
		'$title'    => DI::l10n()->t("Theme settings"),
		'$colorset' => ['duepuntozero_colorset', DI::l10n()->t('Variations'), $color, '', $colorset],
	]);

	return $o;
}
