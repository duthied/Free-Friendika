<?php

/**
 * @file src/Model/GServer.php
 * This file includes the GServer class to handle with servers
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
use Friendica\Protocol\PortableContact;
use Friendica\Protocol\Diaspora;
use Friendica\Network\Probe;

/**
 * This class handles GServer related functions
 */
class GServer
{
	/**
	 * Checks if the given server is reachable
	 *
	 * @param string  $profile URL of the given profile
	 * @param string  $server  URL of the given server (If empty, taken from profile)
	 * @param string  $network Network value that is used, when detection failed
	 * @param boolean $force   Force an update.
	 *
	 * @return boolean 'true' if server seems vital
	 */
	public static function reachable(string $profile, string $server = '', string $network = '', bool $force = false)
	{
		if ($server == '') {
			$server = Contact::getBasepath($profile);
		}

		if ($server == '') {
			return true;
		}

		return self::check($server, $network, $force);
	}

	/**
	 * Checks the state of the given server.
	 *
	 * @param string  $server_url URL of the given server
	 * @param string  $network    Network value that is used, when detection failed
	 * @param boolean $force      Force an update.
	 *
	 * @return boolean 'true' if server seems vital
	 */
	public static function check(string $server_url, string $network = '', bool $force = false)
	{
		// Unify the server address
		$server_url = trim($server_url, '/');
		$server_url = str_replace('/index.php', '', $server_url);

		if ($server_url == '') {
			return false;
		}

		$gserver = DBA::selectFirst('gserver', [], ['nurl' => Strings::normaliseLink($server_url)]);
		if (DBA::isResult($gserver)) {
			if ($gserver['created'] <= DBA::NULL_DATETIME) {
				$fields = ['created' => DateTimeFormat::utcNow()];
				$condition = ['nurl' => Strings::normaliseLink($server_url)];
				DBA::update('gserver', $fields, $condition);
			}

			$last_contact = $gserver['last_contact'];
			$last_failure = $gserver['last_failure'];

			// See discussion under https://forum.friendi.ca/display/0b6b25a8135aabc37a5a0f5684081633
			// It can happen that a zero date is in the database, but storing it again is forbidden.
			if ($last_contact < DBA::NULL_DATETIME) {
				$last_contact = DBA::NULL_DATETIME;
			}

			if ($last_failure < DBA::NULL_DATETIME) {
				$last_failure = DBA::NULL_DATETIME;
			}

			if (!$force && !PortableContact::updateNeeded($gserver['created'], '', $last_failure, $last_contact)) {
				Logger::info('No update needed', ['server' => $server_url]);
				return ($last_contact >= $last_failure);
			}
			Logger::info('Server is outdated. Start discovery.', ['Server' => $server_url, 'Force' => $force, 'Created' => $gserver['created'], 'Failure' => $last_failure, 'Contact' => $last_contact]);
		} else {
			Logger::info('Server is unknown. Start discovery.', ['Server' => $server_url]);
		}

		return self::detect($server_url, $network);
	}

	/**
	 * Detect server data (type, protocol, version number, ...)
	 * The detected data is then updated or inserted in the gserver table.
	 *
	 * @param string  $url     URL of the given server
	 * @param string  $network Network value that is used, when detection failed
	 *
	 * @return boolean 'true' if server could be detected
	 */
	public static function detect(string $url, string $network = '')
	{
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
		// For Friendica and Zot based networks we have to dive deeper to reveal more details
		if (empty($nodeinfo['network']) || in_array($nodeinfo['network'], [Protocol::DFRN, Protocol::ZOT])) {
			// Fetch the landing page, possibly it reveals some data
			if (empty($nodeinfo['network'])) {
				$curlResult = Network::curl($url, false, ['timeout' => $xrd_timeout]);
				if ($curlResult->isSuccess()) {
					$serverdata = self::analyseRootHeader($curlResult, $serverdata);
					$serverdata = self::analyseRootBody($curlResult, $serverdata, $url);
				}

				if (!$curlResult->isSuccess() || empty($curlResult->getBody())) {
					DBA::update('gserver', ['last_failure' => DateTimeFormat::utcNow()], ['nurl' => Strings::normaliseLink($url)]);
					return false;
				}
			}

			if (empty($serverdata['network']) || ($serverdata['network'] == Protocol::ACTIVITYPUB)) {
				$serverdata = self::detectMastodonAlikes($url, $serverdata);
			}

			// All following checks are done for systems that always have got a "host-meta" endpoint.
			// With this check we don't have to waste time and ressources for dead systems.
			// Also this hopefully prevents us from receiving abuse messages.
			if (empty($serverdata['network']) && !self::validHostMeta($url)) {
				DBA::update('gserver', ['last_failure' => DateTimeFormat::utcNow()], ['nurl' => Strings::normaliseLink($url)]);
				return false;
			}

			if (empty($serverdata['network']) || in_array($serverdata['network'], [Protocol::DFRN, Protocol::ACTIVITYPUB])) {
				$serverdata = self::detectFriendica($url, $serverdata);
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

		$serverdata = self::checkPoCo($url, $serverdata);

		// We can't detect the network type. Possibly it is some system that we don't know yet
		if (empty($serverdata['network'])) {
			$serverdata['network'] = Protocol::PHANTOM;
		}

		// When we hadn't been able to detect the network type, we use the hint from the parameter
		if (($serverdata['network'] == Protocol::PHANTOM) && !empty($network)) {
			$serverdata['network'] = $network;
		}

		$serverdata['url'] = $url;
		$serverdata['nurl'] = Strings::normaliseLink($url);

		// We take the highest number that we do find
		$registeredUsers = $serverdata['registered-users'] ?? 0;

		// On an active server there has to be at least a single user
		if (($serverdata['network'] != Protocol::PHANTOM) && ($registeredUsers == 0)) {
			$registeredUsers = 1;
		}

		if ($serverdata['network'] != Protocol::PHANTOM) {
			$gcontacts = DBA::count('gcontact', ['server_url' => [$url, $serverdata['nurl']]]);
			$apcontacts = DBA::count('apcontact', ['baseurl' => [$url, $serverdata['nurl']]]);
			$contacts = DBA::count('contact', ['uid' => 0, 'baseurl' => [$url, $serverdata['nurl']]]);
			$serverdata['registered-users'] = max($gcontacts, $apcontacts, $contacts, $registeredUsers);
		} else {
			$serverdata['registered-users'] = $registeredUsers;
			$serverdata = self::detectNetworkViaContacts($url, $serverdata);
		}

		$serverdata['last_contact'] = DateTimeFormat::utcNow();

		$gserver = DBA::selectFirst('gserver', ['network'], ['nurl' => Strings::normaliseLink($url)]);
		if (!DBA::isResult($gserver)) {
			$serverdata['created'] = DateTimeFormat::utcNow();
			$ret = DBA::insert('gserver', $serverdata);
		} else {
			// Don't override the network with 'unknown' when there had been a valid entry before
			if (($serverdata['network'] == Protocol::PHANTOM) && !empty($gserver['network'])) {
				unset($serverdata['network']);
			}

			$ret = DBA::update('gserver', $serverdata, ['nurl' => $serverdata['nurl']]);
		}

		if (!empty($serverdata['network']) && in_array($serverdata['network'], [Protocol::DFRN, Protocol::DIASPORA])) {
                        self::discoverRelay($url);
                }

		return $ret;
	}

	/**
	 * Fetch relay data from a given server url
	 *
	 * @param string $server_url address of the server
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	private static function discoverRelay(string $server_url)
	{
		Logger::info('Discover relay data', ['server' => $server_url]);

		$curlResult = Network::curl($server_url . '/.well-known/x-social-relay');
		if (!$curlResult->isSuccess()) {
			return;
		}

		$data = json_decode($curlResult->getBody(), true);
		if (!is_array($data)) {
			return;
		}

		$gserver = DBA::selectFirst('gserver', ['id', 'relay-subscribe', 'relay-scope'], ['nurl' => Strings::normaliseLink($server_url)]);
		if (!DBA::isResult($gserver)) {
			return;
		}

		if (($gserver['relay-subscribe'] != $data['subscribe']) || ($gserver['relay-scope'] != $data['scope'])) {
			$fields = ['relay-subscribe' => $data['subscribe'], 'relay-scope' => $data['scope']];
			DBA::update('gserver', $fields, ['id' => $gserver['id']]);
		}

		DBA::delete('gserver-tag', ['gserver-id' => $gserver['id']]);

		if ($data['scope'] == 'tags') {
			// Avoid duplicates
			$tags = [];
			foreach ($data['tags'] as $tag) {
				$tag = mb_strtolower($tag);
				if (strlen($tag) < 100) {
					$tags[$tag] = $tag;
				}
			}

			foreach ($tags as $tag) {
				DBA::insert('gserver-tag', ['gserver-id' => $gserver['id'], 'tag' => $tag], true);
			}
		}

		// Create or update the relay contact
		$fields = [];
		if (isset($data['protocols'])) {
			if (isset($data['protocols']['diaspora'])) {
				$fields['network'] = Protocol::DIASPORA;

				if (isset($data['protocols']['diaspora']['receive'])) {
					$fields['batch'] = $data['protocols']['diaspora']['receive'];
				} elseif (is_string($data['protocols']['diaspora'])) {
					$fields['batch'] = $data['protocols']['diaspora'];
				}
			}

			if (isset($data['protocols']['dfrn'])) {
				$fields['network'] = Protocol::DFRN;

				if (isset($data['protocols']['dfrn']['receive'])) {
					$fields['batch'] = $data['protocols']['dfrn']['receive'];
				} elseif (is_string($data['protocols']['dfrn'])) {
					$fields['batch'] = $data['protocols']['dfrn'];
				}
			}
		}
		Diaspora::setRelayContact($server_url, $fields);
	}

	/**
	 * Fetch server data from '/statistics.json' on the given server
	 *
	 * @param string $url URL of the given server
	 *
	 * @return array server data
	 */
	private static function fetchStatistics(string $url)
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
	 * Detect server type by using the nodeinfo data
	 *
	 * @param string $url address of the server
	 * @return array Server data
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	private static function fetchNodeinfo(string $url, $curlResult)
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
	 * Parses Nodeinfo 1
	 *
	 * @param string $nodeinfo_url address of the nodeinfo path
	 * @return array Server data
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	private static function parseNodeinfo1(string $nodeinfo_url)
	{
		$curlResult = Network::curl($nodeinfo_url);

		if (!$curlResult->isSuccess()) {
			return [];
		}

		$nodeinfo = json_decode($curlResult->getBody(), true);

		if (!is_array($nodeinfo)) {
			return [];
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

		if (empty($server)) {
			return [];
		}

		return $server;
	}

	/**
	 * Parses Nodeinfo 2
	 *
	 * @param string $nodeinfo_url address of the nodeinfo path
	 * @return array Server data
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	private static function parseNodeinfo2(string $nodeinfo_url)
	{
		$curlResult = Network::curl($nodeinfo_url);
		if (!$curlResult->isSuccess()) {
			return [];
		}

		$nodeinfo = json_decode($curlResult->getBody(), true);

		if (!is_array($nodeinfo)) {
			return [];
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

			if (!empty($protocols['dfrn'])) {
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
			return [];
		}

		return $server;
	}

	/**
	 * Fetch server information from a 'siteinfo.json' file on the given server
	 *
	 * @param string $url        URL of the given server
	 * @param array  $serverdata array with server data
	 *
	 * @return array server data
	 */
	private static function fetchSiteinfo(string $url, array $serverdata)
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

	/**
	 * Checks if the server contains a valid host meta file
	 *
	 * @param string $url URL of the given server
	 *
	 * @return boolean 'true' if the server seems to be vital
	 */
	private static function validHostMeta(string $url)
	{
		$xrd_timeout = Config::get('system', 'xrd_timeout');
		$curlResult = Network::curl($url . '/.well-known/host-meta', false, ['timeout' => $xrd_timeout]);
		if (!$curlResult->isSuccess()) {
			return false;
		}

		$xrd = XML::parseString($curlResult->getBody(), false);
		if (!is_object($xrd)) {
			return false;
		}

		$elements = XML::elementToArray($xrd);
		if (empty($elements) || empty($elements['xrd']) || empty($elements['xrd']['link'])) {
			return false;
		}

		$valid = false;
		foreach ($elements['xrd']['link'] as $link) {
			// When there is more than a single "link" element, the array looks slightly different
			if (!empty($link['@attributes'])) {
				$link = $link['@attributes'];
			}

			if (empty($link['rel']) || empty($link['template'])) {
				continue;
			}

			if ($link['rel'] == 'lrdd') {
				// When the webfinger host is the same like the system host, it should be ok.
				$valid = (parse_url($url, PHP_URL_HOST) == parse_url($link['template'], PHP_URL_HOST));
			}
		}

		return $valid;
	}

	/**
	 * Detect the network of the given server via their known contacts
	 *
	 * @param string $url        URL of the given server
	 * @param array  $serverdata array with server data
	 *
	 * @return array server data
	 */
	private static function detectNetworkViaContacts(string $url, array $serverdata)
	{
		$contacts = [];

		$gcontacts = DBA::select('gcontact', ['url', 'nurl'], ['server_url' => [$url, $serverdata['nurl']]]);
		while ($gcontact = DBA::fetch($gcontacts)) {
			$contacts[$gcontact['nurl']] = $gcontact['url'];
		}
		DBA::close($gcontacts);

		$apcontacts = DBA::select('apcontact', ['url'], ['baseurl' => [$url, $serverdata['nurl']]]);
		while ($gcontact = DBA::fetch($gcontacts)) {
			$contacts[Strings::normaliseLink($apcontact['url'])] = $apcontact['url'];
		}
		DBA::close($apcontacts);

		$pcontacts = DBA::select('contact', ['url', 'nurl'], ['uid' => 0, 'baseurl' => [$url, $serverdata['nurl']]]);
		while ($gcontact = DBA::fetch($gcontacts)) {
			$contacts[$pcontact['nurl']] = $pcontact['url'];
		}
		DBA::close($pcontacts);

		if (empty($contacts)) {
			return $serverdata;
		}

		foreach ($contacts as $contact) {
			$probed = Probe::uri($contact);
			if (in_array($probed['network'], Protocol::FEDERATED)) {
				$serverdata['network'] = $probed['network'];
				break;
			}
		}

		$serverdata['registered-users'] = max($serverdata['registered-users'], count($contacts));

		return $serverdata;
	}

	/**
	 * Checks if the given server does have a '/poco' endpoint.
	 * This is used for the 'PortableContact' functionality,
	 * which is used by both Friendica and Hubzilla.
	 *
	 * @param string $url        URL of the given server
	 * @param array  $serverdata array with server data
	 *
	 * @return array server data
	 */
	private static function checkPoCo(string $url, array $serverdata)
	{
		$curlResult = Network::curl($url. '/poco');
		if (!$curlResult->isSuccess()) {
			return $serverdata;
		}

		$data = json_decode($curlResult->getBody(), true);
		if (empty($data)) {
			return $serverdata;
		}

		if (!empty($data['totalResults'])) {
			$registeredUsers = $serverdata['registered-users'] ?? 0;
			$serverdata['registered-users'] = max($data['totalResults'], $registeredUsers);
			$serverdata['poco'] = $url . '/poco';
		} else {
			$serverdata['poco'] = '';
		}

		return $serverdata;
	}

	/**
	 * Detects the version number of a given server when it was a NextCloud installation
	 *
	 * @param string $url        URL of the given server
	 * @param array  $serverdata array with server data
	 *
	 * @return array server data
	 */
	private static function detectNextcloud(string $url, array $serverdata)
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

	/**
	 * Detects data from a given server url if it was a mastodon alike system
	 *
	 * @param string $url        URL of the given server
	 * @param array  $serverdata array with server data
	 *
	 * @return array server data
	 */
	private static function detectMastodonAlikes(string $url, array $serverdata)
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
			$serverdata['version'] = $data['version'] ?? '';
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

		if (!empty($serverdata['version']) && preg_match('/.*?\(compatible;\s(.*)\s(.*)\)/ism', $serverdata['version'], $matches)) {
			$serverdata['platform'] = $matches[1];
			$serverdata['version'] = $matches[2];
		}

		if (!empty($serverdata['version']) && strstr($serverdata['version'], 'Pleroma')) {
			$serverdata['platform'] = 'pleroma';
			$serverdata['version'] = trim(str_replace('Pleroma', '', $serverdata['version']));
		}

		return $serverdata;
	}

	/**
	 * Detects data from typical Hubzilla endpoints
	 *
	 * @param string $url        URL of the given server
	 * @param array  $serverdata array with server data
	 *
	 * @return array server data
	 */
	private static function detectHubzilla(string $url, array $serverdata)
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

	/**
	 * Converts input value to a boolean value
	 *
	 * @param string|integer $val
	 *
	 * @return boolean
	 */
	private static function toBoolean($val)
	{
		if (($val == 'true') || ($val == 1)) {
			return true;
		} elseif (($val == 'false') || ($val == 0)) {
			return false;
		}

		return $val;
        }

	/**
	 * Detect if the URL belongs to a GNU Social server
	 *
	 * @param string $url        URL of the given server
	 * @param array  $serverdata array with server data
	 *
	 * @return array server data
	 */
	private static function detectGNUSocial(string $url, array $serverdata)
	{
		// Test for GNU Social
		$curlResult = Network::curl($url . '/api/gnusocial/version.json');
		if ($curlResult->isSuccess() && ($curlResult->getBody() != '{"error":"not implemented"}') &&
			($curlResult->getBody() != '') && (strlen($curlResult->getBody()) < 30)) {
			$serverdata['platform'] = 'gnusocial';
			// Remove junk that some GNU Social servers return
			$serverdata['version'] = str_replace(chr(239) . chr(187) . chr(191), '', $curlResult->getBody());
			$serverdata['version'] = trim($serverdata['version'], '"');
			$serverdata['network'] = Protocol::OSTATUS;
			return $serverdata;
		}

		// Test for Statusnet
		$curlResult = Network::curl($url . '/api/statusnet/version.json');
		if ($curlResult->isSuccess() && ($curlResult->getBody() != '{"error":"not implemented"}') &&
			($curlResult->getBody() != '') && (strlen($curlResult->getBody()) < 30)) {
			$serverdata['platform'] = 'statusnet';
			// Remove junk that some GNU Social servers return
			$serverdata['version'] = str_replace(chr(239).chr(187).chr(191), '', $curlResult->getBody());
			$serverdata['version'] = trim($serverdata['version'], '"');
			$serverdata['network'] = Protocol::OSTATUS;
		}

		return $serverdata;
	}

	/**
	 * Detect if the URL belongs to a Friendica server
	 *
	 * @param string $url        URL of the given server
	 * @param array  $serverdata array with server data
	 *
	 * @return array server data
	 */
	private static function detectFriendica(string $url, array $serverdata)
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

		$register_policy = ($data['register_policy'] ?? '') ?: 'REGISTER_CLOSED';
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

		$serverdata['platform'] = $data['platform'] ?? '';

		return $serverdata;
	}

	/**
	 * Analyses the landing page of a given server for hints about type and system of that server
	 *
	 * @param object $curlResult result of curl execution
	 * @param array  $serverdata array with server data
	 * @param string $url        Server URL
	 *
	 * @return array server data
	 */
	private static function analyseRootBody($curlResult, array $serverdata, string $url)
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
					$value = trim($attribute->value);
					if (empty($value)) {
						continue;
					}

					$attr[$attribute->name] = $value;
				}

				if (empty($attr['name']) || empty($attr['content'])) {
					continue;
				}
			}

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

						// We still do need a reliable test if some AP plugin is activated
						if (DBA::exists('apcontact', ['baseurl' => $url])) {
							$serverdata['network'] = Protocol::ACTIVITYPUB;
						} else {
							$serverdata['network'] = Protocol::FEED;
						}
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
					$value = trim($attribute->value);
					if (empty($value)) {
						continue;
					}

					$attr[$attribute->name] = $value;
				}

				if (empty($attr['property']) || empty($attr['content'])) {
					continue;
				}
			}

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

	/**
	 * Analyses the header data of a given server for hints about type and system of that server
	 *
	 * @param object $curlResult result of curl execution
	 * @param array  $serverdata array with server data
	 *
	 * @return array server data
	 */
	private static function analyseRootHeader($curlResult, array $serverdata)
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
		}
		return $serverdata;
	}
}
