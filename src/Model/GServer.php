<?php

/**
 * @file src/Model/GServer.php
 * @brief This file includes the GServer class to handle with servers
 */
namespace Friendica\Model;

use DOMDocument;
use DOMXPath;
use Friendica\Core\Config;
use Friendica\Core\Protocol;
use Friendica\Database\DBA;
use Friendica\Module\Register;
use Friendica\Util\Network;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Strings;
use Friendica\Util\XML;
use Friendica\Core\Logger;

/**
 * @brief This class handles GServer related functions
 */
class GServer
{
	/**
	 * Detect server data (type, protocol, version number, ...)
	 * The detected data is then updated or inserted in the gserver table.
	 *
	 * @param string  $url   Server url
	 *
	 * @return boolean 'true' if server could be detected
	 */
	public static function detect($url)
	{
		/// @Todo:
		// - poco endpoint
		// - Pleroma version number

		$serverdata = [];

		// When a nodeinfo is present, we don't need to dig further
		$xrd_timeout = Config::get('system', 'xrd_timeout');
		$curlResult = Network::curl($url . '/.well-known/nodeinfo', false, ['timeout' => $xrd_timeout]);
		if ($curlResult->isTimeout()) {
			DBA::update('gserver', ['last_failure' => DateTimeFormat::utcNow()], ['nurl' => Strings::normaliseLink($url)]);
			return false;
		}

		$nodeinfo = self::fetchNodeinfo($url, $curlResult);

		// When nodeinfo isn't present, we use the older 'statistics.json' endpoint
		if (empty($nodeinfo)) {
			$nodeinfo = self::fetchStatistics($url);
		}

		// If that didn't work out well, we use some protocol specific endpoints
		if (empty($nodeinfo) || ($nodeinfo['network'] == Protocol::DFRN)) {
			// Fetch the landing page, possibly it reveals some data
			$curlResult = Network::curl($url, false, ['timeout' => $xrd_timeout]);
			if ($curlResult->isSuccess()) {
				$serverdata = self::analyseRootHeader($curlResult, $serverdata);
				$serverdata = self::analyseRootBody($curlResult, $serverdata);
			}

			if (!$curlResult->isSuccess() || empty($curlResult->getBody())) {
				DBA::update('gserver', ['last_failure' => DateTimeFormat::utcNow()], ['nurl' => Strings::normaliseLink($url)]);
				return false;
			}

			if (empty($serverdata['network']) || ($serverdata['network'] == Protocol::DFRN)) {
				$serverdata = self::detectFriendica($url, $serverdata);
			}

			if (empty($serverdata['network']) || ($serverdata['network'] == Protocol::ACTIVITYPUB)) {
				$serverdata = self::detectMastodonAlikes($url, $serverdata);
			}

			// the 'siteinfo.json' is some specific endpoint of Hubzilla and Red
			if (empty($serverdata['network']) || ($serverdata['network'] == Protocol::ZOT)) {
				$serverdata = self::fetchSiteinfo($url, $serverdata);
			}

			// The 'siteinfo.json' doesn't seem to be present on older Hubzilla installations
			if (empty($serverdata['network'])) {
				$serverdata = self::detectHubzilla($url, $serverdata);
			}

			if (empty($serverdata['network'])) {
				$serverdata = self::detectNextcloud($url, $serverdata);
			}

			if (empty($serverdata['network'])) {
				$serverdata = self::detectGNUSocial($url, $serverdata);
			}
		} else {
			$serverdata = $nodeinfo;
		}

		// We can't detect the network type. Possibly it is some system that we don't know yet
		if (empty($serverdata['network'])) {
			$serverdata['network'] = Protocol::PHANTOM;
		}

		$serverdata['url'] = $url;
		$serverdata['nurl'] = Strings::normaliseLink($url);

		// When we don't have the registered users, we simply count what we know
		if (empty($serverdata['registered-users'])) {
			$gcontacts = DBA::count('gcontact', ['server_url' => [$url, $serverdata['nurl']]]);
			$apcontacts = DBA::count('apcontact', ['baseurl' => [$url, $serverdata['nurl']]]);
			$contacts = DBA::count('contact', ['uid' => 0, 'baseurl' => [$url, $serverdata['nurl']]]);
			$serverdata['registered-users'] = max($gcontacts, $apcontacts, $contacts);
		}

		$serverdata['last_contact'] = DateTimeFormat::utcNow();

		if (!DBA::exists('gserver', ['nurl' => Strings::normaliseLink($url)])) {
			$serverdata['created'] = DateTimeFormat::utcNow();
			$ret = DBA::insert('gserver', $serverdata);
		} else {
			$ret = DBA::update('gserver', $serverdata, ['nurl' => $serverdata['nurl']]);
		}

		print_r($serverdata);

		return $ret;
	}

	private static function fetchStatistics($url)
	{
		$curlResult = Network::curl($url . '/statistics.json');
		if (!$curlResult->isSuccess()) {
			return [];
		}

		$data = json_decode($curlResult->getBody(), true);
		if (empty($data)) {
			return [];
		}

		$serverdata = [];

		if (!empty($data['version'])) {
			$serverdata['version'] = $data['version'];
			// Version numbers on statistics.json are presented with additional info, e.g.:
			// 0.6.3.0-p1702cc1c, 0.6.99.0-p1b9ab160 or 3.4.3-2-1191.
			$serverdata['version'] = preg_replace('=(.+)-(.{4,})=ism', '$1', $serverdata['version']);
		}

		if (!empty($data['name'])) {
			$serverdata['site_name'] = $data['name'];
		}

		if (!empty($data['network'])) {
			$serverdata['platform'] = $data['network'];

			if ($serverdata['platform'] == 'Diaspora') {
				$serverdata['network'] = Protocol::DIASPORA;
			} elseif ($serverdata['platform'] == 'Friendica') {
				$serverdata['network'] = Protocol::DFRN;
			} elseif ($serverdata['platform'] == 'hubzilla') {
				$serverdata['network'] = Protocol::ZOT;
			} elseif ($serverdata['platform'] == 'redmatrix') {
				$serverdata['network'] = Protocol::ZOT;
			}
		}


		if (!empty($data['registrations_open'])) {
			$serverdata['register_policy'] = Register::OPEN;
		} else {
			$serverdata['register_policy'] = Register::CLOSED;
		}

		return $serverdata;
	}

	/**
	 * @brief Detect server type by using the nodeinfo data
	 *
	 * @param string $url address of the server
	 * @return array Server data
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	private static function fetchNodeinfo($url, $curlResult)
	{
		$nodeinfo = json_decode($curlResult->getBody(), true);

		if (!is_array($nodeinfo) || empty($nodeinfo['links'])) {
			return [];
		}

		$nodeinfo1_url = '';
		$nodeinfo2_url = '';

		foreach ($nodeinfo['links'] as $link) {
			if (!is_array($link) || empty($link['rel']) || empty($link['href'])) {
				Logger::info('Invalid nodeinfo format', ['url' => $url]);
				continue;
			}
			if ($link['rel'] == 'http://nodeinfo.diaspora.software/ns/schema/1.0') {
				$nodeinfo1_url = $link['href'];
			} elseif ($link['rel'] == 'http://nodeinfo.diaspora.software/ns/schema/2.0') {
				$nodeinfo2_url = $link['href'];
			}
		}

		if ($nodeinfo1_url . $nodeinfo2_url == '') {
			return [];
		}

		$server = [];

		// When the nodeinfo url isn't on the same host, then there is obviously something wrong
		if (!empty($nodeinfo2_url) && (parse_url($url, PHP_URL_HOST) == parse_url($nodeinfo2_url, PHP_URL_HOST))) {
			$server = self::parseNodeinfo2($nodeinfo2_url);
		}

		// When the nodeinfo url isn't on the same host, then there is obviously something wrong
		if (empty($server) && !empty($nodeinfo1_url) && (parse_url($url, PHP_URL_HOST) == parse_url($nodeinfo1_url, PHP_URL_HOST))) {
			$server = self::parseNodeinfo1($nodeinfo1_url);
		}

		return $server;
	}

	/**
	 * @brief Parses Nodeinfo 1
	 *
	 * @param string $nodeinfo_url address of the nodeinfo path
	 * @return array Server data
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	private static function parseNodeinfo1($nodeinfo_url)
	{
		$curlResult = Network::curl($nodeinfo_url);

		if (!$curlResult->isSuccess()) {
			return false;
		}

		$nodeinfo = json_decode($curlResult->getBody(), true);

		if (!is_array($nodeinfo)) {
			return false;
		}

		$server = [];

		$server['register_policy'] = Register::CLOSED;

		if (!empty($nodeinfo['openRegistrations'])) {
			$server['register_policy'] = Register::OPEN;
		}

		if (is_array($nodeinfo['software'])) {
			if (!empty($nodeinfo['software']['name'])) {
				$server['platform'] = $nodeinfo['software']['name'];
			}

			if (!empty($nodeinfo['software']['version'])) {
				$server['version'] = $nodeinfo['software']['version'];
				// Version numbers on Nodeinfo are presented with additional info, e.g.:
				// 0.6.3.0-p1702cc1c, 0.6.99.0-p1b9ab160 or 3.4.3-2-1191.
				$server['version'] = preg_replace('=(.+)-(.{4,})=ism', '$1', $server['version']);
			}
		}

		if (!empty($nodeinfo['metadata']['nodeName'])) {
			$server['site_name'] = $nodeinfo['metadata']['nodeName'];
		}

		if (!empty($nodeinfo['usage']['users']['total'])) {
			$server['registered-users'] = $nodeinfo['usage']['users']['total'];
		}

		if (!empty($nodeinfo['protocols']['inbound']) && is_array($nodeinfo['protocols']['inbound'])) {
			$protocols = [];
			foreach ($nodeinfo['protocols']['inbound'] as $protocol) {
				$protocols[$protocol] = true;
			}

			if (!empty($protocols['friendica'])) {
				$server['network'] = Protocol::DFRN;
			} elseif (!empty($protocols['activitypub'])) {
				$server['network'] = Protocol::ACTIVITYPUB;
			} elseif (!empty($protocols['diaspora'])) {
				$server['network'] = Protocol::DIASPORA;
			} elseif (!empty($protocols['ostatus'])) {
				$server['network'] = Protocol::OSTATUS;
			} elseif (!empty($protocols['gnusocial'])) {
				$server['network'] = Protocol::OSTATUS;
			} elseif (!empty($protocols['zot'])) {
				$server['network'] = Protocol::ZOT;
			}
		}

		if (!$server) {
			return false;
		}

		return $server;
	}

	/**
	 * @brief Parses Nodeinfo 2
	 *
	 * @param string $nodeinfo_url address of the nodeinfo path
	 * @return array Server data
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	private static function parseNodeinfo2($nodeinfo_url)
	{
		$curlResult = Network::curl($nodeinfo_url);
		if (!$curlResult->isSuccess()) {
			return false;
		}

		$nodeinfo = json_decode($curlResult->getBody(), true);

		if (!is_array($nodeinfo)) {
			return false;
		}

		$server = [];

		$server['register_policy'] = Register::CLOSED;

		if (!empty($nodeinfo['openRegistrations'])) {
			$server['register_policy'] = Register::OPEN;
		}

		if (is_array($nodeinfo['software'])) {
			if (!empty($nodeinfo['software']['name'])) {
				$server['platform'] = $nodeinfo['software']['name'];
			}

			if (!empty($nodeinfo['software']['version'])) {
				$server['version'] = $nodeinfo['software']['version'];
				// Version numbers on Nodeinfo are presented with additional info, e.g.:
				// 0.6.3.0-p1702cc1c, 0.6.99.0-p1b9ab160 or 3.4.3-2-1191.
				$server['version'] = preg_replace('=(.+)-(.{4,})=ism', '$1', $server['version']);
			}
		}

		if (!empty($nodeinfo['metadata']['nodeName'])) {
			$server['site_name'] = $nodeinfo['metadata']['nodeName'];
		}

		if (!empty($nodeinfo['usage']['users']['total'])) {
			$server['registered-users'] = $nodeinfo['usage']['users']['total'];
		}

		if (!empty($nodeinfo['protocols'])) {
			$protocols = [];
			foreach ($nodeinfo['protocols'] as $protocol) {
				$protocols[$protocol] = true;
			}

			if (!empty($protocols['friendica'])) {
				$server['network'] = Protocol::DFRN;
			} elseif (!empty($protocols['activitypub'])) {
				$server['network'] = Protocol::ACTIVITYPUB;
			} elseif (!empty($protocols['diaspora'])) {
				$server['network'] = Protocol::DIASPORA;
			} elseif (!empty($protocols['ostatus'])) {
				$server['network'] = Protocol::OSTATUS;
			} elseif (!empty($protocols['gnusocial'])) {
				$server['network'] = Protocol::OSTATUS;
			} elseif (!empty($protocols['zot'])) {
				$server['network'] = Protocol::ZOT;
			}
		}

		if (empty($server)) {
			return false;
		}

		return $server;
	}

	private static function fetchSiteinfo($url, $serverdata)
	{
		$curlResult = Network::curl($url . '/siteinfo.json');
		if (!$curlResult->isSuccess()) {
			return $serverdata;
		}

		$data = json_decode($curlResult->getBody(), true);
		if (empty($data)) {
			return $serverdata;
		}

		if (!empty($data['url'])) {
			$serverdata['platform'] = $data['platform'];
			$serverdata['version'] = $data['version'];
		}

		if (!empty($data['plugins'])) {
			if (in_array('pubcrawl', $data['plugins'])) {
				$serverdata['network'] = Protocol::ACTIVITYPUB;
			} elseif (in_array('diaspora', $data['plugins'])) {
				$serverdata['network'] = Protocol::DIASPORA;
			} elseif (in_array('gnusoc', $data['plugins'])) {
				$serverdata['network'] = Protocol::OSTATUS;
			} else {
				$serverdata['network'] = Protocol::ZOT;
			}
		}

		if (!empty($data['site_name'])) {
			$serverdata['site_name'] = $data['site_name'];
		}

		if (!empty($data['channels_total'])) {
			$serverdata['registered-users'] = $data['channels_total'];
		}

		if (!empty($data['register_policy'])) {
			switch ($data['register_policy']) {
				case 'REGISTER_OPEN':
					$serverdata['register_policy'] = Register::OPEN;
					break;

				case 'REGISTER_APPROVE':
					$serverdata['register_policy'] = Register::APPROVE;
					break;

				case 'REGISTER_CLOSED':
				default:
					$serverdata['register_policy'] = Register::CLOSED;
					break;
			}
		}

		return $serverdata;
	}

	private static function detectNextcloud($url, $serverdata)
	{
		$curlResult = Network::curl($url . '/status.php');

		if (!$curlResult->isSuccess() || ($curlResult->getBody() == '')) {
			return $serverdata;
		}

		$data = json_decode($curlResult->getBody(), true);
		if (empty($data)) {
			return $serverdata;
		}

		if (!empty($data['version'])) {
			$serverdata['platform'] = 'nextcloud';
			$serverdata['version'] = $data['version'];
			$serverdata['network'] = Protocol::ACTIVITYPUB;
		}

		return $serverdata;
	}

	private static function detectMastodonAlikes($url, $serverdata)
	{
		$curlResult = Network::curl($url . '/api/v1/instance');

		if (!$curlResult->isSuccess() || ($curlResult->getBody() == '')) {
			return $serverdata;
		}

		$data = json_decode($curlResult->getBody(), true);
		if (empty($data)) {
			return $serverdata;
		}

		if (!empty($data['version'])) {
			$serverdata['platform'] = 'mastodon';
			$serverdata['version'] = defaults($data, 'version', '');
			$serverdata['network'] = Protocol::ACTIVITYPUB;
		}

		if (!empty($data['title'])) {
			$serverdata['site_name'] = $data['title'];
		}

		if (!empty($data['description'])) {
			$serverdata['info'] = trim($data['description']);
		}

		if (!empty($data['stats']['user_count'])) {
			$serverdata['registered-users'] = $data['stats']['user_count'];
		}

		if (!empty($serverdata['version']) && strstr($serverdata['version'], 'Pleroma')) {
			$serverdata['platform'] = 'pleroma';
			$serverdata['version'] = trim(str_replace('Pleroma', '', $serverdata['version'])); // 2.7.2 (compatible; Pleroma 1.0.0-1225-gf31ad554-develop)
		}

		return $serverdata;
	}

	private static function detectHubzilla($url, $serverdata)
	{
		$curlResult = Network::curl($url . '/api/statusnet/config.json');
		if (!$curlResult->isSuccess() || ($curlResult->getBody() == '')) {
			return $serverdata;
		}

		$data = json_decode($curlResult->getBody(), true);
		if (empty($data)) {
			return $serverdata;
		}

		if (!empty($data['site']['name'])) {
			$serverdata['site_name'] = $data['site']['name'];
		}

		if (!empty($data['site']['platform'])) {
			$serverdata['platform'] = $data['site']['platform']['PLATFORM_NAME'];
			$serverdata['version'] = $data['site']['platform']['STD_VERSION'];
			$serverdata['network'] = Protocol::ZOT;
		}

		if (!empty($data['site']['hubzilla'])) {
			$serverdata['platform'] = $data['site']['hubzilla']['PLATFORM_NAME'];
			$serverdata['version'] = $data['site']['hubzilla']['RED_VERSION'];
			$serverdata['network'] = Protocol::ZOT;
		}

		if (!empty($data['site']['redmatrix'])) {
			if (!empty($data['site']['redmatrix']['PLATFORM_NAME'])) {
				$serverdata['platform'] = $data['site']['redmatrix']['PLATFORM_NAME'];
			} elseif (!empty($data['site']['redmatrix']['RED_PLATFORM'])) {
				$serverdata['platform'] = $data['site']['redmatrix']['RED_PLATFORM'];
			}

			$serverdata['version'] = $data['site']['redmatrix']['RED_VERSION'];
			$serverdata['network'] = Protocol::ZOT;
		}

		$private = false;
		$inviteonly = false;
		$closed = false;

		if (!empty($data['site']['closed'])) {
			$closed = self::toBoolean($data['site']['closed']);
		}

		if (!empty($data['site']['private'])) {
			$private = self::toBoolean($data['site']['private']);
		}

		if (!empty($data['site']['inviteonly'])) {
			$inviteonly = self::toBoolean($data['site']['inviteonly']);
		}

		if (!$closed && !$private and $inviteonly) {
			$register_policy = Register::APPROVE;
		} elseif (!$closed && !$private) {
			$register_policy = Register::OPEN;
		} else {
			$register_policy = Register::CLOSED;
		}

		return $serverdata;
	}

	private static function toBoolean($val)
	{
		if (($val == 'true') || ($val == 1)) {
			return true;
		} elseif (($val == 'false') || ($val == 0)) {
			return false;
		}

		return $val;
        }

	private static function detectGNUSocial($url, $serverdata)
	{
		$curlResult = Network::curl($url . '/api/statusnet/version.json');

		if ($curlResult->isSuccess() && ($curlResult->getBody() != '{"error":"not implemented"}') &&
			($curlResult->getBody() != '') && (strlen($curlResult->getBody()) < 30)) {
			$serverdata['platform'] = 'StatusNet';
			// Remove junk that some GNU Social servers return
			$serverdata['version'] = str_replace(chr(239).chr(187).chr(191), '', $curlResult->getBody());
			$serverdata['version'] = trim($serverdata['version'], '"');
			$serverdata['network'] = Protocol::OSTATUS;
		}

		// Test for GNU Social
		$curlResult = Network::curl($url . '/api/gnusocial/version.json');

		if ($curlResult->isSuccess() && ($curlResult->getBody() != '{"error":"not implemented"}') &&
			($curlResult->getBody() != '') && (strlen($curlResult->getBody()) < 30)) {
			$serverdata['platform'] = 'GNU Social';
			// Remove junk that some GNU Social servers return
			$serverdata['version'] = str_replace(chr(239) . chr(187) . chr(191), '', $curlResult->getBody());
			$serverdata['version'] = trim($serverdata['version'], '"');
			$serverdata['network'] = Protocol::OSTATUS;
		}

		return $serverdata;
	}

	private static function detectFriendica($url, $serverdata)
	{
		$curlResult = Network::curl($url . '/friendica/json');
		if (!$curlResult->isSuccess()) {
			$curlResult = Network::curl($url . '/friendika/json');
		}

		if (!$curlResult->isSuccess()) {
			return $serverdata;
		}

		$data = json_decode($curlResult->getBody(), true);
		if (empty($data) || empty($data['version'])) {
			return $serverdata;
		}

		$serverdata['network'] = Protocol::DFRN;
		$serverdata['version'] = $data['version'];

		if (!empty($data['no_scrape_url'])) {
			$serverdata['noscrape'] = $data['no_scrape_url'];
		}

		if (!empty($data['site_name'])) {
			$serverdata['site_name'] = $data['site_name'];
		}

		if (!empty($data['info'])) {
			$serverdata['info'] = trim($data['info']);
		}

		$register_policy = defaults($data, 'register_policy', 'REGISTER_CLOSED');
		switch ($register_policy) {
			case 'REGISTER_OPEN':
				$serverdata['register_policy'] = Register::OPEN;
				break;

			case 'REGISTER_APPROVE':
				$serverdata['register_policy'] = Register::APPROVE;
				break;

			case 'REGISTER_CLOSED':
			case 'REGISTER_INVITATION':
				$serverdata['register_policy'] = Register::CLOSED;
				break;
			default:
				Logger::info('Register policy is invalid', ['policy' => $register_policy, 'server' => $url]);
 				$serverdata['register_policy'] = Register::CLOSED;
				break;
		}

		$serverdata['platform'] = defaults($data, 'platform', '');

		return $serverdata;
	}

	private static function analyseRootBody($curlResult, $serverdata)
	{
		$doc = new DOMDocument();
		@$doc->loadHTML($curlResult->getBody());
		$xpath = new DOMXPath($doc);

		$title = trim(XML::getFirstNodeValue($xpath, '//head/title/text()'));
		if (!empty($title)) {
			$serverdata['site_name'] = $title;
		}

		$list = $xpath->query('//meta[@name]');

		foreach ($list as $node) {
			$attr = [];
			if ($node->attributes->length) {
				foreach ($node->attributes as $attribute) {
					$attribute->value = trim($attribute->value);
					if (empty($attribute->value)) {
						continue;
					}

					$attr[$attribute->name] = $attribute->value;
				}

				if (empty($attr['name']) || empty($attr['content'])) {
					continue;
				}
			}
//print_r($attr);
			if ($attr['name'] == 'description') {
				$serverdata['info'] = $attr['content'];
			}

			if ($attr['name'] == 'application-name') {
				$serverdata['platform'] = $attr['content'];
 				if (in_array($attr['content'], ['Misskey', 'Write.as'])) {
					$serverdata['network'] = Protocol::ACTIVITYPUB;
				}
			}

			if ($attr['name'] == 'generator') {
				$serverdata['platform'] = $attr['content'];

				$version_part = explode(' ', $attr['content']);

				if (count($version_part) == 2) {
					if (in_array($version_part[0], ['WordPress'])) {
						$serverdata['platform'] = $version_part[0];
						$serverdata['version'] = $version_part[1];
						$serverdata['network'] = Protocol::ACTIVITYPUB;
					}
					if (in_array($version_part[0], ['Friendika', 'Friendica'])) {
						$serverdata['platform'] = $version_part[0];
						$serverdata['version'] = $version_part[1];
						$serverdata['network'] = Protocol::DFRN;
					}
				}
			}
		}

		$list = $xpath->query('//meta[@property]');

		foreach ($list as $node) {
			$attr = [];
			if ($node->attributes->length) {
				foreach ($node->attributes as $attribute) {
					$attribute->value = trim($attribute->value);
					if (empty($attribute->value)) {
						continue;
					}

					$attr[$attribute->name] = $attribute->value;
				}

				if (empty($attr['property']) || empty($attr['content'])) {
					continue;
				}
			}
//print_r($attr);

			if ($attr['property'] == 'og:site_name') {
				$serverdata['site_name'] = $attr['content'];
			}

			if ($attr['property'] == 'og:description') {
				$serverdata['info'] = $attr['content'];
			}

			if ($attr['property'] == 'og:platform') {
				$serverdata['platform'] = $attr['content'];

				if (in_array($attr['content'], ['PeerTube'])) {
					$serverdata['network'] = Protocol::ACTIVITYPUB;
				}
			}

			if ($attr['property'] == 'generator') {
				$serverdata['platform'] = $attr['content'];

				if (in_array($attr['content'], ['hubzilla'])) {
					// We later check which compatible protocol modules are loaded.
					$serverdata['network'] = Protocol::ZOT;
				}
			}
		}

		return $serverdata;
	}

	private static function analyseRootHeader($curlResult, $serverdata)
	{
		if ($curlResult->getHeader('server') == 'Mastodon') {
			$serverdata['platform'] = 'mastodon';
			$serverdata['network'] = $network = Protocol::ACTIVITYPUB;
		} elseif ($curlResult->inHeader('x-diaspora-version')) {
			$serverdata['platform'] = 'diaspora';
			$serverdata['network'] = $network = Protocol::DIASPORA;
			$serverdata['version'] = $curlResult->getHeader('x-diaspora-version');

		} elseif ($curlResult->inHeader('x-friendica-version')) {
			$serverdata['platform'] = 'friendica';
			$serverdata['network'] = $network = Protocol::DFRN;
			$serverdata['version'] = $curlResult->getHeader('x-friendica-version');

		} else {
//print_r($curlResult->getHeaderArray());
		}
		return $serverdata;
	}
}
