<?php

require_once('library/HTML5/Parser.php');
require_once('include/crypto.php');
require_once('include/feed.php');

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
				if(attribute_contains($x->getAttribute('class'),'fn')) {
					$ret['fn'] = $x->textContent;
				}
				if((attribute_contains($x->getAttribute('class'),'photo'))
					|| (attribute_contains($x->getAttribute('class'),'avatar'))) {
					$size = intval($x->getAttribute('width'));
					// dfrn prefers 175, so if we find this, we set largest_size so it can't be topped.
					if(($size > $largest_photo) || ($size == 175) || (! $largest_photo)) {
						$ret['photo'] = $x->getAttribute('src');
						$largest_photo = (($size == 175) ? 9999 : $size);
					}
				}
				if(attribute_contains($x->getAttribute('class'),'key')) {
					$ret['key'] = $x->textContent;
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

if(! function_exists('scrape_meta')) {
function scrape_meta($url) {

	$a = get_app();

	$ret = array();

	logger('scrape_meta: url=' . $url);

	$s = fetch_url($url);

	if(! $s)
		return $ret;

	$headers = $a->get_curl_headers();
	logger('scrape_meta: headers=' . $headers, LOGGER_DEBUG);

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
		logger('scrape_meta: parse error: ' . $e);
	}

	if(! $dom)
		return $ret;

	$items = $dom->getElementsByTagName('meta');

	// get DFRN link elements

	foreach($items as $item) {
		$x = $item->getAttribute('name');
		if(substr($x,0,5) == "dfrn-")
			$ret[$x] = $item->getAttribute('content');
	}

	return $ret;
}}


if(! function_exists('scrape_vcard')) {
function scrape_vcard($url) {

	$a = get_app();

	$ret = array();

	logger('scrape_vcard: url=' . $url);

	$s = fetch_url($url);

	if(! $s)
		return $ret;

	$headers = $a->get_curl_headers();
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
		logger('scrape_vcard: parse error: ' . $e);
	}

	if(! $dom)
		return $ret;

	// Pull out hCard profile elements

	$largest_photo = 0;

	$items = $dom->getElementsByTagName('*');
	foreach($items as $item) {
		if(attribute_contains($item->getAttribute('class'), 'vcard')) {
			$level2 = $item->getElementsByTagName('*');
			foreach($level2 as $x) {
				if(attribute_contains($x->getAttribute('class'),'fn'))
					$ret['fn'] = $x->textContent;
				if((attribute_contains($x->getAttribute('class'),'photo'))
					|| (attribute_contains($x->getAttribute('class'),'avatar'))) {
					$size = intval($x->getAttribute('width'));
					if(($size > $largest_photo) || (! $largest_photo)) {
						$ret['photo'] = $x->getAttribute('src');
						$largest_photo = $size;
					}
				}
				if((attribute_contains($x->getAttribute('class'),'nickname'))
					|| (attribute_contains($x->getAttribute('class'),'uid'))) {
					$ret['nick'] = $x->textContent;
				}
			}
		}
	}

	return $ret;
}}


if(! function_exists('scrape_feed')) {
function scrape_feed($url) {

	$a = get_app();

	$ret = array();
	$cookiejar = tempnam(get_temppath(), 'cookiejar-scrape-feed-');
	$s = fetch_url($url, false, $redirects, 0, Null, $cookiejar);
	unlink($cookiejar);

	$headers = $a->get_curl_headers();
	$code = $a->get_curl_code();

	logger('scrape_feed: returns: ' . $code . ' headers=' . $headers, LOGGER_DEBUG);

	if(! $s) {
		logger('scrape_feed: no data returned for ' . $url);
		return $ret;
	}


	$lines = explode("\n",$headers);
	if(count($lines)) {
		foreach($lines as $line) {
			if(stristr($line,'content-type:')) {
				if(stristr($line,'application/atom+xml') || stristr($s,'<feed')) {
					$ret['feed_atom'] = $url;
					return $ret;
				}
				if(stristr($line,'application/rss+xml') || stristr($s,'<rss')) {
					$ret['feed_rss'] = $url;
					return $ret;
				}
			}
		}
		// perhaps an RSS version 1 feed with a generic or incorrect content-type?
		if(stristr($s,'</item>')) {
			$ret['feed_rss'] = $url;
			return $ret;
		}
	}

	$basename = implode('/', array_slice(explode('/',$url),0,3)) . '/';

	$doc = new DOMDocument();
	@$doc->loadHTML($s);
	$xpath = new DomXPath($doc);

	$base = $xpath->query("//base");
	foreach ($base as $node) {
		$attr = array();

		if ($node->attributes->length)
			foreach ($node->attributes as $attribute)
				$attr[$attribute->name] = $attribute->value;

		if ($attr["href"] != "")
			$basename = $attr["href"] ;
	}

	$list = $xpath->query("//link");
	foreach ($list as $node) {
		$attr = array();

		if ($node->attributes->length)
			foreach ($node->attributes as $attribute)
				$attr[$attribute->name] = $attribute->value;

		if (($attr["rel"] == "alternate") AND ($attr["type"] == "application/atom+xml"))
			$ret["feed_atom"] = $attr["href"];

		if (($attr["rel"] == "alternate") AND ($attr["type"] == "application/rss+xml"))
			$ret["feed_rss"] = $attr["href"];
	}

	// Drupal and perhaps others only provide relative URLs. Turn them into absolute.

	if(x($ret,'feed_atom') && (! strstr($ret['feed_atom'],'://')))
		$ret['feed_atom'] = $basename . $ret['feed_atom'];
	if(x($ret,'feed_rss') && (! strstr($ret['feed_rss'],'://')))
		$ret['feed_rss'] = $basename . $ret['feed_rss'];

	return $ret;
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


define ( 'PROBE_NORMAL',   0);
define ( 'PROBE_DIASPORA', 1);

function probe_url($url, $mode = PROBE_NORMAL, $level = 1) {
	require_once('include/email.php');

	$result = array();

	if (!$url)
		return $result;

	$result = Cache::get("probe_url:".$mode.":".$url);
	if (!is_null($result)) {
		$result = unserialize($result);
		return $result;
	}

	$original_url = $url;
	$network = null;
	$diaspora = false;
	$diaspora_base = '';
	$diaspora_guid = '';
	$diaspora_key = '';
	$has_lrdd = false;
	$email_conversant = false;
	$connectornetworks = false;
	$appnet = false;

	if (strpos($url,'twitter.com')) {
		$connectornetworks = true;
		$network = NETWORK_TWITTER;
	}

	$lastfm  = ((strpos($url,'last.fm/user') !== false) ? true : false);

	$at_addr = ((strpos($url,'@') !== false) ? true : false);

	if((!$appnet) && (!$lastfm) && !$connectornetworks) {

		if(strpos($url,'mailto:') !== false && $at_addr) {
			$url = str_replace('mailto:','',$url);
			$links = array();
		}
		else
			$links = lrdd($url);

		if ((count($links) == 0) AND strstr($url, "/index.php")) {
			$url = str_replace("/index.php", "", $url);
			$links = lrdd($url);
		}

		if (count($links)) {
			$has_lrdd = true;

			logger('probe_url: found lrdd links: ' . print_r($links,true), LOGGER_DATA);
			foreach($links as $link) {
				if($link['@attributes']['rel'] === NAMESPACE_ZOT)
					$zot = unamp($link['@attributes']['href']);
				if($link['@attributes']['rel'] === NAMESPACE_DFRN)
					$dfrn = unamp($link['@attributes']['href']);
				if($link['@attributes']['rel'] === 'salmon')
					$notify = unamp($link['@attributes']['href']);
				if($link['@attributes']['rel'] === NAMESPACE_FEED)
					$poll = unamp($link['@attributes']['href']);
				if($link['@attributes']['rel'] === 'http://microformats.org/profile/hcard')
					$hcard = unamp($link['@attributes']['href']);
				if($link['@attributes']['rel'] === 'http://webfinger.net/rel/profile-page')
					$profile = unamp($link['@attributes']['href']);
				if($link['@attributes']['rel'] === 'http://portablecontacts.net/spec/1.0')
					$poco = unamp($link['@attributes']['href']);
				if($link['@attributes']['rel'] === 'http://joindiaspora.com/seed_location') {
					$diaspora_base = unamp($link['@attributes']['href']);
					$diaspora = true;
				}
				if($link['@attributes']['rel'] === 'http://joindiaspora.com/guid') {
					$diaspora_guid = unamp($link['@attributes']['href']);
					$diaspora = true;
				}
				if($link['@attributes']['rel'] === 'diaspora-public-key') {
					$diaspora_key = base64_decode(unamp($link['@attributes']['href']));
					if(strstr($diaspora_key,'RSA '))
						$pubkey = rsatopem($diaspora_key);
					else
						$pubkey = $diaspora_key;
					$diaspora = true;
				}
				if(($link['@attributes']['rel'] === 'http://ostatus.org/schema/1.0/subscribe') AND ($mode == PROBE_NORMAL)) {
					$diaspora = false;
				}
			}

			// Status.Net can have more than one profile URL. We need to match the profile URL
			// to a contact on incoming messages to prevent spam, and we won't know which one
			// to match. So in case of two, one of them is stored as an alias. Only store URL's
			// and not webfinger user@host aliases. If they've got more than two non-email style
			// aliases, let's hope we're lucky and get one that matches the feed author-uri because
			// otherwise we're screwed.

			$backup_alias = "";

			foreach($links as $link) {
				if($link['@attributes']['rel'] === 'alias') {
					if(strpos($link['@attributes']['href'],'@') === false) {
						if(isset($profile)) {
							$alias_url = $link['@attributes']['href'];

							if(($alias_url !== $profile) AND ($backup_alias == "") AND
								($alias_url !== str_replace("/index.php", "", $profile)))
								$backup_alias = $alias_url;

							if(($alias_url !== $profile) AND !strstr($alias_url, "index.php") AND
								($alias_url !== str_replace("/index.php", "", $profile)))
								$alias = $alias_url;
						}
						else
							$profile = unamp($link['@attributes']['href']);
					}
				}
			}

			if ($alias == "")
				$alias = $backup_alias;

			// If the profile is different from the url then the url is abviously an alias
			if (($alias == "") AND ($profile != "") AND !$at_addr AND (normalise_link($profile) != normalise_link($url)))
				$alias = $url;
		}
		elseif($mode == PROBE_NORMAL) {

			// Check email

			$orig_url = $url;
			if((strpos($orig_url,'@')) && validate_email($orig_url)) {
				$x = q("SELECT `prvkey` FROM `user` WHERE `uid` = %d LIMIT 1",
					intval(local_user())
				);
				$r = q("SELECT * FROM `mailacct` WHERE `uid` = %d AND `server` != '' LIMIT 1",
					intval(local_user())
				);
				if(count($x) && count($r)) {
					$mailbox = construct_mailbox_name($r[0]);
					$password = '';
					openssl_private_decrypt(hex2bin($r[0]['pass']),$password,$x[0]['prvkey']);
					$mbox = email_connect($mailbox,$r[0]['user'],$password);
					if(! $mbox)
						logger('probe_url: email_connect failed.');
					unset($password);
				}
				if($mbox) {
					$msgs = email_poll($mbox,$orig_url);
					logger('probe_url: searching ' . $orig_url . ', ' . count($msgs) . ' messages found.', LOGGER_DEBUG);
					if(count($msgs)) {
						$addr = $orig_url;
						$network = NETWORK_MAIL;
						$name = substr($url,0,strpos($url,'@'));
						$phost = substr($url,strpos($url,'@')+1);
						$profile = 'http://' . $phost;
						// fix nick character range
						$vcard = array('fn' => $name, 'nick' => $name, 'photo' => avatar_img($url));
						$notify = 'smtp ' . random_string();
						$poll = 'email ' . random_string();
						$priority = 0;
						$x = email_msg_meta($mbox,$msgs[0]);
						if(stristr($x[0]->from,$orig_url))
							$adr = imap_rfc822_parse_adrlist($x[0]->from,'');
						elseif(stristr($x[0]->to,$orig_url))
							$adr = imap_rfc822_parse_adrlist($x[0]->to,'');
						if(isset($adr)) {
							foreach($adr as $feadr) {
								if((strcasecmp($feadr->mailbox,$name) == 0)
									&&(strcasecmp($feadr->host,$phost) == 0)
									&& (strlen($feadr->personal))) {

									$personal = imap_mime_header_decode($feadr->personal);
									$vcard['fn'] = "";
									foreach($personal as $perspart)
										if ($perspart->charset != "default")
											$vcard['fn'] .= iconv($perspart->charset, 'UTF-8//IGNORE', $perspart->text);
										else
											$vcard['fn'] .= $perspart->text;

									$vcard['fn'] = notags($vcard['fn']);
								}
							}
						}
					}
					imap_close($mbox);
				}
			}
		}
	}

	if($mode == PROBE_NORMAL) {

		if(strlen($zot)) {
			$s = fetch_url($zot);
			if($s) {
				$j = json_decode($s);
				if($j) {
					$network = NETWORK_ZOT;
					$vcard   = array(
						'fn'    => $j->fullname,
						'nick'  => $j->nickname,
						'photo' => $j->photo
					);
					$profile  = $j->url;
					$notify   = $j->post;
					$pubkey   = $j->pubkey;
					$poll     = 'N/A';
				}
			}
		}


		if(strlen($dfrn)) {
			$ret = scrape_dfrn(($hcard) ? $hcard : $dfrn, true);
			if(is_array($ret) && x($ret,'dfrn-request')) {
				$network = NETWORK_DFRN;
				$request = $ret['dfrn-request'];
				$confirm = $ret['dfrn-confirm'];
				$notify  = $ret['dfrn-notify'];
				$poll    = $ret['dfrn-poll'];

				$vcard = array();
				$vcard['fn'] = $ret['fn'];
				$vcard['nick'] = $ret['nick'];
				$vcard['photo'] = $ret['photo'];
			}
		}
	}

	// Scrape the public key from the hcard.
	// Diaspora will remove it from the webfinger somewhere in the future.
	if (($hcard != "") AND ($pubkey == "")) {
		$ret = scrape_dfrn(($hcard) ? $hcard : $dfrn, true);
		if (isset($ret["key"])) {
			$hcard_key = $ret["key"];
			if(strstr($hcard_key,'RSA '))
				$pubkey = rsatopem($hcard_key);
			else
				$pubkey = $hcard_key;
		}
	}
	if($diaspora && $diaspora_base && $diaspora_guid) {
		$diaspora_notify = $diaspora_base.'receive/users/'.$diaspora_guid;

		if($mode == PROBE_DIASPORA || !$notify || ($notify == $diaspora_notify)) {
			$notify = $diaspora_notify;
			$batch  = $diaspora_base . 'receive/public' ;
		}
		if(strpos($url,'@'))
			$addr = str_replace('acct:', '', $url);
	}

	if($network !== NETWORK_ZOT && $network !== NETWORK_DFRN && $network !== NETWORK_MAIL) {
		if($diaspora)
			$network = NETWORK_DIASPORA;
		elseif($has_lrdd AND ($notify))
			$network  = NETWORK_OSTATUS;

		if(strpos($url,'@'))
			$addr = str_replace('acct:', '', $url);

		$priority = 0;

		if($hcard && ! $vcard) {
			$vcard = scrape_vcard($hcard);

			// Google doesn't use absolute url in profile photos

			if((x($vcard,'photo')) && substr($vcard['photo'],0,1) == '/') {
				$h = @parse_url($hcard);
				if($h)
					$vcard['photo'] = $h['scheme'] . '://' . $h['host'] . $vcard['photo'];
			}

			logger('probe_url: scrape_vcard: ' . print_r($vcard,true), LOGGER_DATA);
		}

		if($diaspora && $addr) {
			// Diaspora returns the name as the nick. As the nick will never be updated,
			// let's use the Diaspora nickname (the first part of the handle) as the nick instead
			$addr_parts = explode('@', $addr);
			$vcard['nick'] = $addr_parts[0];
		}

		if($lastfm) {
			$profile = $url;
			$poll = str_replace(array('www.','last.fm/'),array('','ws.audioscrobbler.com/1.0/'),$url) . '/recenttracks.rss';
			$vcard['nick'] = basename($url);
			$vcard['fn'] = $vcard['nick'] . t(' on Last.fm');
			$network = NETWORK_FEED;
		}

		if(! x($vcard,'fn'))
			if(x($vcard,'nick'))
				$vcard['fn'] = $vcard['nick'];

		$check_feed = false;

		if(stristr($url,'tumblr.com') && (! stristr($url,'/rss'))) {
			$poll = $url . '/rss';
			$check_feed = true;
			// Will leave it to others to figure out how to grab the avatar, which is on the $url page in the open graph meta links
		}

		if($appnet || ! $poll)
			$check_feed = true;
		if((! isset($vcard)) || (! x($vcard,'fn')) || (! $profile))
			$check_feed = true;
		if(($at_addr) && (! count($links)))
			$check_feed = false;

		if ($connectornetworks)
			$check_feed = false;

		if($check_feed) {

			$feedret = scrape_feed(($poll) ? $poll : $url);

			logger('probe_url: scrape_feed ' . (($poll)? $poll : $url) . ' returns: ' . print_r($feedret,true), LOGGER_DATA);
			if(count($feedret) && ($feedret['feed_atom'] || $feedret['feed_rss'])) {
				$poll = ((x($feedret,'feed_atom')) ? unamp($feedret['feed_atom']) : unamp($feedret['feed_rss']));
				if(! x($vcard))
					$vcard = array();
			}

			if(x($feedret,'photo') && (! x($vcard,'photo')))
				$vcard['photo'] = $feedret['photo'];

			$cookiejar = tempnam(get_temppath(), 'cookiejar-scrape-feed-');
			$xml = fetch_url($poll, false, $redirects, 0, Null, $cookiejar);
			unlink($cookiejar);

			logger('probe_url: fetch feed: ' . $poll . ' returns: ' . $xml, LOGGER_DATA);

			if ($xml == "") {
				logger("scrape_feed: XML is empty for feed ".$poll);
				$network = NETWORK_PHANTOM;
			} else {
				$data = feed_import($xml,$dummy1,$dummy2, $dummy3, true);

				if (!is_array($data)) {
					logger("scrape_feed: This doesn't seem to be a feed: ".$poll);
					$network = NETWORK_PHANTOM;
				} else {
					if (($vcard["photo"] == "") AND ($data["header"]["author-avatar"] != ""))
						$vcard["photo"] = $data["header"]["author-avatar"];

					if (($vcard["fn"] == "") AND ($data["header"]["author-name"] != ""))
						$vcard["fn"] = $data["header"]["author-name"];

					if (($vcard["nick"] == "") AND ($data["header"]["author-nick"] != ""))
						$vcard["nick"] = $data["header"]["author-nick"];

					if ($network == NETWORK_OSTATUS) {
						if ($data["header"]["author-id"] != "")
							$alias = $data["header"]["author-id"];

						if ($data["header"]["author-link"] != "")
							$profile = $data["header"]["author-link"];

					} elseif(!$profile AND ($data["header"]["author-link"] != "") AND !in_array($network, array("", NETWORK_FEED)))
						$profile = $data["header"]["author-link"];
				}
			}

			// Workaround for misconfigured Friendica servers
			if (($network == "") AND (strstr($url, "/profile/"))) {
				$noscrape = str_replace("/profile/", "/noscrape/", $url);
				$noscrapejson = fetch_url($noscrape);
				if ($noscrapejson) {

					$network = NETWORK_DFRN;

					$poco = str_replace("/profile/", "/poco/", $url);

					$noscrapedata = json_decode($noscrapejson, true);

					if (isset($noscrapedata["addr"]))
						$addr = $noscrapedata["addr"];

					if (isset($noscrapedata["fn"]))
						$vcard["fn"] = $noscrapedata["fn"];

					if (isset($noscrapedata["key"]))
						$pubkey = $noscrapedata["key"];

					if (isset($noscrapedata["photo"]))
						$vcard["photo"] = $noscrapedata["photo"];

					if (isset($noscrapedata["dfrn-request"]))
						$request = $noscrapedata["dfrn-request"];

					if (isset($noscrapedata["dfrn-confirm"]))
						$confirm = $noscrapedata["dfrn-confirm"];

					if (isset($noscrapedata["dfrn-notify"]))
						$notify = $noscrapedata["dfrn-notify"];

					if (isset($noscrapedata["dfrn-poll"]))
						$poll = $noscrapedata["dfrn-poll"];

				}
			}

			if(! $network)
				$network = NETWORK_FEED;

			if(! x($vcard,'nick')) {
				$vcard['nick'] = strtolower(notags(unxmlify($vcard['fn'])));
				if(strpos($vcard['nick'],' '))
					$vcard['nick'] = trim(substr($vcard['nick'],0,strpos($vcard['nick'],' ')));
			}
			if(! $priority)
				$priority = 2;
		}
	}

	if(! x($vcard,'photo')) {
		$a = get_app();
		$vcard['photo'] = App::get_baseurl() . '/images/person-175.jpg' ;
	}

	if(! $profile)
		$profile = $url;

	// No human could be associated with this link, use the URL as the contact name

	if(($network === NETWORK_FEED) && ($poll) && (! x($vcard,'fn')))
		$vcard['fn'] = $url;

	if (($notify != "") AND ($poll != "")) {
		$baseurl = matching_url(normalise_link($notify), normalise_link($poll));

		$baseurl2 = matching_url($baseurl, normalise_link($profile));
		if ($baseurl2 != "")
			$baseurl = $baseurl2;
	}

	if (($baseurl == "") AND ($notify != ""))
		$baseurl = matching_url(normalise_link($profile), normalise_link($notify));

	if (($baseurl == "") AND ($poll != ""))
		$baseurl = matching_url(normalise_link($profile), normalise_link($poll));

	if (substr($baseurl, -10) == "/index.php")
		$baseurl = str_replace("/index.php", "", $baseurl);

	if ($network == "")
		$network = NETWORK_PHANTOM;

	$baseurl = rtrim($baseurl, "/");

	if(strpos($url,'@') AND ($addr == "") AND ($network == NETWORK_DFRN))
		$addr = str_replace('acct:', '', $url);

	$vcard['fn'] = notags($vcard['fn']);
	$vcard['nick'] = str_replace(' ','',notags($vcard['nick']));

	$result['name'] = $vcard['fn'];
	$result['nick'] = $vcard['nick'];
	$result['url'] = $profile;
	$result['addr'] = $addr;
	$result['batch'] = $batch;
	$result['notify'] = $notify;
	$result['poll'] = $poll;
	$result['request'] = $request;
	$result['confirm'] = $confirm;
	$result['poco'] = $poco;
	$result['photo'] = $vcard['photo'];
	$result['priority'] = $priority;
	$result['network'] = $network;
	$result['alias'] = $alias;
	$result['pubkey'] = $pubkey;
	$result['baseurl'] = $baseurl;

	logger('probe_url: ' . print_r($result,true), LOGGER_DEBUG);

	if ($level == 1) {
		// Trying if it maybe a diaspora account
		if (($result['network'] == NETWORK_FEED) OR ($result['addr'] == "")) {
			require_once('include/bbcode.php');
			$address = GetProfileUsername($url, "", true);
			$result2 = probe_url($address, $mode, ++$level);
			if ($result2['network'] != "")
				$result = $result2;
		}

		// Maybe it's some non standard GNU Social installation (Single user, subfolder or no uri rewrite)
		if (($result['network'] == NETWORK_FEED) AND ($result['baseurl'] != "") AND ($result['nick'] != "")) {
			$addr = $result['nick'].'@'.str_replace("http://", "", $result['baseurl']);
			$result2 = probe_url($addr, $mode, ++$level);
			if (($result2['network'] != "") AND ($result2['network'] != NETWORK_FEED))
				$result = $result2;
		}

		// Quickfix for Hubzilla systems with enabled OStatus plugin
		if (($result['network'] == NETWORK_DIASPORA) AND ($result["batch"] == "")) {
			$result2 = probe_url($url, PROBE_DIASPORA, ++$level);
			if ($result2['network'] == NETWORK_DIASPORA) {
				$addr = $result["addr"];
				$result = $result2;

				if (($result["addr"] == "") AND ($addr != ""))
					$result["addr"] = $addr;
			}
		}
	}

	// Only store into the cache if the value seems to be valid
	if ($result['network'] != NETWORK_PHANTOM) {
		Cache::set("probe_url:".$mode.":".$original_url,serialize($result), CACHE_DAY);

		/// @todo temporary fix - we need a real contact update function that updates only changing fields
		/// The biggest problem is the avatar picture that could have a reduced image size.
		/// It should only be updated if the existing picture isn't existing anymore.
		if (($result['network'] != NETWORK_FEED) AND ($mode == PROBE_NORMAL) AND
			$result["name"] AND $result["nick"] AND $result["url"] AND $result["addr"] AND $result["poll"])
			q("UPDATE `contact` SET `name` = '%s', `nick` = '%s', `url` = '%s', `addr` = '%s',
					`notify` = '%s', `poll` = '%s', `alias` = '%s', `success_update` = '%s'
				WHERE `nurl` = '%s' AND NOT `self` AND `uid` = 0",
				dbesc($result["name"]),
				dbesc($result["nick"]),
				dbesc($result["url"]),
				dbesc($result["addr"]),
				dbesc($result["notify"]),
				dbesc($result["poll"]),
				dbesc($result["alias"]),
				dbesc(datetime_convert()),
				dbesc(normalise_link($result['url']))
		);
	}

	return $result;
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
