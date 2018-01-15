<?php
/**
 * @file view/theme/vier/style.php
 */
use Friendica\Core\Config;
use Friendica\Core\PConfig;
use Friendica\Model\Profile;

$uid = Profile::getThemeUid();

$style = PConfig::get($uid, 'vier', 'style');

if ($style == "") {
	$style = Config::get('vier', 'style');
}

if ($style == "") {
	$style = "plus";
}

if ($style == "flat") {
	$stylecssfile = 'view/theme/vier/flat.css';
} else if ($style == "netcolour") {
	$stylecssfile = 'view/theme/vier/netcolour.css';
} else if ($style == "breathe") {
	$stylecssfile = 'view/theme/vier/breathe.css';
} else if ($style == "plus") {
	$stylecssfile = 'view/theme/vier/plus.css';
} else if ($style == "dark") {
	$stylecssfile = 'view/theme/vier/dark.css';
} else if ($style == "plusminus") {
	$stylecssfile = 'view/theme/vier/plusminus.css';
}

if (file_exists($THEMEPATH."//style.css")) {
	$stylecss = file_get_contents($THEMEPATH."//style.css")."\n";
	$modified = filemtime($THEMEPATH."//style.css");
}

$stylemodified = filemtime($stylecssfile);
$stylecss .= file_get_contents($stylecssfile);

if ($stylemodified > $modified) {
	$modified = $stylemodified;
}

$modified = gmdate('r', $modified);

$etag = md5($stylecss);

// Only send the CSS file if it was changed
header('Cache-Control: public');
header('ETag: "'.$etag.'"');
header('Last-Modified: '.$modified);

if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) || isset($_SERVER['HTTP_IF_NONE_MATCH'])) {

	$cached_modified = gmdate('r', strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']));
	$cached_etag = str_replace(['"', "-gzip"], ['', ''],
				stripslashes($_SERVER['HTTP_IF_NONE_MATCH']));

	if (($cached_modified == $modified) && ($cached_etag == $etag)) {
		header('HTTP/1.1 304 Not Modified');
		exit();
	}
}
echo $stylecss;
