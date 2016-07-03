<?php

require_once('library/HTML5/Parser.php');
require_once('include/crypto.php');
require_once('include/feed.php');
require_once('include/Probe.php');

if(! function_exists('scrape_dfrn')) {
function scrape_dfrn($url, $dont_probe = false) {

	$a = get_app();

	$ret = array();

	logger('scrape_dfrn: url=' . $url);

	// Try to fetch the data from noscrape. This is faster than parsing the HTML
	$noscrape = str_replace("/hcard/", "/noscrape/", $url);
	$noscrapejson = fetch_url($noscrape);
	$noscrapedata = array();
	if ($noscrapejson) {
		$noscrapedata = json_decode($noscrapejson, true);

		if (is_array($noscrapedata)) {
			if ($noscrapedata["nick"] != "")
				return($noscrapedata);
			else
				unset($noscrapedata["nick"]);
		} else
			$noscrapedata = array();
	}

	$s = fetch_url($url);

	if (!$s)
		return $ret;

	if (!$dont_probe) {
		$probe = probe_url($url);

		if (isset($probe["addr"]))
			$ret["addr"] = $probe["addr"];
	}

	$headers = $a->get_curl_headers();
	logger('scrape_dfrn: headers=' . $headers, LOGGER_DEBUG);


	$lines = explode("\n",$headers);
	if(count($lines)) {
		foreach($lines as $line) {
			// don't try and run feeds through the html5 parser
			if(stristr($line,'content-type:') && ((stristr($line,'application/atom+xml')) || (stristr($line,'application/rss+xml'))))
				return ret;
		}
	}

	try {
		$dom = HTML5_Parser::parse($s);
	} catch (DOMException $e) {
		logger('scrape_dfrn: parse error: ' . $e);
	}

	if(! $dom)
		return $ret;

	$items = $dom->getElementsByTagName('link');

	// get DFRN link elements

	foreach($items as $item) {
		$x = $item->getAttribute('rel');
		if(($x === 'alternate') && ($item->getAttribute('type') === 'application/atom+xml'))
			$ret['feed_atom'] = $item->getAttribute('href');
		if(substr($x,0,5) == "dfrn-") {
			$ret[$x] = $item->getAttribute('href');
		}
		if($x === 'lrdd') {
			$decoded = urldecode($item->getAttribute('href'));
			if(preg_match('/acct:([^@]*)@/',$decoded,$matches))
				$ret['nick'] = $matches[1];
		}
	}

	// Pull out hCard profile elements

	$largest_photo = 0;

	$items = $dom->getElementsByTagName('*');
	foreach($items as $item) {
		if(attribute_contains($item->getAttribute('class'), 'vcard')) {
			$level2 = $item->getElementsByTagName('*');
			foreach($level2 as $x) {
				if(attribute_contains($x->getAttribute('class'),'uid'))
					$ret['guid'] = $x->textContent;
				if(attribute_contains($x->getAttribute('class'),'nickname'))
					$ret['nickname'] = $x->textContent;
				if(attribute_contains($x->getAttribute('class'),'fn'))
					$ret['fn'] = $x->textContent;
				if(attribute_contains($x->getAttribute('class'),'searchable'))
					$ret['searchable'] = $x->textContent;
				if(attribute_contains($x->getAttribute('class'),'key'))
					$ret['key'] = $x->textContent;
				if(attribute_contains($x->getAttribute('class'),'url'))
					$ret['url'] = $x->textContent;
				if((attribute_contains($x->getAttribute('class'),'photo'))
					|| (attribute_contains($x->getAttribute('class'),'avatar'))) {
					$size = intval($x->getAttribute('width'));
					// dfrn prefers 175, so if we find this, we set largest_size so it can't be topped.
					if(($size > $largest_photo) || ($size == 175) || (! $largest_photo)) {
						$ret['photo'] = $x->getAttribute('src');
						$largest_photo = (($size == 175) ? 9999 : $size);
					}
				}
			}
		}
	}
	return array_merge($ret, $noscrapedata);
}}






if(! function_exists('validate_dfrn')) {
function validate_dfrn($a) {
	$errors = 0;
	if(! x($a,'key'))
		$errors ++;
	if(! x($a,'dfrn-request'))
		$errors ++;
	if(! x($a,'dfrn-confirm'))
		$errors ++;
	if(! x($a,'dfrn-notify'))
		$errors ++;
	if(! x($a,'dfrn-poll'))
		$errors ++;
	return $errors;
}}

/**
 *
 * Probe a network address to discover what kind of protocols we need to communicate with it.
 *
 * Warning: this function is a bit touchy and there are some subtle dependencies within the logic flow.
 * Edit with care.
 *
 */

/**
 *
 * PROBE_DIASPORA has a bias towards returning Diaspora information
 * while PROBE_NORMAL has a bias towards dfrn/zot - in the case where
 * an address (such as a Friendica address) supports more than one type
 * of network.
 *
 */


define('PROBE_NORMAL',   0);
define('PROBE_DIASPORA', 1);

function probe_url($url, $mode = PROBE_NORMAL, $level = 1) {

	if ($mode == PROBE_DIASPORA)
		$network = NETWORK_DIASPORA;
	else
		$network = "";

	$data = Probe::uri($url, $network);

	return $data;
}

/**
 * @brief Find the matching part between two url
 *
 * @param string $url1
 * @param string $url2
 * @return string The matching part
 */
function matching_url($url1, $url2) {

	if (($url1 == "") OR ($url2 == ""))
		return "";

	$url1 = normalise_link($url1);
	$url2 = normalise_link($url2);

	$parts1 = parse_url($url1);
	$parts2 = parse_url($url2);

	if (!isset($parts1["host"]) OR !isset($parts2["host"]))
		return "";

	if ($parts1["scheme"] != $parts2["scheme"])
		return "";

	if ($parts1["host"] != $parts2["host"])
		return "";

	if ($parts1["port"] != $parts2["port"])
		return "";

	$match = $parts1["scheme"]."://".$parts1["host"];

	if ($parts1["port"])
		$match .= ":".$parts1["port"];

	$pathparts1 = explode("/", $parts1["path"]);
	$pathparts2 = explode("/", $parts2["path"]);

	$i = 0;
	$path = "";
	do {
		$path1 = $pathparts1[$i];
		$path2 = $pathparts2[$i];

		if ($path1 == $path2)
			$path .= $path1."/";

	} while (($path1 == $path2) AND ($i++ <= count($pathparts1)));

	$match .= $path;

	return normalise_link($match);
}
