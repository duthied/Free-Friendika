<?php

namespace Friendica\Core;

use Friendica\BaseObject;
use Friendica\Database\DBA;
use Friendica\Model\Contact;
use Friendica\Network\HTTPException;
use Friendica\Network\Probe;
use Friendica\Object\Search\ContactResult;
use Friendica\Object\Search\ResultList;
use Friendica\Protocol\PortableContact;
use Friendica\Util\Network;
use Friendica\Util\Strings;

/**
 * Specific class to perform searches for different systems. Currently:
 * - Probe for contacts
 * - Search in the local directory
 * - Search in the global directory
 */
class Search extends BaseObject
{
	const DEFAULT_DIRECTORY = 'https://dir.friendica.social';

	const TYPE_PEOPLE = 0;
	const TYPE_FORUM  = 1;
	const TYPE_ALL    = 2;

	/**
	 * Search a user based on his/her profile address
	 * pattern: @username@domain.tld
	 *
	 * @param string $user The user to search for
	 *
	 * @return ResultList|null
	 * @throws HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function getContactsFromProbe($user)
	{
		if ((filter_var($user, FILTER_VALIDATE_EMAIL) && Network::isEmailDomainValid($user)) ||
		    (substr(Strings::normaliseLink($user), 0, 7) == "http://")) {

			$user_data = Probe::uri($user);
			if (empty($user_data)) {
				return null;
			}

			if (!(in_array($user_data["network"], [Protocol::ACTIVITYPUB, Protocol::DFRN, Protocol::OSTATUS, Protocol::DIASPORA]))) {
				return null;
			}

			$contactDetails = Contact::getDetailsByURL(defaults($user_data, 'url', ''), local_user());
			$itemUrl        = (($contactDetails["addr"] != "") ? $contactDetails["addr"] : defaults($user_data, 'url', ''));

			$result = new ContactResult(
				defaults($user_data, 'name', ''),
				defaults($user_data, 'addr', ''),
				$itemUrl,
				defaults($user_data, 'url', ''),
				defaults($user_data, 'photo', ''),
				defaults($user_data, 'network', ''),
				defaults($contactDetails, 'cid', 0),
				0,
				defaults($user_data, 'tags', '')
			);

			return new ResultList(1, 1, 1, [$result]);

		} else {
			return null;
		}
	}

	/**
	 * Search in the global directory for occurrences of the search string
	 *
	 * @see https://github.com/friendica/friendica-directory/blob/master/docs/Protocol.md#search
	 *
	 * @param string $search
	 * @param int    $type specific type of searching
	 * @param int    $page
	 *
	 * @return ResultList|null
	 * @throws HTTPException\InternalServerErrorException
	 */
	public static function getContactsFromGlobalDirectory($search, $type = self::TYPE_ALL, $page = 1)
	{
		$config = self::getApp()->getConfig();
		$server = $config->get('system', 'directory', self::DEFAULT_DIRECTORY);

		$searchUrl = $server . '/search';

		switch ($type) {
			case self::TYPE_FORUM:
				$searchUrl .= '/forum';
				break;
			case self::TYPE_PEOPLE:
				$searchUrl .= '/people';
				break;
		}
		$searchUrl .= '?q=' . urlencode($search);

		if ($page > 1) {
			$searchUrl .= '&page=' . $page;
		}

		$red        = 0;
		$resultJson = Network::fetchUrl($searchUrl, false, $red, 0, 'application/json');

		$results = json_decode($resultJson, true);

		$resultList = new ResultList(
			defaults($results, 'page', 1),
			defaults($results, 'count', 1),
			defaults($results, 'itemsperpage', 1)
		);

		$profiles = defaults($results, 'profiles', []);

		foreach ($profiles as $profile) {
			$contactDetails = Contact::getDetailsByURL(defaults($profile, 'profile_url', ''), local_user());
			$itemUrl        = (!empty($contactDetails['addr']) ?
				$contactDetails['addr'] :
				defaults($profile, 'profile_url', ''));

			$result = new ContactResult(
				defaults($profile, 'name', ''),
				defaults($profile, 'addr', ''),
				$itemUrl,
				defaults($profile, 'profile_url', ''),
				defaults($profile, 'photo', ''),
				Protocol::DFRN,
				defaults($contactDetails, 'cid', 0),
				0,
				defaults($profile, 'tags', ''));

			$resultList->addResult($result);
		}

		return $resultList;
	}

	/**
	 * Search in the local database for occurrences of the search string
	 *
	 * @param string $search
	 * @param int    $type
	 * @param int    $start
	 * @param int    $itemPage
	 *
	 * @return ResultList|null
	 * @throws HTTPException\InternalServerErrorException
	 */
	public static function getContactsFromLocalDirectory($search, $type = self::TYPE_ALL, $start = 0, $itemPage = 80)
	{
		$config = self::getApp()->getConfig();

		$diaspora = $config->get('system', 'diaspora_enabled') ? Protocol::DIASPORA : Protocol::DFRN;
		$ostatus  = !$config->get('system', 'ostatus_disabled') ? Protocol::OSTATUS : Protocol::DFRN;

		$wildcard = Strings::escapeHtml('%' . $search . '%');

		$count = DBA::count('gcontact', [
			'NOT `hide`
			AND `network` IN (?, ?, ?, ?)
			AND ((`last_contact` >= `last_failure`) OR (`updated` >= `last_failure`))
			AND (`url` LIKE ? OR `name` LIKE ? OR `location` LIKE ? 
				OR `addr` LIKE ? OR `about` LIKE ? OR `keywords` LIKE ?)
			AND `community` = ?',
			Protocol::ACTIVITYPUB, Protocol::DFRN, $ostatus, $diaspora,
			$wildcard, $wildcard, $wildcard,
			$wildcard, $wildcard, $wildcard,
			($type === self::TYPE_FORUM),
		]);

		if (empty($count)) {
			return null;
		}

		$data = DBA::select('gcontact', ['nurl'], [
			'NOT `hide`
			AND `network` IN (?, ?, ?, ?)
			AND ((`last_contact` >= `last_failure`) OR (`updated` >= `last_failure`))
			AND (`url` LIKE ? OR `name` LIKE ? OR `location` LIKE ? 
				OR `addr` LIKE ? OR `about` LIKE ? OR `keywords` LIKE ?)
			AND `community` = ?',
			Protocol::ACTIVITYPUB, Protocol::DFRN, $ostatus, $diaspora,
			$wildcard, $wildcard, $wildcard,
			$wildcard, $wildcard, $wildcard,
			($type === self::TYPE_FORUM),
		], [
			'group_by' => ['nurl', 'updated'],
			'limit'    => [$start, $itemPage],
			'order'    => ['updated' => 'DESC']
		]);

		if (!DBA::isResult($data)) {
			return null;
		}

		$resultList = new ResultList($start, $itemPage, $count);

		while ($row = DBA::fetch($data)) {
			if (PortableContact::alternateOStatusUrl($row["nurl"])) {
				continue;
			}

			$urlParts = parse_url($row["nurl"]);

			// Ignore results that look strange.
			// For historic reasons the gcontact table does contain some garbage.
			if (!empty($urlParts['query']) || !empty($urlParts['fragment'])) {
				continue;
			}

			$contact = Contact::getDetailsByURL($row["nurl"], local_user());

			if ($contact["name"] == "") {
				$contact["name"] = end(explode("/", $urlParts["path"]));
			}

			$result = new ContactResult(
				$contact["name"],
				$contact["addr"],
				$contact["addr"],
				$contact["url"],
				$contact["photo"],
				$contact["network"],
				$contact["cid"],
				$contact["zid"],
				$contact["keywords"]
			);

			$resultList->addResult($result);
		}

		DBA::close($data);

		// Add found profiles from the global directory to the local directory
		Worker::add(PRIORITY_LOW, 'DiscoverPoCo', "dirsearch", urlencode($search));

		return $resultList;
	}
}
