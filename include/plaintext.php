<?php
function get_attached_data($body) {
/*
 - text:
 - type: link, video, photo
 - title:
 - url:
 - image:
 - description:
 - (thumbnail)
*/

	// Simplify image codes
	$body = preg_replace("/\[img\=([0-9]*)x([0-9]*)\](.*?)\[\/img\]/ism", '[img]$3[/img]', $body);

	$post = array();

	if (preg_match_all("(\[class=(.*?)\](.*?)\[\/class\])ism",$body, $attached,  PREG_SET_ORDER)) {
		foreach ($attached AS $data) {
			if (!in_array($data[1], array("type-link", "type-video", "type-photo")))
				continue;

			$post["type"] = substr($data[1], 5);

			$post["text"] = trim(str_replace($data[0], "", $body));

			$attacheddata = $data[2];

			$URLSearchString = "^\[\]";

			if (preg_match("/\[img\]([$URLSearchString]*)\[\/img\]/ism", $attacheddata, $matches))
				$post["image"] = $matches[1];

			if (preg_match("/\[bookmark\=([$URLSearchString]*)\](.*?)\[\/bookmark\]/ism", $attacheddata, $matches)) {
				$post["url"] = $matches[1];
				$post["title"] = $matches[2];
			}

			// Search for description
			if (preg_match("/\[quote\](.*?)\[\/quote\]/ism", $attacheddata, $matches))
				$post["description"] = $matches[1];

		}
	}

	// if nothing is found, it maybe having an image.
	if (!isset($post["type"])) {
		require_once("mod/parse_url.php");
		require_once("include/Photo.php");

		$URLSearchString = "^\[\]";
		if (preg_match_all("(\[url=([$URLSearchString]*)\]\s*\[img\]([$URLSearchString]*)\[\/img\]\s*\[\/url\])ism", $body, $pictures,  PREG_SET_ORDER)) {
			if (count($pictures) == 1) {
				// Checking, if the link goes to a picture
				$data = parseurl_getsiteinfo_cached($pictures[0][1], true);
				if ($data["type"] == "photo") {
					$post["type"] = "photo";
					if (isset($data["images"][0])) {
						$post["image"] = $data["images"][0]["src"];
						$post["url"] = $data["url"];
					} else
						$post["image"] = $data["url"];

					$post["preview"] = $pictures[0][2];
					$post["text"] = str_replace($pictures[0][0], "", $body);
				} else {
					$imgdata = get_photo_info($pictures[0][1]);
					if (substr($imgdata["mime"], 0, 6) == "image/") {
						$post["type"] = "photo";
						$post["image"] = $pictures[0][1];
						$post["preview"] = $pictures[0][2];
						$post["text"] = str_replace($pictures[0][0], "", $body);
					}
				}
			} elseif (count($pictures) > 1) {
				$post["type"] = "link";
				$post["url"] = $b["plink"];
				$post["image"] = $pictures[0][2];
				$post["text"] = $body;
			}
		} elseif (preg_match_all("(\[img\]([$URLSearchString]*)\[\/img\])ism", $body, $pictures,  PREG_SET_ORDER)) {
			if (count($pictures) == 1) {
				$post["type"] = "photo";
				$post["image"] = $pictures[0][1];
				$post["text"] = str_replace($pictures[0][0], "", $body);
			} elseif (count($pictures) > 1) {
				$post["type"] = "link";
				$post["url"] = $b["plink"];
				$post["image"] = $pictures[0][1];
				$post["text"] = $body;
			}
		}
		if (!isset($post["type"])) {
			$post["type"] = "text";
			$post["text"] = trim($body);
		}
	} elseif (isset($post["url"]) AND ($post["type"] == "video")) {
		require_once("mod/parse_url.php");
		$data = parseurl_getsiteinfo_cached($post["url"], true);

		if (isset($data["images"][0]))
			$post["image"] = $data["images"][0]["src"];
	}

	return($post);
}

function shortenmsg($msg, $limit, $twitter = false) {
	// To-Do:
	// For Twitter URLs aren't shortened, but they have to be calculated as if.

	$lines = explode("\n", $msg);
	$msg = "";
	$recycle = html_entity_decode("&#x2672; ", ENT_QUOTES, 'UTF-8');
	foreach ($lines AS $row=>$line) {
		if (iconv_strlen(trim($msg."\n".$line), "UTF-8") <= $limit)
			$msg = trim($msg."\n".$line);
		// Is the new message empty by now or is it a reshared message?
		elseif (($msg == "") OR (($row == 1) AND (substr($msg, 0, 4) == $recycle)))
			$msg = iconv_substr(iconv_substr(trim($msg."\n".$line), 0, $limit, "UTF-8"), 0, -3, "UTF-8")."...";
		else
			break;
	}
	return($msg);
}

function plaintext($a, $b, $limit = 0, $includedlinks = false, $htmlmode = 2) {
	require_once("include/bbcode.php");
	require_once("include/html2plain.php");
	require_once("include/network.php");

	// At first look at data that is attached via "type-..." stuff
	// This will hopefully replaced with a dedicated bbcode later
	$post = get_attached_data($b["body"]);

	if (($b["title"] != "") AND ($post["text"] != ""))
		$post["text"] = trim($b["title"]."\n\n".$post["text"]);
	elseif ($b["title"] != "")
		$post["text"] = trim($b["title"]);

	$html = bbcode($post["text"], false, false, $htmlmode);
	$msg = html2plain($html, 0, true);
	$msg = trim(html_entity_decode($msg,ENT_QUOTES,'UTF-8'));

	$link = "";
	if ($includedlinks) {
		if ($post["type"] == "link")
			$link = $post["url"];
		elseif ($post["type"] == "video")
			$link = $post["url"];
		elseif ($post["type"] == "photo")
			$link = $post["image"];

		if (($msg == "") AND isset($post["title"]))
			$msg = trim($post["title"]);

		if (($msg == "") AND isset($post["description"]))
			$msg = trim($post["description"]);

		// If the link is already contained in the post, then it neeedn't to be added again
		// But: if the link is beyond the limit, then it has to be added.
		if (($link != "") AND strstr($msg, $link)) {
			$pos = strpos($msg, $link);
			if (($limit == 0) OR ($pos < $limit))
				$link = "";
		}
	}

	if ($limit > 0) {
		// Reduce multiple spaces
		// When posted to a network with limited space, we try to gain space where possible
		while (strpos($msg, "  ") !== false)
			$msg = str_replace("  ", " ", $msg);

		// Twitter is using its own limiter, so we always assume that shortened links will have this length
		if (iconv_strlen($link, "UTF-8") > 0)
			$limit = $limit - 23;

		if (iconv_strlen($msg, "UTF-8") > $limit) {

			if (!isset($post["url"])) {
				$limit = $limit - 23;
				$post["url"] = $b["plink"];
			} elseif (strpos($b["body"], "[share") !== false)
				$post["url"] = $b["plink"];
			elseif (get_pconfig($b["uid"], "system", "no_intelligent_shortening"))
				$post["url"] = $b["plink"];

			$msg = shortenmsg($msg, $limit);
		}
	}

	$post["text"] = trim($msg);

	return($post);
}
?>
