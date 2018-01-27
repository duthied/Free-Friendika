<?php
/**
 * @file include/network.php
 */
use Friendica\App;
use Friendica\Core\Addon;
use Friendica\Core\L10n;
use Friendica\Core\System;
use Friendica\Core\Config;
use Friendica\Network\Probe;
use Friendica\Object\Image;
use Friendica\Util\Network;
use Friendica\Util\XML;

function fetch_url($url, $binary = false, &$redirects = 0, $timeout = 0, $accept_content = null, $cookiejar = 0)
{
	return Network::fetchURL($url, $binary, $redirects, $timeout, $accept_content, $cookiejar);
}

function z_fetch_url($url, $binary = false, &$redirects = 0, $opts = [])
{
	return Network::zFetchURL($url, $binary, $redirects, $opts);
}

function post_url($url, $params, $headers = null, &$redirects = 0, $timeout = 0)
{
	return Network::postURL($url, $params, $headers, $redirects, $timeout);
}

function xml_status($st, $message = '')
{
	Network::xmlStatus($st, $message);
}

function http_status_exit($val, $description = [])
{
	Network::httpStatusExit($val, $description);
}

function validate_url($url)
{
	return Network::validateURL($url);
}

function validate_email($addr)
{
	return Network::validateEmail($addr);
}

function allowed_url($url)
{
	return Network::allowedURL($url);
}

function blocked_url($url)
{
	return Network::blockedURL($url);
}

function allowedEmail($email)
{
	return Network::allowedEmail($email);
}

function allowed_domain($domain, array $domain_list)
{
	return Network::allowedDomain($domain, $domain_list);
}

function avatar_img($email)
{
	return Network::avatarImg($email);
}


function parse_xml_string($s, $strict = true)
{
	return Network::parseXmlString($s, $strict);
}

function scale_external_images($srctext, $include_link = true, $scale_replace = false)
{
	return Network::scaleExternalImages($srctext, $include_link, $scale_replace);
}

function fix_contact_ssl_policy(&$contact, $new_policy)
{
	Network::fixContactSslPolicy($contact, $new_policy);
}

function strip_tracking_query_params($url)
{
	return Network::stripTrackingQueryParams($url);
}

function original_url($url, $depth = 1, $fetchbody = false)
{
	return Network::originalURL($url, $depth, $fetchbody);
}

function short_link($url)
{
	return Network::shortLink($url);
}

function json_return_and_die($x)
{
	Network::jsonReturnAndDie($x);
}

function matching_url($url1, $url2)
{
	return Network::matchingURL($url1, $url2);
}

function unParseUrl($parsed)
{
	return Network::unParseURL($parsed);
}
