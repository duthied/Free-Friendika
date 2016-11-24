<?php
/** 
 * @file mod/parse_url.php
 * 
 * @todo https://developers.google.com/+/plugins/snippet/
 * 
 * @verbatim
 * <meta itemprop="name" content="Toller Titel">
 * <meta itemprop="description" content="Eine tolle Beschreibung">
 * <meta itemprop="image" content="http://maple.libertreeproject.org/images/tree-icon.png">
 * 
 * <body itemscope itemtype="http://schema.org/Product">
 *   <h1 itemprop="name">Shiny Trinket</h1>
 *   <img itemprop="image" src="{image-url}" />
 *   <p itemprop="description">Shiny trinkets are shiny.</p>
 * </body>
 * @endverbatim
*/

use \Friendica\ParseUrl;

require_once("include/items.php");

function parse_url_content(&$a) {

	$text = null;
	$str_tags = "";

	$textmode = false;

	if (local_user() && (!feature_enabled(local_user(), "richtext"))) {
		$textmode = true;
	}

	$br = (($textmode) ? "\n" : "<br />");

	if (x($_GET,"binurl")) {
		$url = trim(hex2bin($_GET["binurl"]));
	} else {
		$url = trim($_GET["url"]);
	}

	if ($_GET["title"]) {
		$title = strip_tags(trim($_GET["title"]));
	}

	if ($_GET["description"]) {
		$text = strip_tags(trim($_GET["description"]));
	}

	if ($_GET["tags"]) {
		$arr_tags = ParseUrl::convertTagsToArray($_GET["tags"]);
		if (count($arr_tags)) {
			$str_tags = $br . implode(" ", $arr_tags) . $br;
		}
	}

	// Add url scheme if it is missing
	$arrurl = parse_url($url);
	if (!x($arrurl, "scheme")) {
		if (x($arrurl, "host")) {
			$url = "http:".$url;
		} else {
			$url = "http://".$url;
		}
	}

	logger("prse_url: " . $url);

	// If the URL is a image, video or audio file format the URL with the corresponding
	// BBCode media tag
	$redirects = 0;
	// Fetch the header of the URL
	$result = z_fetch_url($url, false, $redirects, array("novalidate" => true, "nobody" => true));
	if($result["success"]) {
		// Convert the header fields into an array
		$hdrs = array();
		$h = explode("\n", $result["header"]);
		foreach ($h as $l) {
			list($k,$v) = array_map("trim", explode(":", trim($l), 2));
			$hdrs[$k] = $v;
		}
		if (array_key_exists("Content-Type", $hdrs)) {
			$type = $hdrs["Content-Type"];
		}
		if ($type) {
			if(stripos($type, "image/") !== false) {
				echo $br . "[img]" . $url . "[/img]" . $br;
				killme();
			}
			if (stripos($type, "video/") !== false) {
				echo $br . "[video]" . $url . "[/video]" . $br;
				killme();
			}
			if (stripos($type, "audio/") !== false) {
				echo $br . "[audio]" . $url . "[/audio]" . $br;
				killme();
			}
		}
	}

	if ($textmode) {
		$template = "[bookmark=%s]%s[/bookmark]%s";
	} else {
		$template = "<a class=\"bookmark\" href=\"%s\" >%s</a>%s";
	}

	$arr = array("url" => $url, "text" => "");

	call_hooks("parse_link", $arr);

	if (strlen($arr["text"])) {
		echo $arr["text"];
		killme();
	}


	if ($url && $title && $text) {

		$title = str_replace(array("\r","\n"),array("",""),$title);

		if ($textmode) {
			$text = "[quote]" . trim($text) . "[/quote]" . $br;
		} else {
			$text = "<blockquote>" . htmlspecialchars(trim($text)) . "</blockquote><br />";
			$title = htmlspecialchars($title);
		}

		$result = sprintf($template, $url, ($title) ? $title : $url, $text) . $str_tags;

		logger("parse_url (unparsed): returns: " . $result);

		echo $result;
		killme();
	}

	// Fetch the information from the webpage
	$siteinfo = ParseUrl::getSiteinfo($url);

	unset($siteinfo["keywords"]);

	// Format it as BBCode attachment
	$info = add_page_info_data($siteinfo);

	if (!$textmode) {
		// Replace ' with ’ - not perfect - but the richtext editor has problems otherwise
		$info = str_replace(array("&#039;"), array("&#8217;"), $info);
	}

	echo $info;

	killme();
}
