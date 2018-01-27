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
