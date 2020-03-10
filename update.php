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
 * Automatic post-databse structure change updates
 *
 * These functions are responsible for doing critical post update changes to the data (not the structure) in the database.
 *
 * Database structure changes are done in static/dbstructure.config.php
 *
 * For non-critical database migrations, please add a method in the Database\PostUpdate class
 *
 * If there is a need for a post update to a structure change, update this file
 * by adding a new function at the end with the number of the new DB_UPDATE_VERSION.
 *
 * The numbered script in this file has to be exactly like the DB_UPDATE_VERSION
 *
 * Example:
 * You are currently on version 4711 and you are preparing changes that demand an update script.
 *
 * 1. Create a function "update_4712()" here in the update.php
 * 2. Apply the needed structural changes in static/dbStructure.php
 * 3. Set DB_UPDATE_VERSION in static/dbstructure.config.php to 4712.
 *
 * If you need to run a script before the database update, name the function "pre_update_4712()"
 */

use Friendica\Core\Addon;
use Friendica\Core\Logger;
use Friendica\Core\Update;
use Friendica\Core\Worker;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Model\GContact;
use Friendica\Model\Item;
use Friendica\Model\User;
use Friendica\Model\Storage;
use Friendica\Util\DateTimeFormat;
use Friendica\Worker\Delivery;

function update_1179()
{
	if (DI::config()->get('system', 'no_community_page')) {
		DI::config()->set('system', 'community_page_style', CP_NO_COMMUNITY_PAGE);
	}

	// Update the central item storage with uid=0
	Worker::add(PRIORITY_LOW, "threadupdate");

	return Update::SUCCESS;
}

function update_1181()
{

	// Fill the new fields in the term table.
	Worker::add(PRIORITY_LOW, "TagUpdate");

	return Update::SUCCESS;
}

function update_1189()
{

	if (strlen(DI::config()->get('system', 'directory_submit_url')) &&
		!strlen(DI::config()->get('system', 'directory'))) {
		DI::config()->set('system', 'directory', dirname(DI::config()->get('system', 'directory_submit_url')));
		DI::config()->delete('system', 'directory_submit_url');
	}

	return Update::SUCCESS;
}

function update_1191()
{
	DI::config()->set('system', 'maintenance', 1);

	if (Addon::isEnabled('forumlist')) {
		Addon::uninstall('forumlist');
	}

	// select old formlist addon entries
	$r = q("SELECT `uid`, `cat`, `k`, `v` FROM `pconfig` WHERE `cat` = '%s' ",
		DBA::escape('forumlist')
	);

	// convert old forumlist addon entries in new config entries
	if (DBA::isResult($r)) {
		foreach ($r as $rr) {
			$uid = $rr['uid'];
			$family = $rr['cat'];
			$key = $rr['k'];
			$value = $rr['v'];

			if ($key === 'randomise') {
				DI::pConfig()->delete($uid, $family, $key);
			}

			if ($key === 'show_on_profile') {
				if ($value) {
					DI::pConfig()->set($uid, 'feature', 'forumlist_profile', $value);
				}

				DI::pConfig()->delete($uid, $family, $key);
			}

			if ($key === 'show_on_network') {
				if ($value) {
					DI::pConfig()->set($uid, 'feature', 'forumlist_widget', $value);
				}

				DI::pConfig()->delete($uid, $family, $key);
			}
		}
	}

	DI::config()->set('system', 'maintenance', 0);

	return Update::SUCCESS;
}

function update_1203()
{
	$r = q("UPDATE `user` SET `account-type` = %d WHERE `page-flags` IN (%d, %d)",
		DBA::escape(User::ACCOUNT_TYPE_COMMUNITY),
		DBA::escape(User::PAGE_FLAGS_COMMUNITY),
		DBA::escape(User::PAGE_FLAGS_PRVGROUP)
	);
}

function update_1244()
{
	// Sets legacy_password for all legacy hashes
	DBA::update('user', ['legacy_password' => true], ['SUBSTR(password, 1, 4) != "$2y$"']);

	// All legacy hashes are re-hashed using the new secure hashing function
	$stmt = DBA::select('user', ['uid', 'password'], ['legacy_password' => true]);
	while ($user = DBA::fetch($stmt)) {
		DBA::update('user', ['password' => User::hashPassword($user['password'])], ['uid' => $user['uid']]);
	}

	// Logged in users are forcibly logged out
	DBA::delete('session', ['1 = 1']);

	return Update::SUCCESS;
}

function update_1245()
{
	$rino = DI::config()->get('system', 'rino_encrypt');

	if (!$rino) {
		return Update::SUCCESS;
	}

	DI::config()->set('system', 'rino_encrypt', 1);

	return Update::SUCCESS;
}

function update_1247()
{
	// Removing hooks with the old name
	DBA::e("DELETE FROM `hook`
WHERE `hook` LIKE 'plugin_%'");

	// Make sure we install the new renamed ones
	Addon::reload();
}

function update_1260()
{
	DI::config()->set('system', 'maintenance', 1);
	DI::config()->set(
		'system',
		'maintenance_reason',
		DI::l10n()->t(
			'%s: Updating author-id and owner-id in item and thread table. ',
			DateTimeFormat::utcNow().' '.date('e')
		)
	);

	$items = DBA::p("SELECT `id`, `owner-link`, `owner-name`, `owner-avatar`, `network` FROM `item`
		WHERE `owner-id` = 0 AND `owner-link` != ''");
	while ($item = DBA::fetch($items)) {
		$contact = ['url' => $item['owner-link'], 'name' => $item['owner-name'],
			'photo' => $item['owner-avatar'], 'network' => $item['network']];
		$cid = Contact::getIdForURL($item['owner-link'], 0, false, $contact);
		if (empty($cid)) {
			continue;
		}
		Item::update(['owner-id' => $cid], ['id' => $item['id']]);
	}
	DBA::close($items);

	DBA::e("UPDATE `thread` INNER JOIN `item` ON `thread`.`iid` = `item`.`id`
		SET `thread`.`owner-id` = `item`.`owner-id` WHERE `thread`.`owner-id` = 0");

	$items = DBA::p("SELECT `id`, `author-link`, `author-name`, `author-avatar`, `network` FROM `item`
		WHERE `author-id` = 0 AND `author-link` != ''");
	while ($item = DBA::fetch($items)) {
		$contact = ['url' => $item['author-link'], 'name' => $item['author-name'],
			'photo' => $item['author-avatar'], 'network' => $item['network']];
		$cid = Contact::getIdForURL($item['author-link'], 0, false, $contact);
		if (empty($cid)) {
			continue;
		}
		Item::update(['author-id' => $cid], ['id' => $item['id']]);
	}
	DBA::close($items);

	DBA::e("UPDATE `thread` INNER JOIN `item` ON `thread`.`iid` = `item`.`id`
		SET `thread`.`author-id` = `item`.`author-id` WHERE `thread`.`author-id` = 0");

	DI::config()->set('system', 'maintenance', 0);
	return Update::SUCCESS;
}

function update_1261()
{
	// This fixes the results of an issue in the develop branch of 2018-05.
	DBA::update('contact', ['blocked' => false, 'pending' => false], ['uid' => 0, 'blocked' => true, 'pending' => true]);
	return Update::SUCCESS;
}

function update_1278()
{
	DI::config()->set('system', 'maintenance', 1);
	DI::config()->set(
		'system',
		'maintenance_reason',
		DI::l10n()->t(
			'%s: Updating post-type.',
			DateTimeFormat::utcNow().' '.date('e')
		)
	);

	Item::update(['post-type' => Item::PT_PAGE], ['bookmark' => true]);
	Item::update(['post-type' => Item::PT_PERSONAL_NOTE], ['type' => 'note']);

	DI::config()->set('system', 'maintenance', 0);

	return Update::SUCCESS;
}

function update_1288()
{
	// Updates missing `uri-id` values

	DBA::e("UPDATE `item-activity` INNER JOIN `item` ON `item`.`iaid` = `item-activity`.`id` SET `item-activity`.`uri-id` = `item`.`uri-id` WHERE `item-activity`.`uri-id` IS NULL OR `item-activity`.`uri-id` = 0");
	DBA::e("UPDATE `item-content` INNER JOIN `item` ON `item`.`icid` = `item-content`.`id` SET `item-content`.`uri-id` = `item`.`uri-id` WHERE `item-content`.`uri-id` IS NULL OR `item-content`.`uri-id` = 0");

	return Update::SUCCESS;
}

// Post-update script of PR 5751
function update_1298()
{
	$keys = ['gender', 'marital', 'sexual'];
	foreach ($keys as $translateKey) {
		$allData = DBA::select('profile', ['id', $translateKey]);
		$allLangs = DI::l10n()->getAvailableLanguages();
		$success = 0;
		$fail = 0;
		foreach ($allData as $key => $data) {
			$toTranslate = $data[$translateKey];
			if ($toTranslate != '') {
				foreach ($allLangs as $key => $lang) {
					$a = new \stdClass();
					$a->strings = [];

					// First we get the the localizations
					if (file_exists("view/lang/$lang/strings.php")) {
						include "view/lang/$lang/strings.php";
					}
					if (file_exists("addon/morechoice/lang/$lang/strings.php")) {
						include "addon/morechoice/lang/$lang/strings.php";
					}

					$localizedStrings = $a->strings;
					unset($a);

					$key = array_search($toTranslate, $localizedStrings);
					if ($key !== false) {
						break;
					}

					// defaulting to empty string
					$key = '';
				}

				if ($key == '') {
					$fail++;
				} else {
					DBA::update('profile', [$translateKey => $key], ['id' => $data['id']]);
					Logger::notice('Updated contact', ['action' => 'update', 'contact' => $data['id'], "$translateKey" => $key,
						'was' => $data[$translateKey]]);
					Worker::add(PRIORITY_LOW, 'ProfileUpdate', $data['id']);
					Contact::updateSelfFromUserID($data['id']);
					GContact::updateForUser($data['id']);
					$success++;
				}
			}
		}

		Logger::notice($translateKey . " fix completed", ['action' => 'update', 'translateKey' => $translateKey, 'Success' => $success, 'Fail' => $fail ]);
	}
	return Update::SUCCESS;
}

function update_1309()
{
	$queue = DBA::select('queue', ['id', 'cid', 'guid']);
	while ($entry = DBA::fetch($queue)) {
		$contact = DBA::selectFirst('contact', ['uid'], ['id' => $entry['cid']]);
		if (!DBA::isResult($contact)) {
			continue;
		}

		$item = Item::selectFirst(['id', 'gravity'], ['uid' => $contact['uid'], 'guid' => $entry['guid']]);
		if (!DBA::isResult($item)) {
			continue;
		}

		$deliver_options = ['priority' => PRIORITY_MEDIUM, 'dont_fork' => true];
		Worker::add($deliver_options, 'Delivery', Delivery::POST, $item['id'], $entry['cid']);
		Logger::info('Added delivery worker', ['item' => $item['id'], 'contact' => $entry['cid']]);
		DBA::delete('queue', ['id' => $entry['id']]);
	}
	return Update::SUCCESS;
}

function update_1315()
{
	DBA::delete('item-delivery-data', ['postopts' => '', 'inform' => '', 'queue_count' => 0, 'queue_done' => 0]);
	return Update::SUCCESS;
}

function update_1318()
{
	DBA::update('profile', ['marital' => "In a relation"], ['marital' => "Unavailable"]);
	DBA::update('profile', ['marital' => "Single"], ['marital' => "Available"]);

	Worker::add(PRIORITY_LOW, 'ProfileUpdate');
	return Update::SUCCESS;
}

function update_1323()
{
	$users = DBA::select('user', ['uid']);
	while ($user = DBA::fetch($users)) {
		Contact::updateSelfFromUserID($user['uid']);
	}
	DBA::close($users);

	return Update::SUCCESS;
}

function update_1327()
{
	$contacts = DBA::select('contact', ['uid', 'id', 'blocked', 'readonly'], ["`uid` != ? AND (`blocked` OR `readonly`) AND NOT `pending`", 0]);
	while ($contact = DBA::fetch($contacts)) {
		Contact::setBlockedForUser($contact['id'], $contact['uid'], $contact['blocked']);
		Contact::setIgnoredForUser($contact['id'], $contact['uid'], $contact['readonly']);
	}
	DBA::close($contacts);

	return Update::SUCCESS;
}

function update_1330()
{
	$currStorage = DI::config()->get('storage', 'class', '');

	// set the name of the storage instead of the classpath as config
	if (!empty($currStorage)) {
		/** @var Storage\IStorage $currStorage */
		if (!DI::config()->set('storage', 'name', $currStorage::getName())) {
			return Update::FAILED;
		}

		// try to delete the class since it isn't needed. This won't work with config files
		DI::config()->delete('storage', 'class');
	}

	// Update attachments and photos
	if (!DBA::p("UPDATE `photo` SET `photo`.`backend-class` = SUBSTR(`photo`.`backend-class`, 25) WHERE `photo`.`backend-class` LIKE 'Friendica\\\Model\\\Storage\\\%' ESCAPE '|'") ||
	    !DBA::p("UPDATE `attach` SET `attach`.`backend-class` = SUBSTR(`attach`.`backend-class`, 25) WHERE `attach`.`backend-class` LIKE 'Friendica\\\Model\\\Storage\\\%' ESCAPE '|'")) {
		return Update::FAILED;
	};

	return Update::SUCCESS;
}

function update_1332()
{
	$condition = ["`is-default` IS NOT NULL"];
	$profiles = DBA::select('profile', [], $condition);

	while ($profile = DBA::fetch($profiles)) {
		DI::profileField()->migrateFromLegacyProfile($profile);
	}
	DBA::close($profiles);

	DBA::update('contact', ['profile-id' => null], ['`profile-id` IS NOT NULL']);

	return Update::SUCCESS;
}
