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

use Friendica\Core\Hook;
use Friendica\Core\Logger;
use Friendica\Core\Protocol;
use Friendica\Core\Renderer;
use Friendica\Core\Session;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Item;
use Friendica\Protocol\DFRN;
use Friendica\Protocol\Feed;
use Friendica\Protocol\OStatus;
use Friendica\Util\Network;
use Friendica\Util\ParseUrl;
use Friendica\Util\Strings;

require_once __DIR__ . '/../mod/share.php';

function add_page_info_data(array $data, $no_photos = false)
{
	Hook::callAll('page_info_data', $data);

	if (empty($data['type'])) {
		return '';
	}

	// It maybe is a rich content, but if it does have everything that a link has,
	// then treat it that way
	if (($data["type"] == "rich") && is_string($data["title"]) &&
		is_string($data["text"]) && !empty($data["images"])) {
		$data["type"] = "link";
	}

	$data["title"] = $data["title"] ?? '';

	if ((($data["type"] != "link") && ($data["type"] != "video") && ($data["type"] != "photo")) || ($data["title"] == $data["url"])) {
		return "";
	}

	if ($no_photos && ($data["type"] == "photo")) {
		return "";
	}

	// Escape some bad characters
	$data["url"] = str_replace(["[", "]"], ["&#91;", "&#93;"], htmlentities($data["url"], ENT_QUOTES, 'UTF-8', false));
	$data["title"] = str_replace(["[", "]"], ["&#91;", "&#93;"], htmlentities($data["title"], ENT_QUOTES, 'UTF-8', false));

	$text = "[attachment type='".$data["type"]."'";

	if (empty($data["text"])) {
		$data["text"] = $data["title"];
	}

	if (empty($data["text"])) {
		$data["text"] = $data["url"];
	}

	if (!empty($data["url"])) {
		$text .= " url='".$data["url"]."'";
	}

	if (!empty($data["title"])) {
		$text .= " title='".$data["title"]."'";
	}

	// Only embedd a picture link when it seems to be a valid picture ("width" is set)
	if (!empty($data["images"]) && !empty($data["images"][0]["width"])) {
		$preview = str_replace(["[", "]"], ["&#91;", "&#93;"], htmlentities($data["images"][0]["src"], ENT_QUOTES, 'UTF-8', false));
		// if the preview picture is larger than 500 pixels then show it in a larger mode
		// But only, if the picture isn't higher than large (To prevent huge posts)
		if (!DI::config()->get('system', 'always_show_preview') && ($data["images"][0]["width"] >= 500)
			&& ($data["images"][0]["width"] >= $data["images"][0]["height"])) {
			$text .= " image='".$preview."'";
		} else {
			$text .= " preview='".$preview."'";
		}
	}

	$text .= "]".$data["text"]."[/attachment]";

	$hashtags = "";
	if (isset($data["keywords"]) && count($data["keywords"])) {
		$hashtags = "\n";
		foreach ($data["keywords"] as $keyword) {
			/// @TODO make a positive list of allowed characters
			$hashtag = str_replace([' ', '+', '/', '.', '#', '@', "'", '"', '’', '`', '(', ')', '„', '“'], '', $keyword);
			$hashtags .= "#[url=" . DI::baseUrl() . "/search?tag=" . $hashtag . "]" . $hashtag . "[/url] ";
		}
	}

	return "\n".$text.$hashtags;
}

function query_page_info($url, $photo = "", $keywords = false, $keyword_denylist = "")
{
	$data = ParseUrl::getSiteinfoCached($url, true);

	if ($photo != "") {
		$data["images"][0]["src"] = $photo;
	}

	Logger::log('fetch page info for ' . $url . ' ' . print_r($data, true), Logger::DEBUG);

	if (!$keywords && isset($data["keywords"])) {
		unset($data["keywords"]);
	}

	if (($keyword_denylist != "") && isset($data["keywords"])) {
		$list = explode(", ", $keyword_denylist);

		foreach ($list as $keyword) {
			$keyword = trim($keyword);

			$index = array_search($keyword, $data["keywords"]);
			if ($index !== false) {
				unset($data["keywords"][$index]);
			}
		}
	}

	return $data;
}

function get_page_keywords($url, $photo = "", $keywords = false, $keyword_denylist = "")
{
	$data = query_page_info($url, $photo, $keywords, $keyword_denylist);
	if (empty($data["keywords"]) || !is_array($data["keywords"])) {
		return [];
	}

	$taglist = [];
	foreach ($data['keywords'] as $keyword) {
		$hashtag = str_replace([" ", "+", "/", ".", "#", "'"],
			["", "", "", "", "", ""], $keyword);

		$taglist[] = $hashtag;
	}

	return $taglist;
}

function add_page_info($url, $no_photos = false, $photo = "", $keywords = false, $keyword_denylist = "")
{
	$data = query_page_info($url, $photo, $keywords, $keyword_denylist);

	$text = '';

	if (is_array($data)) {
		$text = add_page_info_data($data, $no_photos);
	}

	return $text;
}

function add_page_info_to_body($body, $texturl = false, $no_photos = false)
{
	Logger::log('add_page_info_to_body: fetch page info for body ' . $body, Logger::DEBUG);

	$URLSearchString = "^\[\]";

	// Fix for Mastodon where the mentions are in a different format
	$body = preg_replace("/\[url\=([$URLSearchString]*)\]([#!@])(.*?)\[\/url\]/ism",
		'$2[url=$1]$3[/url]', $body);

	// Adding these spaces is a quick hack due to my problems with regular expressions :)
	preg_match("/[^!#@]\[url\]([$URLSearchString]*)\[\/url\]/ism", " " . $body, $matches);

	if (!$matches) {
		preg_match("/[^!#@]\[url\=([$URLSearchString]*)\](.*?)\[\/url\]/ism", " " . $body, $matches);
	}

	// Convert urls without bbcode elements
	if (!$matches && $texturl) {
		preg_match("/([^\]\='".'"'."]|^)(https?\:\/\/[a-zA-Z0-9\:\/\-\?\&\;\.\=\_\~\#\%\$\!\+\,]+)/ism", " ".$body, $matches);

		// Yeah, a hack. I really hate regular expressions :)
		if ($matches) {
			$matches[1] = $matches[2];
		}
	}

	if ($matches) {
		$footer = add_page_info($matches[1], $no_photos);
	}

	// Remove the link from the body if the link is attached at the end of the post
	if (isset($footer) && (trim($footer) != "") && (strpos($footer, $matches[1]))) {
		$removedlink = trim(str_replace($matches[1], "", $body));
		if (($removedlink == "") || strstr($body, $removedlink)) {
			$body = $removedlink;
		}

		$removedlink = preg_replace("/\[url\=" . preg_quote($matches[1], '/') . "\](.*?)\[\/url\]/ism", '', $body);
		if (($removedlink == "") || strstr($body, $removedlink)) {
			$body = $removedlink;
		}
	}

	// Add the page information to the bottom
	if (isset($footer) && (trim($footer) != "")) {
		$body .= $footer;
	}

	return $body;
}

/**
 * @deprecated since 2020.06
 * @see \Friendica\Protocol\Feed::consume
 */
function consume_feed($xml, array $importer, array $contact, &$hub)
{
	\Friendica\Protocol\Feed::consume($xml, $importer, $contact, $hub);
}
