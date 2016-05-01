<?php

/**
 * @file include/network.php
 */

require_once("include/xml.php");


/**
 * @brief Curl wrapper
 * 
 * If binary flag is true, return binary results.
 * Set the cookiejar argument to a string (e.g. "/tmp/friendica-cookies.txt")
 * to preserve cookies from one request to the next.
 * 
 * @param string $url URL to fetch
 * @param boolean $binary default false
 *    TRUE if asked to return binary results (file download)
 * @param integer $redirects The recursion counter for internal use - default 0
 * @param integer $timeout Timeout in seconds, default system config value or 60 seconds
 * @param string $accept_content supply Accept: header with 'accept_content' as the value
 * @param string $cookiejar Path to cookie jar file
 * 
 * @return string The fetched content
 */
function fetch_url($url,$binary = false, &$redirects = 0, $timeout = 0, $accept_content=Null, $cookiejar = 0) {

	$ret = z_fetch_url(
		$url,
		$binary,
		$redirects,
		array('timeout'=>$timeout,
		'accept_content'=>$accept_content,
		'cookiejar'=>$cookiejar
		));

	return($ret['body']);
}

/**
 * @brief fetches an URL.
 *
 * @param string $url URL to fetch
 * @param boolean $binary default false
 *    TRUE if asked to return binary results (file download)
 * @param int $redirects The recursion counter for internal use - default 0
 * @param array $opts (optional parameters) assoziative array with:
 *    'accept_content' => supply Accept: header with 'accept_content' as the value
 *    'timeout' => int Timeout in seconds, default system config value or 60 seconds
 *    'http_auth' => username:password
 *    'novalidate' => do not validate SSL certs, default is to validate using our CA list
 *    'nobody' => only return the header
 *    'cookiejar' => path to cookie jar file
 *
 * @return array an assoziative array with:
 *    int 'return_code' => HTTP return code or 0 if timeout or failure
 *    boolean 'success' => boolean true (if HTTP 2xx result) or false
 *    string 'redirect_url' => in case of redirect, content was finally retrieved from this URL
 *    string 'header' => HTTP headers
 *    string 'body' => fetched content
 */
function z_fetch_url($url,$binary = false, &$redirects = 0, $opts=array()) {

	$ret = array('return_code' => 0, 'success' => false, 'header' => "", 'body' => "");


	$stamp1 = microtime(true);

	$a = get_app();

	$ch = @curl_init($url);
	if(($redirects > 8) || (! $ch))
		return false;

	@curl_setopt($ch, CURLOPT_HEADER, true);

	if(x($opts,"cookiejar")) {
		curl_setopt($ch, CURLOPT_COOKIEJAR, $opts["cookiejar"]);
		curl_setopt($ch, CURLOPT_COOKIEFILE, $opts["cookiejar"]);
	}

// These settings aren't needed. We're following the location already.
//	@curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
//	@curl_setopt($ch, CURLOPT_MAXREDIRS, 5);

	if (x($opts,'accept_content')){
		curl_setopt($ch,CURLOPT_HTTPHEADER, array (
			"Accept: " . $opts['accept_content']
		));
	}

	@curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
	@curl_setopt($ch, CURLOPT_USERAGENT, $a->get_useragent());



	if(x($opts,'headers')){
		@curl_setopt($ch, CURLOPT_HTTPHEADER, $opts['headers']);
	}
	if(x($opts,'nobody')){
		@curl_setopt($ch, CURLOPT_NOBODY, $opts['nobody']);
	}
	if(x($opts,'timeout')){
		@curl_setopt($ch, CURLOPT_TIMEOUT, $opts['timeout']);
	} else {
		$curl_time = intval(get_config('system','curl_timeout'));
		@curl_setopt($ch, CURLOPT_TIMEOUT, (($curl_time !== false) ? $curl_time : 60));
	}

	// by default we will allow self-signed certs
	// but you can override this

	$check_cert = get_config('system','verifyssl');
	@curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, (($check_cert) ? true : false));
	@curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, (($check_cert) ? 2 : false));

	$prx = get_config('system','proxy');
	if(strlen($prx)) {
		@curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, 1);
		@curl_setopt($ch, CURLOPT_PROXY, $prx);
		$prxusr = @get_config('system','proxyuser');
		if(strlen($prxusr))
			@curl_setopt($ch, CURLOPT_PROXYUSERPWD, $prxusr);
	}
	if($binary)
		@curl_setopt($ch, CURLOPT_BINARYTRANSFER,1);

	$a->set_curl_code(0);

	// don't let curl abort the entire application
	// if it throws any errors.

	$s = @curl_exec($ch);
	if (curl_errno($ch) !== CURLE_OK) {
		logger('fetch_url error fetching '.$url.': '.curl_error($ch), LOGGER_NORMAL);
	}

	$base = $s;
	$curl_info = @curl_getinfo($ch);

	$http_code = $curl_info['http_code'];
	logger('fetch_url '.$url.': '.$http_code." ".$s, LOGGER_DATA);
	$header = '';

	// Pull out multiple headers, e.g. proxy and continuation headers
	// allow for HTTP/2.x without fixing code

	while(preg_match('/^HTTP\/[1-2].+? [1-5][0-9][0-9]/',$base)) {
		$chunk = substr($base,0,strpos($base,"\r\n\r\n")+4);
		$header .= $chunk;
		$base = substr($base,strlen($chunk));
	}

	$a->set_curl_code($http_code);
	$a->set_curl_content_type($curl_info['content_type']);
	$a->set_curl_headers($header);

	if($http_code == 301 || $http_code == 302 || $http_code == 303 || $http_code == 307) {
		$new_location_info = @parse_url($curl_info["redirect_url"]);
		$old_location_info = @parse_url($curl_info["url"]);

		$newurl = $curl_info["redirect_url"];

		if (($new_location_info["path"] == "") AND ($new_location_info["host"] != ""))
			$newurl = $new_location_info["scheme"]."://".$new_location_info["host"].$old_location_info["path"];

		$matches = array();
		if (preg_match('/(Location:|URI:)(.*?)\n/i', $header, $matches)) {
			$newurl = trim(array_pop($matches));
		}
		if(strpos($newurl,'/') === 0)
			$newurl = $old_location_info["scheme"]."://".$old_location_info["host"].$newurl;
		if (filter_var($newurl, FILTER_VALIDATE_URL)) {
			$redirects++;
			@curl_close($ch);
			return z_fetch_url($newurl,$binary, $redirects, $opts);
		}
	}


	$a->set_curl_code($http_code);
	$a->set_curl_content_type($curl_info['content_type']);

	$body = substr($s,strlen($header));



	$rc = intval($http_code);
	$ret['return_code'] = $rc;
	$ret['success'] = (($rc >= 200 && $rc <= 299) ? true : false);
	$ret['redirect_url'] = $url;
	if(! $ret['success']) {
		$ret['error'] = curl_error($ch);
		$ret['debug'] = $curl_info;
		logger('z_fetch_url: error: ' . $url . ': ' . $ret['error'], LOGGER_DEBUG);
		logger('z_fetch_url: debug: ' . print_r($curl_info,true), LOGGER_DATA);
	}
	$ret['body'] = substr($s,strlen($header));
	$ret['header'] = $header;
	if(x($opts,'debug')) {
		$ret['debug'] = $curl_info;
	}
	@curl_close($ch);

	$a->save_timestamp($stamp1, "network");

	return($ret);

}

// post request to $url. $params is an array of post variables.

/**
 * @brief Post request to $url
 * 
 * @param string $url URL to post
 * @param mixed $params
 * @param string $headers HTTP headers
 * @param integer $redirects Recursion counter for internal use - default = 0
 * @param integer $timeout The timeout in seconds, default system config value or 60 seconds
 * 
 * @return string The content
 */
function post_url($url,$params, $headers = null, &$redirects = 0, $timeout = 0) {
	$stamp1 = microtime(true);

	$a = get_app();
	$ch = curl_init($url);
	if(($redirects > 8) || (! $ch))
		return false;

	logger("post_url: start ".$url, LOGGER_DATA);

	curl_setopt($ch, CURLOPT_HEADER, true);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
	curl_setopt($ch, CURLOPT_POST,1);
	curl_setopt($ch, CURLOPT_POSTFIELDS,$params);
	curl_setopt($ch, CURLOPT_USERAGENT, $a->get_useragent());

	if(intval($timeout)) {
		curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
	}
	else {
		$curl_time = intval(get_config('system','curl_timeout'));
		curl_setopt($ch, CURLOPT_TIMEOUT, (($curl_time !== false) ? $curl_time : 60));
	}

	if(defined('LIGHTTPD')) {
		if(!is_array($headers)) {
			$headers = array('Expect:');
		} else {
			if(!in_array('Expect:', $headers)) {
				array_push($headers, 'Expect:');
			}
		}
	}
	if($headers)
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

	$check_cert = get_config('system','verifyssl');
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, (($check_cert) ? true : false));
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, (($check_cert) ? 2 : false));
	$prx = get_config('system','proxy');
	if(strlen($prx)) {
		curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, 1);
		curl_setopt($ch, CURLOPT_PROXY, $prx);
		$prxusr = get_config('system','proxyuser');
		if(strlen($prxusr))
			curl_setopt($ch, CURLOPT_PROXYUSERPWD, $prxusr);
	}

	$a->set_curl_code(0);

	// don't let curl abort the entire application
	// if it throws any errors.

	$s = @curl_exec($ch);

	$base = $s;
	$curl_info = curl_getinfo($ch);
	$http_code = $curl_info['http_code'];

	logger("post_url: result ".$http_code." - ".$url, LOGGER_DATA);

	$header = '';

	// Pull out multiple headers, e.g. proxy and continuation headers
	// allow for HTTP/2.x without fixing code

	while(preg_match('/^HTTP\/[1-2].+? [1-5][0-9][0-9]/',$base)) {
		$chunk = substr($base,0,strpos($base,"\r\n\r\n")+4);
		$header .= $chunk;
		$base = substr($base,strlen($chunk));
	}

	if($http_code == 301 || $http_code == 302 || $http_code == 303 || $http_code == 307) {
		$matches = array();
		preg_match('/(Location:|URI:)(.*?)\n/', $header, $matches);
		$newurl = trim(array_pop($matches));
		if(strpos($newurl,'/') === 0)
			$newurl = $old_location_info["scheme"] . "://" . $old_location_info["host"] . $newurl;
		if (filter_var($newurl, FILTER_VALIDATE_URL)) {
			$redirects++;
			logger("post_url: redirect ".$url." to ".$newurl);
			return post_url($newurl,$params, $headers, $redirects, $timeout);
			//return fetch_url($newurl,false,$redirects,$timeout);
		}
	}
	$a->set_curl_code($http_code);
	$body = substr($s,strlen($header));

	$a->set_curl_headers($header);

	curl_close($ch);

	$a->save_timestamp($stamp1, "network");

	logger("post_url: end ".$url, LOGGER_DATA);

	return($body);
}

// Generic XML return
// Outputs a basic dfrn XML status structure to STDOUT, with a <status> variable
// of $st and an optional text <message> of $message and terminates the current process.

function xml_status($st, $message = '') {

	$xml_message = ((strlen($message)) ? "\t<message>" . xmlify($message) . "</message>\r\n" : '');

	if($st)
		logger('xml_status returning non_zero: ' . $st . " message=" . $message);

	header( "Content-type: text/xml" );
	echo '<?xml version="1.0" encoding="UTF-8"?>'."\r\n";
	echo "<result>\r\n\t<status>$st</status>\r\n$xml_message</result>\r\n";
	killme();
}


/**
 * @brief Send HTTP status header and exit.
 *
 * @param integer $val HTTP status result value
 * @param array $description optional message
 *    'title' => header title
 *    'description' => optional message
 */

function http_status_exit($val, $description = array()) {
	$err = '';
	if($val >= 400) {
		$err = 'Error';
		if (!isset($description["title"]))
			$description["title"] = $err." ".$val;
	}
	if($val >= 200 && $val < 300)
		$err = 'OK';

	logger('http_status_exit ' . $val);
	header($_SERVER["SERVER_PROTOCOL"] . ' ' . $val . ' ' . $err);

	if (isset($description["title"])) {
		$tpl = get_markup_template('http_status.tpl');
		echo replace_macros($tpl, array('$title' => $description["title"],
						'$description' => $description["description"]));
	}

	killme();

}

// Given an email style address, perform webfinger lookup and
// return the resulting DFRN profile URL, or if no DFRN profile URL
// is located, returns an OStatus subscription template (prefixed
// with the string 'stat:' to identify it as on OStatus template).
// If this isn't an email style address just return $webbie.
// Return an empty string if email-style addresses but webfinger fails,
// or if the resultant personal XRD doesn't contain a supported
// subscription/friend-request attribute.

// amended 7/9/2011 to return an hcard which could save potentially loading
// a lengthy content page to scrape dfrn attributes

function webfinger_dfrn($webbie,&$hcard) {
	if(! strstr($webbie,'@')) {
		return $webbie;
	}
	$profile_link = '';

	$links = webfinger($webbie);
	logger('webfinger_dfrn: ' . $webbie . ':' . print_r($links,true), LOGGER_DATA);
	if(count($links)) {
		foreach($links as $link) {
			if(empty($profile_link) && $link['@attributes']['rel'] === NAMESPACE_DFRN) {
				$profile_link = $link['@attributes']['href'];
			} elseif(empty($profile_link) && $link['@attributes']['rel'] === NAMESPACE_OSTATUSSUB) {
				$profile_link = 'stat:' . $link['@attributes']['template'];
			} elseif(empty($hcard) && $link['@attributes']['rel'] === 'http://microformats.org/profile/hcard') {
				$hcard = $link['@attributes']['href'];
			}
		}
	}
	return $profile_link;
}

/**
 * @brief Perform webfinger lookup on an email style address
 * 
 * @param string $webbi An email style address
 * @param boolean $debug
 * 
 * @return array of link attributes from the personal XRD file
 *    empty array on error/failure
 */
function webfinger($webbie, $debug = false) {
	$host = '';
	if(strstr($webbie,'@')) {
		$host = substr($webbie,strpos($webbie,'@') + 1);
	}
	if(strlen($host)) {
		$tpl = fetch_lrdd_template($host);
		logger('webfinger: lrdd template: ' . $tpl);
		if(strlen($tpl)) {
			$pxrd = str_replace('{uri}', urlencode('acct:' . $webbie), $tpl);
			logger('webfinger: pxrd: ' . $pxrd);
			$links = fetch_xrd_links($pxrd);
			if(! count($links)) {
				// try with double slashes
				$pxrd = str_replace('{uri}', urlencode('acct://' . $webbie), $tpl);
				logger('webfinger: pxrd: ' . $pxrd);
				$links = fetch_xrd_links($pxrd);
			}
			return $links;
		}
	}
	return array();
}

function lrdd($uri, $debug = false) {

	$a = get_app();

	// default priority is host priority, host-meta first

	$priority = 'host';

	// All we have is an email address. Resource-priority is irrelevant
	// because our URI isn't directly resolvable.

	if(strstr($uri,'@')) {
		return(webfinger($uri));
	}

	// get the host meta file

	$host = @parse_url($uri);

	if($host) {
		$url  = ((x($host,'scheme')) ? $host['scheme'] : 'http') . '://';
		$url .= $host['host'] . '/.well-known/host-meta' ;
	}
	else
		return array();

	logger('lrdd: constructed url: ' . $url);

	$xml = fetch_url($url);

	$headers = $a->get_curl_headers();

	if (! $xml)
		return array();

	logger('lrdd: host_meta: ' . $xml, LOGGER_DATA);

	if(! stristr($xml,'<xrd'))
		return array();

	$h = parse_xml_string($xml);
	if(! $h)
		return array();

	$arr = xml::element_to_array($h);

	if(isset($arr['xrd']['property'])) {
		$property = $arr['crd']['property'];
		if(! isset($property[0]))
			$properties = array($property);
		else
			$properties = $property;
		foreach($properties as $prop)
			if((string) $prop['@attributes'] === 'http://lrdd.net/priority/resource')
				$priority = 'resource';
	}

	// save the links in case we need them

	$links = array();

	if(isset($arr['xrd']['link'])) {
		$link = $arr['xrd']['link'];
		if(! isset($link[0]))
			$links = array($link);
		else
			$links = $link;
	}

	// do we have a template or href?

	if(count($links)) {
		foreach($links as $link) {
			if($link['@attributes']['rel'] && attribute_contains($link['@attributes']['rel'],'lrdd')) {
				if(x($link['@attributes'],'template'))
					$tpl = $link['@attributes']['template'];
				elseif(x($link['@attributes'],'href'))
					$href = $link['@attributes']['href'];
			}
		}
	}

	if((! isset($tpl)) || (! strpos($tpl,'{uri}')))
		$tpl = '';

	if($priority === 'host') {
		if(strlen($tpl))
			$pxrd = str_replace('{uri}', urlencode($uri), $tpl);
		elseif(isset($href))
			$pxrd = $href;
		if(isset($pxrd)) {
			logger('lrdd: (host priority) pxrd: ' . $pxrd);
			$links = fetch_xrd_links($pxrd);
			return $links;
		}

		$lines = explode("\n",$headers);
		if(count($lines)) {
			foreach($lines as $line) {
				if((stristr($line,'link:')) && preg_match('/<([^>].*)>.*rel\=[\'\"]lrdd[\'\"]/',$line,$matches)) {
					return(fetch_xrd_links($matches[1]));
					break;
				}
			}
		}
	}


	// priority 'resource'


	$html = fetch_url($uri);
	$headers = $a->get_curl_headers();
	logger('lrdd: headers=' . $headers, LOGGER_DEBUG);

	// don't try and parse raw xml as html
	if(! strstr($html,'<?xml')) {
		require_once('library/HTML5/Parser.php');

		try {
			$dom = HTML5_Parser::parse($html);
		} catch (DOMException $e) {
			logger('lrdd: parse error: ' . $e);
		}

		if(isset($dom) && $dom) {
			$items = $dom->getElementsByTagName('link');
			foreach($items as $item) {
				$x = $item->getAttribute('rel');
				if($x == "lrdd") {
					$pagelink = $item->getAttribute('href');
					break;
				}
			}
		}
	}

	if(isset($pagelink))
		return(fetch_xrd_links($pagelink));

	// next look in HTTP headers

	$lines = explode("\n",$headers);
	if(count($lines)) {
		foreach($lines as $line) {
			/// @TODO Alter the following regex to support multiple relations (space separated)
			if((stristr($line,'link:')) && preg_match('/<([^>].*)>.*rel\=[\'\"]lrdd[\'\"]/',$line,$matches)) {
				$pagelink = $matches[1];
				break;
			}
			// don't try and run feeds through the html5 parser
			if(stristr($line,'content-type:') && ((stristr($line,'application/atom+xml')) || (stristr($line,'application/rss+xml'))))
				return array();
			if(stristr($html,'<rss') || stristr($html,'<feed'))
				return array();
		}
	}

	if(isset($pagelink))
		return(fetch_xrd_links($pagelink));

	// If we haven't found any links, return the host xrd links (which we have already fetched)

	if(isset($links))
		return $links;

	return array();

}

// Given a host name, locate the LRDD template from that
// host. Returns the LRDD template or an empty string on
// error/failure.

function fetch_lrdd_template($host) {
	$tpl = '';

	$url1 = 'https://' . $host . '/.well-known/host-meta' ;
	$url2 = 'http://' . $host . '/.well-known/host-meta' ;
	$links = fetch_xrd_links($url1);
	logger('fetch_lrdd_template from: ' . $url1);
	logger('template (https): ' . print_r($links,true));
	if(! count($links)) {
		logger('fetch_lrdd_template from: ' . $url2);
		$links = fetch_xrd_links($url2);
		logger('template (http): ' . print_r($links,true));
	}
	if(count($links)) {
		foreach($links as $link)
			if($link['@attributes']['rel'] && $link['@attributes']['rel'] === 'lrdd' && (!$link['@attributes']['type'] || $link['@attributes']['type'] === 'application/xrd+xml'))
				$tpl = $link['@attributes']['template'];
	}
	if(! strpos($tpl,'{uri}'))
		$tpl = '';
	return $tpl;
}

/**
 * @brief Given a URL, retrieve the page as an XRD document.
 * 
 * @param string $url An url
 * @return array of links
 *    return empty array on error/failure
 */
function fetch_xrd_links($url) {

	$xrd_timeout = intval(get_config('system','xrd_timeout'));
	$redirects = 0;
	$xml = fetch_url($url,false,$redirects,(($xrd_timeout) ? $xrd_timeout : 20), "application/xrd+xml");

	logger('fetch_xrd_links: ' . $xml, LOGGER_DATA);

	if ((! $xml) || (! stristr($xml,'<xrd')))
		return array();

	// fix diaspora's bad xml
	$xml = str_replace(array('href=&quot;','&quot;/>'),array('href="','"/>'),$xml);

	$h = parse_xml_string($xml);
	if(! $h)
		return array();

	$arr = xml::element_to_array($h);

	$links = array();

	if(isset($arr['xrd']['link'])) {
		$link = $arr['xrd']['link'];
		if(! isset($link[0]))
			$links = array($link);
		else
			$links = $link;
	}
	if(isset($arr['xrd']['alias'])) {
		$alias = $arr['xrd']['alias'];
		if(! isset($alias[0]))
			$aliases = array($alias);
		else
			$aliases = $alias;
		if(is_array($aliases) && count($aliases)) {
			foreach($aliases as $alias) {
				$links[]['@attributes'] = array('rel' => 'alias' , 'href' => $alias);
			}
		}
	}

	logger('fetch_xrd_links: ' . print_r($links,true), LOGGER_DATA);

	return $links;

}

/**
 * @brief Check URL to se if ts's real
 * 
 * Take a URL from the wild, prepend http:// if necessary
 * and check DNS to see if it's real (or check if is a valid IP address)
 * 
 * @param string $url The URL to be validated
 * @return boolean True if it's a valid URL, fals if something wrong with it
 */
function validate_url(&$url) {
	logger(sprintf('[%s:%d]: url=%s - CALLED!', __FUNCTION__, __LINE__, $url), LOGGER_TRACE);

	if(get_config('system','disable_url_validation'))
		return true;

	// no naked subdomains (allow localhost for tests)
	if(strpos($url,'.') === false && strpos($url,'/localhost/') === false)
		return false;

	if(substr($url,0,4) != 'http' && substr($url,0,5) != 'https')
		$url = 'http://' . $url;

	logger(sprintf('[%s:%d]: url=%s - before parse_url() ...', __FUNCTION__, __LINE__, $url), LOGGER_DEBUG);

	$h = @parse_url($url);

	logger(sprintf('[%s:%d]: h[]=%s', __FUNCTION__, __LINE__, gettype($h)), LOGGER_DEBUG);

	if(($h) && (dns_get_record($h['host'], DNS_A + DNS_CNAME + DNS_PTR) || filter_var($h['host'], FILTER_VALIDATE_IP) )) {
		return true;
	}
	return false;
}

/**
 * @brief Checks that email is an actual resolvable internet address
 * 
 * @param string $addr The email address
 * @return boolean True if it's a valid email address, false if it's not
 */
function validate_email($addr) {

	if(get_config('system','disable_email_validation'))
		return true;

	if(! strpos($addr,'@'))
		return false;
	$h = substr($addr,strpos($addr,'@') + 1);

	if(($h) && (dns_get_record($h, DNS_A + DNS_CNAME + DNS_PTR + DNS_MX) || filter_var($h, FILTER_VALIDATE_IP) )) {
		return true;
	}
	return false;
}

/**
 * @brief Check if URL is allowed
 * 
 * Check $url against our list of allowed sites,
 * wildcards allowed. If allowed_sites is unset return true;
 * 
 * @param string $url URL which get tested
 * @return boolean True if url is allowed otherwise return false
 */
function allowed_url($url) {

	$h = @parse_url($url);

	if(! $h) {
		return false;
	}

	$str_allowed = get_config('system','allowed_sites');
	if(! $str_allowed)
		return true;

	$found = false;

	$host = strtolower($h['host']);

	// always allow our own site

	if($host == strtolower($_SERVER['SERVER_NAME']))
		return true;

	$fnmatch = function_exists('fnmatch');
	$allowed = explode(',',$str_allowed);

	if(count($allowed)) {
		foreach($allowed as $a) {
			$pat = strtolower(trim($a));
			if(($fnmatch && fnmatch($pat,$host)) || ($pat == $host)) {
				$found = true;
				break;
			}
		}
	}
	return $found;
}

/**
 * @brief Check if email address is allowed to register here.
 * 
 * Compare against our list (wildcards allowed).
 * 
 * @param type $email
 * @return boolean False if not allowed, true if allowed
 *    or if allowed list is not configured
 */
function allowed_email($email) {


	$domain = strtolower(substr($email,strpos($email,'@') + 1));
	if(! $domain)
		return false;

	$str_allowed = get_config('system','allowed_email');
	if(! $str_allowed)
		return true;

	$found = false;

	$fnmatch = function_exists('fnmatch');
	$allowed = explode(',',$str_allowed);

	if(count($allowed)) {
		foreach($allowed as $a) {
			$pat = strtolower(trim($a));
			if(($fnmatch && fnmatch($pat,$domain)) || ($pat == $domain)) {
				$found = true;
				break;
			}
		}
	}
	return $found;
}

function avatar_img($email) {

	$a = get_app();

	$avatar['size'] = 175;
	$avatar['email'] = $email;
	$avatar['url'] = '';
	$avatar['success'] = false;

	call_hooks('avatar_lookup', $avatar);

	if(! $avatar['success'])
		$avatar['url'] = $a->get_baseurl() . '/images/person-175.jpg';

	logger('Avatar: ' . $avatar['email'] . ' ' . $avatar['url'], LOGGER_DEBUG);
	return $avatar['url'];
}


function parse_xml_string($s,$strict = true) {
	/// @todo Move this function to the xml class
	if($strict) {
		if(! strstr($s,'<?xml'))
			return false;
		$s2 = substr($s,strpos($s,'<?xml'));
	}
	else
		$s2 = $s;
	libxml_use_internal_errors(true);

	$x = @simplexml_load_string($s2);
	if(! $x) {
		logger('libxml: parse: error: ' . $s2, LOGGER_DATA);
		foreach(libxml_get_errors() as $err)
			logger('libxml: parse: ' . $err->code." at ".$err->line.":".$err->column." : ".$err->message, LOGGER_DATA);
		libxml_clear_errors();
	}
	return $x;
}

function scale_external_images($srctext, $include_link = true, $scale_replace = false) {

	// Suppress "view full size"
	if (intval(get_config('system','no_view_full_size')))
		$include_link = false;

	$a = get_app();

	// Picture addresses can contain special characters
	$s = htmlspecialchars_decode($srctext);

	$matches = null;
	$c = preg_match_all('/\[img.*?\](.*?)\[\/img\]/ism',$s,$matches,PREG_SET_ORDER);
	if($c) {
		require_once('include/Photo.php');
		foreach($matches as $mtch) {
			logger('scale_external_image: ' . $mtch[1]);

			$hostname = str_replace('www.','',substr($a->get_baseurl(),strpos($a->get_baseurl(),'://')+3));
			if(stristr($mtch[1],$hostname))
				continue;

			// $scale_replace, if passed, is an array of two elements. The
			// first is the name of the full-size image. The second is the
			// name of a remote, scaled-down version of the full size image.
			// This allows Friendica to display the smaller remote image if
			// one exists, while still linking to the full-size image
			if($scale_replace)
				$scaled = str_replace($scale_replace[0], $scale_replace[1], $mtch[1]);
			else
				$scaled = $mtch[1];
			$i = @fetch_url($scaled);
			if(! $i)
				return $srctext;

			// guess mimetype from headers or filename
			$type = guess_image_type($mtch[1],true);

			if($i) {
				$ph = new Photo($i, $type);
				if($ph->is_valid()) {
					$orig_width = $ph->getWidth();
					$orig_height = $ph->getHeight();

					if($orig_width > 640 || $orig_height > 640) {

						$ph->scaleImage(640);
						$new_width = $ph->getWidth();
						$new_height = $ph->getHeight();
						logger('scale_external_images: ' . $orig_width . '->' . $new_width . 'w ' . $orig_height . '->' . $new_height . 'h' . ' match: ' . $mtch[0], LOGGER_DEBUG);
						$s = str_replace($mtch[0],'[img=' . $new_width . 'x' . $new_height. ']' . $scaled . '[/img]'
							. "\n" . (($include_link)
								? '[url=' . $mtch[1] . ']' . t('view full size') . '[/url]' . "\n"
								: ''),$s);
						logger('scale_external_images: new string: ' . $s, LOGGER_DEBUG);
					}
				}
			}
		}
	}

	// replace the special char encoding
	$s = htmlspecialchars($s,ENT_NOQUOTES,'UTF-8');
	return $s;
}


function fix_contact_ssl_policy(&$contact,$new_policy) {

	$ssl_changed = false;
	if((intval($new_policy) == SSL_POLICY_SELFSIGN || $new_policy === 'self') && strstr($contact['url'],'https:')) {
		$ssl_changed = true;
		$contact['url']     = 	str_replace('https:','http:',$contact['url']);
		$contact['request'] = 	str_replace('https:','http:',$contact['request']);
		$contact['notify']  = 	str_replace('https:','http:',$contact['notify']);
		$contact['poll']    = 	str_replace('https:','http:',$contact['poll']);
		$contact['confirm'] = 	str_replace('https:','http:',$contact['confirm']);
		$contact['poco']    = 	str_replace('https:','http:',$contact['poco']);
	}

	if((intval($new_policy) == SSL_POLICY_FULL || $new_policy === 'full') && strstr($contact['url'],'http:')) {
		$ssl_changed = true;
		$contact['url']     = 	str_replace('http:','https:',$contact['url']);
		$contact['request'] = 	str_replace('http:','https:',$contact['request']);
		$contact['notify']  = 	str_replace('http:','https:',$contact['notify']);
		$contact['poll']    = 	str_replace('http:','https:',$contact['poll']);
		$contact['confirm'] = 	str_replace('http:','https:',$contact['confirm']);
		$contact['poco']    = 	str_replace('http:','https:',$contact['poco']);
	}

	if($ssl_changed) {
		q("update contact set
			url = '%s',
			request = '%s',
			notify = '%s',
			poll = '%s',
			confirm = '%s',
			poco = '%s'
			where id = %d limit 1",
			dbesc($contact['url']),
			dbesc($contact['request']),
			dbesc($contact['notify']),
			dbesc($contact['poll']),
			dbesc($contact['confirm']),
			dbesc($contact['poco']),
			intval($contact['id'])
		);
	}
}

function original_url($url, $depth=1, $fetchbody = false) {

	$a = get_app();

	// Remove Analytics Data from Google and other tracking platforms
	$urldata = parse_url($url);
	if (is_string($urldata["query"])) {
		$query = $urldata["query"];
		parse_str($query, $querydata);

		if (is_array($querydata))
			foreach ($querydata AS $param=>$value)
				if (in_array($param, array("utm_source", "utm_medium", "utm_term", "utm_content", "utm_campaign",
							"wt_mc", "pk_campaign", "pk_kwd", "mc_cid", "mc_eid",
							"fb_action_ids", "fb_action_types", "fb_ref",
							"awesm", "wtrid",
							"woo_campaign", "woo_source", "woo_medium", "woo_content", "woo_term"))) {

					$pair = $param."=".urlencode($value);
					$url = str_replace($pair, "", $url);

					// Second try: if the url isn't encoded completely
					$pair = $param."=".str_replace(" ", "+", $value);
					$url = str_replace($pair, "", $url);

					// Third try: Maybey the url isn't encoded at all
					$pair = $param."=".$value;
					$url = str_replace($pair, "", $url);

					$url = str_replace(array("?&", "&&"), array("?", ""), $url);
				}

		if (substr($url, -1, 1) == "?")
			$url = substr($url, 0, -1);
	}

	if ($depth > 10)
		return($url);

	$url = trim($url, "'");

	$stamp1 = microtime(true);

	$siteinfo = array();
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_HEADER, 1);
	curl_setopt($ch, CURLOPT_NOBODY, 1);
	curl_setopt($ch, CURLOPT_TIMEOUT, 10);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_USERAGENT, $a->get_useragent());

	$header = curl_exec($ch);
	$curl_info = @curl_getinfo($ch);
	$http_code = $curl_info['http_code'];
	curl_close($ch);

	$a->save_timestamp($stamp1, "network");

	if ($http_code == 0)
		return($url);

	if ((($curl_info['http_code'] == "301") OR ($curl_info['http_code'] == "302"))
		AND (($curl_info['redirect_url'] != "") OR ($curl_info['location'] != ""))) {
		if ($curl_info['redirect_url'] != "")
			return(original_url($curl_info['redirect_url'], ++$depth, $fetchbody));
		else
			return(original_url($curl_info['location'], ++$depth, $fetchbody));
	}

	// Check for redirects in the meta elements of the body if there are no redirects in the header.
	if (!$fetchbody)
		return(original_url($url, ++$depth, true));

	// if the file is too large then exit
	if ($curl_info["download_content_length"] > 1000000)
		return($url);

	// if it isn't a HTML file then exit
	if (($curl_info["content_type"] != "") AND !strstr(strtolower($curl_info["content_type"]),"html"))
		return($url);

	$stamp1 = microtime(true);

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_NOBODY, 0);
	curl_setopt($ch, CURLOPT_TIMEOUT, 10);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_USERAGENT, $a->get_useragent());

	$body = curl_exec($ch);
	curl_close($ch);

	$a->save_timestamp($stamp1, "network");

	if (trim($body) == "")
		return($url);

	// Check for redirect in meta elements
	$doc = new DOMDocument();
	@$doc->loadHTML($body);

	$xpath = new DomXPath($doc);

	$list = $xpath->query("//meta[@content]");
	foreach ($list as $node) {
		$attr = array();
		if ($node->attributes->length)
			foreach ($node->attributes as $attribute)
				$attr[$attribute->name] = $attribute->value;

		if (@$attr["http-equiv"] == 'refresh') {
			$path = $attr["content"];
			$pathinfo = explode(";", $path);
			$content = "";
			foreach ($pathinfo AS $value)
				if (substr(strtolower($value), 0, 4) == "url=")
					return(original_url(substr($value, 4), ++$depth));
		}
	}

	return($url);
}

function short_link($url) {
	require_once('library/slinky.php');
	$slinky = new Slinky($url);
	$yourls_url = get_config('yourls','url1');
	if ($yourls_url) {
		$yourls_username = get_config('yourls','username1');
		$yourls_password = get_config('yourls', 'password1');
		$yourls_ssl = get_config('yourls', 'ssl1');
		$yourls = new Slinky_YourLS();
		$yourls->set('username', $yourls_username);
		$yourls->set('password', $yourls_password);
		$yourls->set('ssl', $yourls_ssl);
		$yourls->set('yourls-url', $yourls_url);
		$slinky->set_cascade( array($yourls, new Slinky_UR1ca(), new Slinky_Trim(), new Slinky_IsGd(), new Slinky_TinyURL()));
	} else {
		// setup a cascade of shortening services
		// try to get a short link from these services
		// in the order ur1.ca, trim, id.gd, tinyurl
		$slinky->set_cascade(array(new Slinky_UR1ca(), new Slinky_Trim(), new Slinky_IsGd(), new Slinky_TinyURL()));
	}
	return $slinky->short();
}

/**
 * @brief Encodes content to json
 * 
 * This function encodes an array to json format
 * and adds an application/json HTTP header to the output.
 * After finishing the process is getting killed.
 * 
 * @param array $x The input content
 */
function json_return_and_die($x) {
	header("content-type: application/json");
	echo json_encode($x);
	killme();
}
