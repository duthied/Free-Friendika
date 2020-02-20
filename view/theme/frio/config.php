<?php
/**
 * @copyright Copyright (C) 2020, Friendica
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

use Friendica\App;
use Friendica\Core\Renderer;
use Friendica\DI;

require_once 'view/theme/frio/php/Image.php';

function theme_post(App $a)
{
	if (!local_user()) {
		return;
	}

	if (isset($_POST['frio-settings-submit'])) {
		DI::pConfig()->set(local_user(), 'frio', 'scheme',           $_POST['frio_scheme']           ?? '');
		DI::pConfig()->set(local_user(), 'frio', 'nav_bg',           $_POST['frio_nav_bg']           ?? '');
		DI::pConfig()->set(local_user(), 'frio', 'nav_icon_color',   $_POST['frio_nav_icon_color']   ?? '');
		DI::pConfig()->set(local_user(), 'frio', 'link_color',       $_POST['frio_link_color']       ?? '');
		DI::pConfig()->set(local_user(), 'frio', 'background_color', $_POST['frio_background_color'] ?? '');
		DI::pConfig()->set(local_user(), 'frio', 'contentbg_transp', $_POST['frio_contentbg_transp'] ?? '');
		DI::pConfig()->set(local_user(), 'frio', 'background_image', $_POST['frio_background_image'] ?? '');
		DI::pConfig()->set(local_user(), 'frio', 'bg_image_option',  $_POST['frio_bg_image_option']  ?? '');
		DI::pConfig()->set(local_user(), 'frio', 'css_modified',     time());
	}
}

function theme_admin_post(App $a)
{
	if (!is_site_admin()) {
		return;
	}

	if (isset($_POST['frio-settings-submit'])) {
		DI::config()->set('frio', 'scheme',           $_POST['frio_scheme']           ?? '');
		DI::config()->set('frio', 'nav_bg',           $_POST['frio_nav_bg']           ?? '');
		DI::config()->set('frio', 'nav_icon_color',   $_POST['frio_nav_icon_color']   ?? '');
		DI::config()->set('frio', 'link_color',       $_POST['frio_link_color']       ?? '');
		DI::config()->set('frio', 'background_color', $_POST['frio_background_color'] ?? '');
		DI::config()->set('frio', 'contentbg_transp', $_POST['frio_contentbg_transp'] ?? '');
		DI::config()->set('frio', 'background_image', $_POST['frio_background_image'] ?? '');
		DI::config()->set('frio', 'bg_image_option',  $_POST['frio_bg_image_option']  ?? '');
		DI::config()->set('frio', 'login_bg_image',   $_POST['frio_login_bg_image']   ?? '');
		DI::config()->set('frio', 'login_bg_color',   $_POST['frio_login_bg_color']   ?? '');
		DI::config()->set('frio', 'css_modified',     time());
	}
}

function theme_content(App $a)
{
	if (!local_user()) {
		return;
	}
	$arr = [];

	$node_scheme = DI::config()->get('frio', 'scheme', DI::config()->get('frio', 'scheme'));

	$arr['scheme']           = DI::pConfig()->get(local_user(), 'frio', 'scheme', DI::pConfig()->get(local_user(), 'frio', 'schema', $node_scheme));
	$arr['share_string']     = '';
	$arr['nav_bg']           = DI::pConfig()->get(local_user(), 'frio', 'nav_bg'          , DI::config()->get('frio', 'nav_bg'));
	$arr['nav_icon_color']   = DI::pConfig()->get(local_user(), 'frio', 'nav_icon_color'  , DI::config()->get('frio', 'nav_icon_color'));
	$arr['link_color']       = DI::pConfig()->get(local_user(), 'frio', 'link_color'      , DI::config()->get('frio', 'link_color'));
	$arr['background_color'] = DI::pConfig()->get(local_user(), 'frio', 'background_color', DI::config()->get('frio', 'background_color'));
	$arr['contentbg_transp'] = DI::pConfig()->get(local_user(), 'frio', 'contentbg_transp', DI::config()->get('frio', 'contentbg_transp'));
	$arr['background_image'] = DI::pConfig()->get(local_user(), 'frio', 'background_image', DI::config()->get('frio', 'background_image'));
	$arr['bg_image_option']  = DI::pConfig()->get(local_user(), 'frio', 'bg_image_option' , DI::config()->get('frio', 'bg_image_option'));

	return frio_form($arr);
}

function theme_admin(App $a)
{
	if (!local_user()) {
		return;
	}
	$arr = [];

	$arr['scheme']           = DI::config()->get('frio', 'scheme', DI::config()->get('frio', 'schema'));
	$arr['share_string']     = '';
	$arr['nav_bg']           = DI::config()->get('frio', 'nav_bg');
	$arr['nav_icon_color']   = DI::config()->get('frio', 'nav_icon_color');
	$arr['link_color']       = DI::config()->get('frio', 'link_color');
	$arr['background_color'] = DI::config()->get('frio', 'background_color');
	$arr['contentbg_transp'] = DI::config()->get('frio', 'contentbg_transp');
	$arr['background_image'] = DI::config()->get('frio', 'background_image');
	$arr['bg_image_option']  = DI::config()->get('frio', 'bg_image_option');
	$arr['login_bg_image']   = DI::config()->get('frio', 'login_bg_image');
	$arr['login_bg_color']   = DI::config()->get('frio', 'login_bg_color');

	return frio_form($arr);
}

function frio_form($arr)
{
	require_once 'view/theme/frio/php/scheme.php';

	$scheme_info = get_scheme_info($arr['scheme']);
	$disable = $scheme_info['overwrites'];
	if (!is_array($disable)) {
		$disable = [];
	}

	$scheme_choices = [];
	$scheme_choices['---'] = DI::l10n()->t('Custom');
	$files = glob('view/theme/frio/scheme/*.php');
	if ($files) {
		foreach ($files as $file) {
			$f = basename($file, '.php');
			if ($f != 'default') {
				$scheme_name = ucfirst($f);
				$scheme_choices[$f] = $scheme_name;
			}
		}
	}

	$background_image_help = '<strong>' . DI::l10n()->t('Note') . ': </strong>' . DI::l10n()->t('Check image permissions if all users are allowed to see the image');

	$t = Renderer::getMarkupTemplate('theme_settings.tpl');
	$ctx = [
		'$submit'           => DI::l10n()->t('Submit'),
		'$title'            => DI::l10n()->t('Theme settings'),
		'$scheme'           => ['frio_scheme', DI::l10n()->t('Select color scheme'), $arr['scheme'], '', $scheme_choices],
		'$share_string'     => ['frio_share_string', DI::l10n()->t('Copy or paste schemestring'), $arr['share_string'], DI::l10n()->t('You can copy this string to share your theme with others. Pasting here applies the schemestring'), false, false],
		'$nav_bg'           => array_key_exists('nav_bg', $disable) ? '' : ['frio_nav_bg', DI::l10n()->t('Navigation bar background color'), $arr['nav_bg'], '', false],
		'$nav_icon_color'   => array_key_exists('nav_icon_color', $disable) ? '' : ['frio_nav_icon_color', DI::l10n()->t('Navigation bar icon color '), $arr['nav_icon_color'], '', false],
		'$link_color'       => array_key_exists('link_color', $disable) ? '' : ['frio_link_color', DI::l10n()->t('Link color'), $arr['link_color'], '', false],
		'$background_color' => array_key_exists('background_color', $disable) ? '' : ['frio_background_color', DI::l10n()->t('Set the background color'), $arr['background_color'], '', false],
		'$contentbg_transp' => array_key_exists('contentbg_transp', $disable) ? '' : ['frio_contentbg_transp', DI::l10n()->t('Content background opacity'), ($arr['contentbg_transp'] ?? 0) ?: 100, ''],
		'$background_image' => array_key_exists('background_image', $disable) ? '' : ['frio_background_image', DI::l10n()->t('Set the background image'), $arr['background_image'], $background_image_help, false],
		'$bg_image_options_title' => DI::l10n()->t('Background image style'),
		'$bg_image_options' => Image::get_options($arr),
	];

	if (array_key_exists('login_bg_image', $arr) && !array_key_exists('login_bg_image', $disable)) {
		$ctx['$login_bg_image'] = ['frio_login_bg_image', DI::l10n()->t('Login page background image'), $arr['login_bg_image'], $background_image_help, false];
	}

	if (array_key_exists('login_bg_color', $arr) && !array_key_exists('login_bg_color', $disable)) {
		$ctx['$login_bg_color'] = ['frio_login_bg_color', DI::l10n()->t('Login page background color'), $arr['login_bg_color'], DI::l10n()->t('Leave background image and color empty for theme defaults'), false];
	}

	$o = Renderer::replaceMacros($t, $ctx);

	return $o;
}
