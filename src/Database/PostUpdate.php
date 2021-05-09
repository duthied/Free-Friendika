<?php
/**
 * @copyright Copyright (C) 2010-2021, the Friendica project
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

namespace Friendica\Database;

use Friendica\Core\Logger;
use Friendica\Core\Protocol;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Model\GServer;
use Friendica\Model\Item;
use Friendica\Model\ItemURI;
use Friendica\Model\Photo;
use Friendica\Model\Post;
use Friendica\Model\Post\Category;
use Friendica\Model\Tag;
use Friendica\Model\Verb;
use Friendica\Util\Strings;

/**
 * These database-intensive post update routines are meant to be executed in the background by the cronjob.
 *
 * If there is a need for a intensive migration after a database structure change, update this file
 * by adding a new method at the end with the number of the new DB_UPDATE_VERSION.
 */
class PostUpdate
{
	// Needed for the helper function to read from the legacy term table
	const OBJECT_TYPE_POST  = 1;
	const VERSION = 1400;

	/**
	 * Calls the post update functions
	 */
	public static function update()
	{
		if (!self::update1297()) {
			return false;
		}
		if (!self::update1322()) {
			return false;
		}
		if (!self::update1329()) {
			return false;
		}
		if (!self::update1341()) {
			return false;
		}
		if (!self::update1342()) {
			return false;
		}
		if (!self::update1345()) {
			return false;
		}
		if (!self::update1346()) {
			return false;
		}
		if (!self::update1347()) {
			return false;
		}
		if (!self::update1348()) {
			return false;
		}
		if (!self::update1349()) {
			return false;
		}
		if (!self::update1383()) {
			return false;
		}
		if (!self::update1384()) {
			return false;
		}
		if (!self::update1400()) {
			return false;
		}
		return true;
	}

	/**
	 * Set the delivery queue count to a negative value for all items preceding the feature.
	 *
	 * @return bool "true" when the job is done
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	private static function update1297()
	{
		// Was the script completed?
		if (DI::config()->get('system', 'post_update_version') >= 1297) {
			return true;
		}

		if (!DBStructure::existsTable('item-delivery-data')) {
			DI::config()->set('system', 'post_update_version', 1297);
			return true;
		}

		$max_item_delivery_data = DBA::selectFirst('item-delivery-data', ['iid'], ['queue_count > 0 OR queue_done > 0'], ['order' => ['iid']]);
		$max_iid = $max_item_delivery_data['iid'];

		Logger::info('Start update1297 with max iid: ' . $max_iid);

		$condition = ['`queue_count` = 0 AND `iid` < ?', $max_iid];

		DBA::update('item-delivery-data', ['queue_count' => -1], $condition);

		if (DBA::errorNo() != 0) {
			Logger::error('Database error ' . DBA::errorNo() . ':' . DBA::errorMessage());
			return false;
		}

		Logger::info('Processed rows: ' . DBA::affectedRows());

		DI::config()->set('system', 'post_update_version', 1297);

		Logger::info('Done');

		return true;
	}
	/**
	 * Remove contact duplicates
	 *
	 * @return bool "true" when the job is done
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	private static function update1322()
	{
		// Was the script completed?
		if (DI::config()->get('system', 'post_update_version') >= 1322) {
			return true;
		}

		Logger::info('Start');

		$contacts = DBA::p("SELECT `nurl`, `uid` FROM `contact`
			WHERE EXISTS (SELECT `nurl` FROM `contact` AS `c2`
				WHERE `c2`.`nurl` = `contact`.`nurl` AND `c2`.`id` != `contact`.`id` AND `c2`.`uid` = `contact`.`uid` AND `c2`.`network` IN (?, ?, ?) AND NOT `deleted`)
			AND (`network` IN (?, ?, ?) OR (`uid` = ?)) AND NOT `deleted` GROUP BY `nurl`, `uid`",
			Protocol::DIASPORA, Protocol::OSTATUS, Protocol::ACTIVITYPUB,
			Protocol::DIASPORA, Protocol::OSTATUS, Protocol::ACTIVITYPUB, 0);

		while ($contact = DBA::fetch($contacts)) {
			Logger::info('Remove duplicates', ['nurl' => $contact['nurl'], 'uid' => $contact['uid']]);
			Contact::removeDuplicates($contact['nurl'], $contact['uid']);
		}

		DBA::close($contact);
		DI::config()->set('system', 'post_update_version', 1322);

		Logger::info('Done');

		return true;
	}

	/**
	 * update user notification data
	 *
	 * @return bool "true" when the job is done
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	private static function update1329()
	{
		// Was the script completed?
		if (DI::config()->get('system', 'post_update_version') >= 1329) {
			return true;
		}

		if (!DBStructure::existsTable('item')) {
			DI::config()->set('system', 'post_update_version', 1329);
			return true;
		}

		$id = DI::config()->get('system', 'post_update_version_1329_id', 0);

		Logger::info('Start', ['item' => $id]);

		$start_id = $id;
		$rows = 0;
		$condition = ["`id` > ?", $id];
		$params = ['order' => ['id'], 'limit' => 10000];
		$items = DBA::select('item', ['id', 'uri-id', 'uid'], $condition, $params);

		if (DBA::errorNo() != 0) {
			Logger::error('Database error', ['no' => DBA::errorNo(), 'message' => DBA::errorMessage()]);
			return false;
		}

		while ($item = DBA::fetch($items)) {
			$id = $item['id'];

			Post\UserNotification::setNotification($item['uri-id'], $item['uid']);

			++$rows;
		}
		DBA::close($items);

		DI::config()->set('system', 'post_update_version_1329_id', $id);

		Logger::info('Processed', ['rows' => $rows, 'last' => $id]);

		if ($start_id == $id) {
			DI::config()->set('system', 'post_update_version', 1329);
			Logger::info('Done');
			return true;
		}

		return false;
	}

	/**
	 * Fill the "tag" table with tags and mentions from the body
	 *
	 * @return bool "true" when the job is done
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	private static function update1341()
	{
		// Was the script completed?
		if (DI::config()->get('system', 'post_update_version') >= 1341) {
			return true;
		}

		if (!DBStructure::existsTable('item-content')) {
			DI::config()->set('system', 'post_update_version', 1342);
			return true;
		}

		$id = DI::config()->get('system', 'post_update_version_1341_id', 0);

		Logger::info('Start', ['item' => $id]);

		$rows = 0;

		$items = DBA::p("SELECT `uri-id`,`body` FROM `item-content` WHERE
			(`body` LIKE ? OR `body` LIKE ? OR `body` LIKE ?) AND `uri-id` >= ?
			ORDER BY `uri-id` LIMIT 100000", '%#%', '%@%', '%!%', $id);

		if (DBA::errorNo() != 0) {
			Logger::error('Database error', ['no' => DBA::errorNo(), 'message' => DBA::errorMessage()]);
			return false;
		}

		while ($item = DBA::fetch($items)) {
			Tag::storeFromBody($item['uri-id'], $item['body'], '#!@', false);
			$id = $item['uri-id'];
			++$rows;
			if ($rows % 1000 == 0) {
				DI::config()->set('system', 'post_update_version_1341_id', $id);
			}
		}
		DBA::close($items);

		DI::config()->set('system', 'post_update_version_1341_id', $id);

		Logger::info('Processed', ['rows' => $rows, 'last' => $id]);

		// When there are less than 1,000 items processed this means that we reached the end
		// The other entries will then be processed with the regular functionality
		if ($rows < 1000) {
			DI::config()->set('system', 'post_update_version', 1341);
			Logger::info('Done');
			return true;
		}

		return false;
	}

	/**
	 * Fill the "tag" table with tags and mentions from the "term" table
	 *
	 * @return bool "true" when the job is done
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	private static function update1342()
	{
		// Was the script completed?
		if (DI::config()->get('system', 'post_update_version') >= 1342) {
			return true;
		}

		if (!DBStructure::existsTable('term') || !DBStructure::existsTable('item-content')) {
			DI::config()->set('system', 'post_update_version', 1342);
			return true;
		}

		$id = DI::config()->get('system', 'post_update_version_1342_id', 0);

		Logger::info('Start', ['item' => $id]);

		$rows = 0;

		$terms = DBA::p("SELECT `term`.`tid`, `item`.`uri-id`, `term`.`type`, `term`.`term`, `term`.`url`, `item-content`.`body`
			FROM `term`
			INNER JOIN `item` ON `item`.`id` = `term`.`oid`
			INNER JOIN `item-content` ON `item-content`.`uri-id` = `item`.`uri-id`
			WHERE term.type IN (?, ?, ?, ?) AND `tid` >= ? ORDER BY `tid` LIMIT 100000",
			Tag::HASHTAG, Tag::MENTION, Tag::EXCLUSIVE_MENTION, Tag::IMPLICIT_MENTION, $id);

		if (DBA::errorNo() != 0) {
			Logger::error('Database error', ['no' => DBA::errorNo(), 'message' => DBA::errorMessage()]);
			return false;
		}

		while ($term = DBA::fetch($terms)) {
			if (($term['type'] == Tag::MENTION) && !empty($term['url']) && !strstr($term['body'], $term['url'])) {
                $condition = ['nurl' => Strings::normaliseLink($term['url']), 'uid' => 0, 'deleted' => false];
                $contact = DBA::selectFirst('contact', ['url', 'alias'], $condition, ['order' => ['id']]);
                if (!DBA::isResult($contact)) {
                        $ssl_url = str_replace('http://', 'https://', $term['url']);
                        $condition = ['`alias` IN (?, ?, ?) AND `uid` = ? AND NOT `deleted`', $term['url'], Strings::normaliseLink($term['url']), $ssl_url, 0];
                        $contact = DBA::selectFirst('contact', ['url', 'alias'], $condition, ['order' => ['id']]);
                }

                if (DBA::isResult($contact) && (!strstr($term['body'], $contact['url']) && (empty($contact['alias']) || !strstr($term['body'], $contact['alias'])))) {
                        $term['type'] = Tag::IMPLICIT_MENTION;
                }
			}

			Tag::store($term['uri-id'], $term['type'], $term['term'], $term['url'], false);

			$id = $term['tid'];
			++$rows;
			if ($rows % 1000 == 0) {
				DI::config()->set('system', 'post_update_version_1342_id', $id);
			}
		}
		DBA::close($terms);

		DI::config()->set('system', 'post_update_version_1342_id', $id);

		Logger::info('Processed', ['rows' => $rows, 'last' => $id]);

		// When there are less than 1,000 items processed this means that we reached the end
		// The other entries will then be processed with the regular functionality
		if ($rows < 1000) {
			DI::config()->set('system', 'post_update_version', 1342);
			Logger::info('Done');
			return true;
		}

		return false;
	}

	/**
	 * Fill the "post-delivery-data" table with data from the "item-delivery-data" table
	 *
	 * @return bool "true" when the job is done
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	private static function update1345()
	{
		// Was the script completed?
		if (DI::config()->get('system', 'post_update_version') >= 1345) {
			return true;
		}

		if (!DBStructure::existsTable('item-delivery-data')) {
			DI::config()->set('system', 'post_update_version', 1345);
			return true;
		}

		$id = DI::config()->get('system', 'post_update_version_1345_id', 0);

		Logger::info('Start', ['item' => $id]);

		$rows = 0;

		$deliveries = DBA::p("SELECT `uri-id`, `iid`, `item-delivery-data`.`postopts`, `item-delivery-data`.`inform`,
			`queue_count`, `queue_done`, `activitypub`, `dfrn`, `diaspora`, `ostatus`, `legacy_dfrn`, `queue_failed`
			FROM `item-delivery-data`
			INNER JOIN `item` ON `item`.`id` = `item-delivery-data`.`iid`
			WHERE `iid` >= ? ORDER BY `iid` LIMIT 10000", $id);

		if (DBA::errorNo() != 0) {
			Logger::error('Database error', ['no' => DBA::errorNo(), 'message' => DBA::errorMessage()]);
			return false;
		}

		while ($delivery = DBA::fetch($deliveries)) {
			$id = $delivery['iid'];
			unset($delivery['iid']);
			DBA::insert('post-delivery-data', $delivery, Database::INSERT_UPDATE);
			++$rows;
		}
		DBA::close($deliveries);

		DI::config()->set('system', 'post_update_version_1345_id', $id);

		Logger::info('Processed', ['rows' => $rows, 'last' => $id]);

		// When there are less than 100 items processed this means that we reached the end
		// The other entries will then be processed with the regular functionality
		if ($rows < 100) {
			DI::config()->set('system', 'post_update_version', 1345);
			Logger::info('Done');
			return true;
		}

		return false;
	}

	/**
	 * Generates the legacy item.file field string from an item ID.
	 * Includes only file and category terms.
	 *
	 * @param int $item_id
	 * @return string
	 * @throws \Exception
	 */
	private static function fileTextFromItemId($item_id)
	{
		$file_text = '';

		$condition = ['otype' => self::OBJECT_TYPE_POST, 'oid' => $item_id, 'type' => [Category::FILE, Category::CATEGORY]];
		$tags = DBA::selectToArray('term', ['type', 'term', 'url'], $condition);
		foreach ($tags as $tag) {
			if ($tag['type'] == Category::CATEGORY) {
				$file_text .= '<' . $tag['term'] . '>';
			} else {
				$file_text .= '[' . $tag['term'] . ']';
			}
		}

		return $file_text;
	}

	/**
	 * Fill the "tag" table with tags and mentions from the "term" table
	 *
	 * @return bool "true" when the job is done
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	private static function update1346()
	{
		// Was the script completed?
		if (DI::config()->get('system', 'post_update_version') >= 1346) {
			return true;
		}

		if (!DBStructure::existsTable('term')) {
			DI::config()->set('system', 'post_update_version', 1346);
			return true;
		}

		$id = DI::config()->get('system', 'post_update_version_1346_id', 0);

		Logger::info('Start', ['item' => $id]);

		$rows = 0;

		$terms = DBA::select('term', ['oid'],
			["`type` IN (?, ?) AND `oid` >= ?", Category::CATEGORY, Category::FILE, $id],
			['order' => ['oid'], 'limit' => 1000, 'group_by' => ['oid']]);

		if (DBA::errorNo() != 0) {
			Logger::error('Database error', ['no' => DBA::errorNo(), 'message' => DBA::errorMessage()]);
			return false;
		}

		while ($term = DBA::fetch($terms)) {
			$item = Post::selectFirst(['uri-id', 'uid'], ['id' => $term['oid']]);
			if (!DBA::isResult($item)) {
				continue;
			}

			$file = self::fileTextFromItemId($term['oid']);
			if (!empty($file)) {
				Category::storeTextByURIId($item['uri-id'], $item['uid'], $file);
			}

			$id = $term['oid'];
			++$rows;
			if ($rows % 100 == 0) {
				DI::config()->set('system', 'post_update_version_1346_id', $id);
			}
		}
		DBA::close($terms);

		DI::config()->set('system', 'post_update_version_1346_id', $id);

		Logger::info('Processed', ['rows' => $rows, 'last' => $id]);

		// When there are less than 10 items processed this means that we reached the end
		// The other entries will then be processed with the regular functionality
		if ($rows < 10) {
			DI::config()->set('system', 'post_update_version', 1346);
			Logger::info('Done');
			return true;
		}

		return false;
	}

	/**
	 * update the "vid" (verb) field in the item table
	 *
	 * @return bool "true" when the job is done
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	private static function update1347()
	{
		// Was the script completed?
		if (DI::config()->get("system", "post_update_version") >= 1347) {
			return true;
		}

		if (!DBStructure::existsTable('item-activity') || !DBStructure::existsTable('item')) {
			DI::config()->set('system', 'post_update_version', 1347);
			return true;
		}

		$id = DI::config()->get("system", "post_update_version_1347_id", 0);

		Logger::info('Start', ['item' => $id]);

		$start_id = $id;
		$rows = 0;

		$items = DBA::p("SELECT `item`.`id`, `item`.`verb` AS `item-verb`, `item-content`.`verb`, `item-activity`.`activity`
			FROM `item` LEFT JOIN `item-content` ON `item-content`.`uri-id` = `item`.`uri-id`
			LEFT JOIN `item-activity` ON `item-activity`.`uri-id` = `item`.`uri-id` AND `item`.`gravity` = ?
			WHERE `item`.`id` >= ? AND `item`.`vid` IS NULL ORDER BY `item`.`id` LIMIT 10000", GRAVITY_ACTIVITY, $id);

		if (DBA::errorNo() != 0) {
			Logger::error('Database error', ['no' => DBA::errorNo(), 'message' => DBA::errorMessage()]);
			return false;
		}

		while ($item = DBA::fetch($items)) {
			$id = $item['id'];
			$verb = $item['item-verb'];
			if (empty($verb)) {
				$verb = $item['verb'];
			}
			if (empty($verb) && is_int($item['activity'])) {
				$verb = Item::ACTIVITIES[$item['activity']];
			}
			if (empty($verb)) {
				continue;
			}

			DBA::update('item', ['vid' => Verb::getID($verb)], ['id' => $item['id']]);
			++$rows;
		}
		DBA::close($items);

		DI::config()->set("system", "post_update_version_1347_id", $id);

		Logger::info('Processed', ['rows' => $rows, 'last' => $id]);

		if ($start_id == $id) {
			DI::config()->set("system", "post_update_version", 1347);
			Logger::info('Done');
			return true;
		}

		return false;
	}

	/**
	 * update the "gsid" (global server id) field in the contact table
	 *
	 * @return bool "true" when the job is done
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	private static function update1348()
	{
		// Was the script completed?
		if (DI::config()->get("system", "post_update_version") >= 1348) {
			return true;
		}

		$id = DI::config()->get("system", "post_update_version_1348_id", 0);

		Logger::info('Start', ['contact' => $id]);

		$start_id = $id;
		$rows = 0;
		$condition = ["`id` > ? AND `gsid` IS NULL AND `baseurl` != '' AND NOT `baseurl` IS NULL", $id];
		$params = ['order' => ['id'], 'limit' => 10000];
		$contacts = DBA::select('contact', ['id', 'baseurl'], $condition, $params);

		if (DBA::errorNo() != 0) {
			Logger::error('Database error', ['no' => DBA::errorNo(), 'message' => DBA::errorMessage()]);
			return false;
		}

		while ($contact = DBA::fetch($contacts)) {
			$id = $contact['id'];

			DBA::update('contact',
				['gsid' => GServer::getID($contact['baseurl'], true), 'baseurl' => GServer::cleanURL($contact['baseurl'])],
				['id' => $contact['id']]);

			++$rows;
		}
		DBA::close($contacts);

		DI::config()->set("system", "post_update_version_1348_id", $id);

		Logger::info('Processed', ['rows' => $rows, 'last' => $id]);

		if ($start_id == $id) {
			DI::config()->set("system", "post_update_version", 1348);
			Logger::info('Done');
			return true;
		}

		return false;
	}

	/**
	 * update the "gsid" (global server id) field in the apcontact table
	 *
	 * @return bool "true" when the job is done
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	private static function update1349()
	{
		// Was the script completed?
		if (DI::config()->get("system", "post_update_version") >= 1349) {
			return true;
		}

		$id = DI::config()->get("system", "post_update_version_1349_id", '');

		Logger::info('Start', ['apcontact' => $id]);

		$start_id = $id;
		$rows = 0;
		$condition = ["`url` > ? AND `gsid` IS NULL AND `baseurl` != '' AND NOT `baseurl` IS NULL", $id];
		$params = ['order' => ['url'], 'limit' => 10000];
		$apcontacts = DBA::select('apcontact', ['url', 'baseurl'], $condition, $params);

		if (DBA::errorNo() != 0) {
			Logger::error('Database error', ['no' => DBA::errorNo(), 'message' => DBA::errorMessage()]);
			return false;
		}

		while ($apcontact = DBA::fetch($apcontacts)) {
			$id = $apcontact['url'];

			DBA::update('apcontact',
				['gsid' => GServer::getID($apcontact['baseurl'], true), 'baseurl' => GServer::cleanURL($apcontact['baseurl'])],
				['url' => $apcontact['url']]);

			++$rows;
		}
		DBA::close($apcontacts);

		DI::config()->set("system", "post_update_version_1349_id", $id);

		Logger::info('Processed', ['rows' => $rows, 'last' => $id]);

		if ($start_id == $id) {
			DI::config()->set("system", "post_update_version", 1349);
			Logger::info('Done');
			return true;
		}

		return false;
	}

	/**
	 * Remove orphaned photo entries
	 *
	 * @return bool "true" when the job is done
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	private static function update1383()
	{
		// Was the script completed?
		if (DI::config()->get("system", "post_update_version") >= 1383) {
			return true;
		}

		Logger::info('Start');

		$deleted = 0;
		$avatar = [4 => 'photo', 5 => 'thumb', 6 => 'micro'];

		$photos = DBA::select('photo', ['id', 'contact-id', 'resource-id', 'scale'], ["`contact-id` != ? AND `album` = ?", 0, Photo::CONTACT_PHOTOS]);
		while ($photo = DBA::fetch($photos)) {
			$delete = !in_array($photo['scale'], [4, 5, 6]);

			if (!$delete) {
				// Check if there is a contact entry with that photo
				$delete = !DBA::exists('contact', ["`id` = ? AND `" . $avatar[$photo['scale']] . "` LIKE ?",
					$photo['contact-id'], '%' . $photo['resource-id'] . '%']);
			}

			if ($delete) {
				Photo::delete(['id' => $photo['id']]);
				$deleted++;
			}
		}
		DBA::close($photos);

		DI::config()->set("system", "post_update_version", 1383);
		Logger::info('Done', ['deleted' => $deleted]);
		return true;
	}

	/**
	 * update the "hash" field in the photo table
	 *
	 * @return bool "true" when the job is done
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	private static function update1384()
	{
		// Was the script completed?
		if (DI::config()->get("system", "post_update_version") >= 1384) {
			return true;
		}

		$condition = ["`hash` IS NULL"];
		Logger::info('Start', ['rest' => DBA::count('photo', $condition)]);

		$rows = 0;
		$photos = DBA::select('photo', [], $condition, ['limit' => 10000]);

		if (DBA::errorNo() != 0) {
			Logger::error('Database error', ['no' => DBA::errorNo(), 'message' => DBA::errorMessage()]);
			return false;
		}

		while ($photo = DBA::fetch($photos)) {
			$img = Photo::getImageForPhoto($photo);
			if (!empty($img)) {
				$md5 = md5($img->asString());
			} else {
				$md5 = '';
			}
			DBA::update('photo', ['hash' => $md5], ['id' => $photo['id']]);
			++$rows;
		}
		DBA::close($photos);

		Logger::info('Processed', ['rows' => $rows]);

		if ($rows <= 100) {
			DI::config()->set("system", "post_update_version", 1384);
			Logger::info('Done');
			return true;
		}

		return false;
	}

	/**
	 * update the "hash" field in the photo table
	 *
	 * @return bool "true" when the job is done
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	private static function update1400()
	{
		// Was the script completed?
		if (DI::config()->get("system", "post_update_version") >= 1400) {
			return true;
		}

		if (!DBStructure::existsTable('item')) {
			DI::config()->set("system", "post_update_version", 1400);
			return true;
		}

		$condition = ["`extid` != ? AND EXISTS(SELECT `id` FROM `post-user` WHERE `uri-id` = `item`.`uri-id` AND `uid` = `item`.`uid` AND `external-id` IS NULL)", ''];
		Logger::info('Start', ['rest' => DBA::count('item', $condition)]);

		$rows = 0;
		$items = DBA::select('item', ['uri-id', 'uid', 'extid'], $condition, ['order' => ['id'], 'limit' => 10000]);

		if (DBA::errorNo() != 0) {
			Logger::error('Database error', ['no' => DBA::errorNo(), 'message' => DBA::errorMessage()]);
			return false;
		}

		while ($item = DBA::fetch($items)) {
			Post::update(['external-id' => ItemURI::getIdByURI($item['extid'])], ['uri-id' => $item['uri-id'], 'uid' => $item['uid']]);
			++$rows;
		}
		DBA::close($items);

		Logger::info('Processed', ['rows' => $rows]);

		if ($rows <= 100) {
			DI::config()->set("system", "post_update_version", 1400);
			Logger::info('Done');
			return true;
		}

		return false;
	}
}
