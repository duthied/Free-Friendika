<?php
/**
 * @file view/theme/quattro/style.php
 */
use Friendica\Core\Config;
use Friendica\DI;

$uid = $_REQUEST['puid'] ?? 0;

$color = false;
$quattro_align = false;
$site_color = DI::config()->get("quattro", "color", "dark");
$site_quattro_align = DI::config()->get("quattro", "align", false);

if ($uid) {
	$color = DI::pConfig()->get($uid, "quattro", "color", false);
	$quattro_align = DI::pConfig()->get($uid, 'quattro', 'align', false);
}

if ($color === false) {
	$color = $site_color;
}

if ($quattro_align === false) {
	$quattro_align = $site_quattro_align;
}

$color = \Friendica\Util\Strings::sanitizeFilePathItem($color);

if (file_exists("$THEMEPATH/$color/style.css")) {
	echo file_get_contents("$THEMEPATH/$color/style.css");
}


if ($quattro_align == "center") {
	echo "
		html { width: 100%; margin:0px; padding:0px; }
		body {
			margin: 50px auto;
			width: 900px;
		}
	";
}


$textarea_font_size = false;
$post_font_size = false;

$site_textarea_font_size = DI::config()->get("quattro", "tfs", "20");
$site_post_font_size = DI::config()->get("quattro", "pfs", "12");

if ($uid) {
	$textarea_font_size = DI::pConfig()->get($uid, "quattro", "tfs", false);
	$post_font_size = DI::pConfig()->get($uid, "quattro", "pfs", false);
}

if ($textarea_font_size === false) {
	$textarea_font_size = $site_textarea_font_size;
}
if ($post_font_size === false) {
	$post_font_size = $site_post_font_size;
}

echo "
	textarea { font-size: ${textarea_font_size}px; }
	.wall-item-comment-wrapper .comment-edit-text-full { font-size: ${textarea_font_size}px; }
	#jot .profile-jot-text:focus { font-size: ${textarea_font_size}px; }
	.wall-item-container .wall-item-content  { font-size: ${post_font_size}px; }
";
