<?php
/*
 * Name: Dark
 * Licence: AGPL
 * Author: Hypolite Petovan <hypolite@friendica.mrpetovan.com>
 * Overwrites: nav_bg, nav_icon_color, link_color, background_color, contentbg_transp
 * Accented: yes
 */

require_once 'view/theme/frio/php/PHPColors/Color.php';

$accentColor = new Color($scheme_accent);

$menu_background_hover_color = '#' . $accentColor->darken(20);
switch ($scheme_accent) {
	default:
		$link_color = '#' . $accentColor->lighten(25);
}
$nav_icon_color = $scheme_accent;
$nav_icon_hover_color = '#' . $accentColor->darken(20);
switch ($scheme_accent) {
	case FRIO_SCHEME_ACCENT_GREEN:
	case FRIO_SCHEME_ACCENT_RED:
		$nav_bg = '#' . $accentColor->darken(25);
		$background_color = '#' . $accentColor->darken(27);
		break;
	default:
		$nav_bg = '#' . $accentColor->darken(30);
		$background_color = '#' . $accentColor->darken(33);
}

$contentbg_transp = 4;
$font_color = '#e4e4e4';
$font_color_darker = '#dcdcdc';
