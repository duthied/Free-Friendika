<?php
/*
 * Name: Black
 * Licence: AGPL
 * Author: Hypolite Petovan <hypolite@friendica.mrpetovan.com>
 * Overwrites: nav_bg, nav_icon_color, link_color, background_color, background_image, contentbg_transp
 * Accented: yes
 */

require_once 'view/theme/frio/php/PHPColors/Color.php';

$accentColor = new Color($scheme_accent);

$menu_background_hover_color = '#' . $accentColor->darken(45);
$nav_bg = '#202020';
$link_color = '#' . $accentColor->lighten(10);
$nav_icon_color = '#d4d4d4';
$background_color = '#000000';
$contentbg_transp = '0';
$font_color = '#cccccc';
$font_color_darker = '#acacac';
$background_image = '';
