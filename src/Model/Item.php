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
 */

namespace Friendica\Model;

use Friendica\Content\Text\BBCode;
use Friendica\Content\Text\HTML;
use Friendica\Core\Hook;
use Friendica\Core\Logger;
use Friendica\Core\Protocol;
use Friendica\Core\Renderer;
use Friendica\Core\Session;
use Friendica\Core\System;
use Friendica\Core\Worker;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Post\Category;
use Friendica\Protocol\Activity;
use Friendica\Protocol\ActivityPub;
use Friendica\Protocol\Diaspora;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Map;
use Friendica\Util\Network;
use Friendica\Util\Security;
use Friendica\Util\Strings;
use Friendica\Worker\Delivery;
use Text_LanguageDetect;
use Friendica\Repository\PermissionSet as RepPermissionSet;

class Item
{
	// Posting types, inspired by https://www.w3.org/TR/activitystreams-vocabulary/#object-types
	const PT_ARTICLE = 0;
	const PT_NOTE = 1;
	const PT_PAGE = 2;
	const PT_IMAGE = 16;
	const PT_AUDIO = 17;
	const PT_VIDEO = 18;
	const PT_DOCUMENT = 19;
	const PT_EVENT = 32;
	const PT_PERSONAL_NOTE = 128;

	// Field list that is used to display the items
	const DISPLAY_FIELDLIST = [
		'uid', 'id', 'parent', 'uri-id', 'uri', 'thr-parent', 'parent-uri', 'guid', 'network', 'gravity',
		'commented', 'created', 'edited', 'received', 'verb', 'object-type', 'postopts', 'plink',
		'wall', 'private', 'starred', 'origin', 'title', 'body', 'file', 'attach', 'language',
		'content-warning', 'location', 'coord', 'app', 'rendered-hash', 'rendered-html', 'object',
		'allow_cid', 'allow_gid', 'deny_cid', 'deny_gid', 'item_id',
		'author-id', 'author-link', 'author-name', 'author-avatar', 'author-network',
		'owner-id', 'owner-link', 'owner-name', 'owner-avatar', 'owner-network',
		'contact-id', 'contact-uid', 'contact-link', 'contact-name', 'contact-avatar',
		'writable', 'self', 'cid', 'alias', 'pinned',
		'event-id', 'event-created', 'event-edited', 'event-start', 'event-finish',
		'event-summary', 'event-desc', 'event-location', 'event-type',
		'event-nofinish', 'event-adjust', 'event-ignore', 'event-id',
		'delivery_queue_count', 'delivery_queue_done', 'delivery_queue_failed'
	];

	// Field list that is used to deliver items via the protocols
	const DELIVER_FIELDLIST = ['uid', 'id', 'parent', 'uri-id', 'uri', 'thr-parent', 'parent-uri', 'guid',
			'parent-guid', 'created', 'edited', 'verb', 'object-type', 'object', 'target',
			'private', 'title', 'body', 'location', 'coord', 'app',
			'attach', 'deleted', 'extid', 'post-type', 'gravity',
			'allow_cid', 'allow_gid', 'deny_cid', 'deny_gid',
			'author-id', 'author-link', 'owner-link', 'contact-uid',
			'signed_text', 'signature', 'signer', 'network'];

	// Field list for "item-content" table that is mixed with the item table
	const MIXED_CONTENT_FIELDLIST = ['title', 'content-warning', 'body', 'location',
			'coord', 'app', 'rendered-hash', 'rendered-html', 'verb',
			'object-type', 'object', 'target-type', 'target', 'plink'];

	// Field list for "item-content" table that is not present in the "item" table
	const CONTENT_FIELDLIST = ['language'];

	// All fields in the item table
	const ITEM_FIELDLIST = ['id', 'uid', 'parent', 'uri', 'parent-uri', 'thr-parent',
			'guid', 'uri-id', 'parent-uri-id', 'thr-parent-id', 'vid',
			'contact-id', 'type', 'wall', 'gravity', 'extid', 'icid', 'psid',
			'created', 'edited', 'commented', 'received', 'changed', 'verb',
			'postopts', 'plink', 'resource-id', 'event-id', 'attach', 'inform',
			'file', 'allow_cid', 'allow_gid', 'deny_cid', 'deny_gid', 'post-type',
			'private', 'pubmail', 'moderated', 'visible', 'starred', 'bookmark',
			'unseen', 'deleted', 'origin', 'forum_mode', 'mention', 'global', 'network',
			'title', 'content-warning', 'body', 'location', 'coord', 'app',
			'rendered-hash', 'rendered-html', 'object-type', 'object', 'target-type', 'target',
			'author-id', 'author-link', 'author-name', 'author-avatar', 'author-network',
			'owner-id', 'owner-link', 'owner-name', 'owner-avatar'];

	// List of all verbs that don't need additional content data.
	// Never reorder or remove entries from this list. Just add new ones at the end, if needed.
	const ACTIVITIES = [
		Activity::LIKE, Activity::DISLIKE,
		Activity::ATTEND, Activity::ATTENDNO, Activity::ATTENDMAYBE,
		Activity::FOLLOW,
		Activity::ANNOUNCE];

	const PUBLIC = 0;
	const PRIVATE = 1;
	const UNLISTED = 2;

	private static $legacy_mode = null;

	public static function isLegacyMode()
	{
		if (is_null(self::$legacy_mode)) {
			self::$legacy_mode = (DI::config()->get("system", "post_update_version") < 1279);
		}

		return self::$legacy_mode;
	}

	/**
	 * Set the pinned state of an item
	 *
	 * @param integer $iid    Item ID
	 * @param integer $uid    User ID
	 * @param boolean $pinned Pinned state
	 */
	public static function setPinned(int $iid, int $uid, bool $pinned)
	{
		DBA::update('user-item', ['pinned' => $pinned], ['iid' => $iid, 'uid' => $uid], true);
	}

	/**
	 * Get the pinned state
	 *
	 * @param integer $iid Item ID
	 * @param integer $uid User ID
	 *
	 * @return boolean pinned state
	 */
	public static function getPinned(int $iid, int $uid)
	{
		$useritem = DBA::selectFirst('user-item', ['pinned'], ['iid' => $iid, 'uid' => $uid]);
		if (!DBA::isResult($useritem)) {
			return false;
		}
		return (bool)$useritem['pinned'];
	}

	/**
	 * Select pinned rows from the item table for a given user
	 *
	 * @param integer $uid       User ID
	 * @param array   $selected  Array of selected fields, empty for all
	 * @param array   $condition Array of fields for condition
	 * @param array   $params    Array of several parameters
	 *
	 * @return boolean|object
	 * @throws \Exception
	 */
	public static function selectPinned(int $uid, array $selected = [], array $condition = [], $params = [])
	{
		$useritems = DBA::select('user-item', ['iid'], ['uid' => $uid, 'pinned' => true]);
		if (!DBA::isResult($useritems)) {
			return $useritems;
		}

		$pinned = [];
		while ($useritem = DBA::fetch($useritems)) {
			$pinned[] = $useritem['iid'];
		}
		DBA::close($useritems);

		if (empty($pinned)) {
			return [];
		}

		if (empty($condition) || !is_array($condition)) {
			$condition = ['iid' => $pinned];
		} else {
			reset($condition);
			$first_key = key($condition);
			if (!is_int($first_key)) {
				$condition['iid'] = $pinned;
			} else {
				$values_string = substr(str_repeat("?, ", count($pinned)), 0, -2);
				$condition[0] = '(' . $condition[0] . ") AND `iid` IN (" . $values_string . ")";
				$condition = array_merge($condition, $pinned);
			}
		}

		return self::selectThreadForUser($uid, $selected, $condition, $params);
	}

	/**
	 * Fetch a single item row
	 *
	 * @param mixed $stmt statement object
	 * @return array current row
	 */
	public static function fetch($stmt)
	{
		$row = DBA::fetch($stmt);

		if (is_bool($row)) {
			return $row;
		}

		// ---------------------- Transform item structure data ----------------------

		// We prefer the data from the user's contact over the public one
		if (!empty($row['author-link']) && !empty($row['contact-link']) &&
			($row['author-link'] == $row['contact-link'])) {
			if (isset($row['author-avatar']) && !empty($row['contact-avatar'])) {
				$row['author-avatar'] = $row['contact-avatar'];
			}
			if (isset($row['author-name']) && !empty($row['contact-name'])) {
				$row['author-name'] = $row['contact-name'];
			}
		}

		if (!empty($row['owner-link']) && !empty($row['contact-link']) &&
			($row['owner-link'] == $row['contact-link'])) {
			if (isset($row['owner-avatar']) && !empty($row['contact-avatar'])) {
				$row['owner-avatar'] = $row['contact-avatar'];
			}
			if (isset($row['owner-name']) && !empty($row['contact-name'])) {
				$row['owner-name'] = $row['contact-name'];
			}
		}

		// We can always comment on posts from these networks
		if (array_key_exists('writable', $row) &&
			in_array($row['internal-network'], Protocol::FEDERATED)) {
			$row['writable'] = true;
		}

		// ---------------------- Transform item content data ----------------------

		// Fetch data from the item-content table whenever there is content there
		if (self::isLegacyMode()) {
			$legacy_fields = array_merge(Post\DeliveryData::LEGACY_FIELD_LIST, self::MIXED_CONTENT_FIELDLIST);
			foreach ($legacy_fields as $field) {
				if (empty($row[$field]) && !empty($row['internal-item-' . $field])) {
					$row[$field] = $row['internal-item-' . $field];
				}
				unset($row['internal-item-' . $field]);
			}
		}

		if (array_key_exists('verb', $row)) {
			if (!is_null($row['internal-verb'])) {
				$row['verb'] = $row['internal-verb'];
			}

			if (in_array($row['verb'], self::ACTIVITIES)) {
				if (array_key_exists('title', $row)) {
					$row['title'] = '';
				}
				if (array_key_exists('body', $row)) {
					$row['body'] = $row['verb'];
				}
				if (array_key_exists('object', $row)) {
					$row['object'] = '';
				}
				if (array_key_exists('object-type', $row)) {
					$row['object-type'] = Activity\ObjectType::NOTE;
				}
			} elseif (in_array($row['verb'], ['', Activity::POST, Activity::SHARE])) {
				// Posts don't have a target - but having tags or files.
				if (array_key_exists('target', $row)) {
					$row['target'] = '';
				}
			}
		}

		if (array_key_exists('vid', $row) && is_null($row['vid']) && !empty($row['verb'])) {
			$row['vid'] = Verb::getID($row['verb']);
		}
			
		if (!array_key_exists('verb', $row) || in_array($row['verb'], ['', Activity::POST, Activity::SHARE])) {
			// Build the file string out of the term entries
			if (array_key_exists('file', $row) && empty($row['file'])) {
				$row['file'] = Category::getTextByURIId($row['internal-uri-id'], $row['internal-uid']);
			}
		}

		if ($row['internal-psid'] == RepPermissionSet::PUBLIC) {
			if (array_key_exists('allow_cid', $row)) {
				$row['allow_cid'] = '';
			}
			if (array_key_exists('allow_gid', $row)) {
				$row['allow_gid'] = '';
			}
			if (array_key_exists('deny_cid', $row)) {
				$row['deny_cid'] = '';
			}
			if (array_key_exists('deny_gid', $row)) {
				$row['deny_gid'] = '';
			}
		}

		if (array_key_exists('ignored', $row) && array_key_exists('internal-user-ignored', $row) && !is_null($row['internal-user-ignored'])) {
			$row['ignored'] = $row['internal-user-ignored'];
		}

		// Remove internal fields
		unset($row['internal-network']);
		unset($row['internal-uri-id']);
		unset($row['internal-uid']);
		unset($row['internal-psid']);
		unset($row['internal-verb']);
		unset($row['internal-user-ignored']);
		unset($row['interaction']);

		return $row;
	}

	/**
	 * Fills an array with data from an item query
	 *
	 * @param object $stmt statement object
	 * @param bool   $do_close
	 * @return array Data array
	 */
	public static function inArray($stmt, $do_close = true) {
		if (is_bool($stmt)) {
			return $stmt;
		}

		$data = [];
		while ($row = self::fetch($stmt)) {
			$data[] = $row;
		}
		if ($do_close) {
			DBA::close($stmt);
		}
		return $data;
	}

	/**
	 * Check if item data exists
	 *
	 * @param array $condition array of fields for condition
	 *
	 * @return boolean Are there rows for that condition?
	 * @throws \Exception
	 */
	public static function exists($condition) {
		$stmt = self::select(['id'], $condition, ['limit' => 1]);

		if (is_bool($stmt)) {
			$retval = $stmt;
		} else {
			$retval = (DBA::numRows($stmt) > 0);
		}

		DBA::close($stmt);

		return $retval;
	}

	/**
	 * Retrieve a single record from the item table for a given user and returns it in an associative array
	 *
	 * @param integer $uid User ID
	 * @param array   $selected
	 * @param array   $condition
	 * @param array   $params
	 * @return bool|array
	 * @throws \Exception
	 * @see   DBA::select
	 */
	public static function selectFirstForUser($uid, array $selected = [], array $condition = [], $params = [])
	{
		$params['uid'] = $uid;

		if (empty($selected)) {
			$selected = Item::DISPLAY_FIELDLIST;
		}

		return self::selectFirst($selected, $condition, $params);
	}

	/**
	 * Select rows from the item table for a given user
	 *
	 * @param integer $uid       User ID
	 * @param array   $selected  Array of selected fields, empty for all
	 * @param array   $condition Array of fields for condition
	 * @param array   $params    Array of several parameters
	 *
	 * @return boolean|object
	 * @throws \Exception
	 */
	public static function selectForUser($uid, array $selected = [], array $condition = [], $params = [])
	{
		$params['uid'] = $uid;

		if (empty($selected)) {
			$selected = Item::DISPLAY_FIELDLIST;
		}

		return self::select($selected, $condition, $params);
	}

	/**
	 * Retrieve a single record from the item table and returns it in an associative array
	 *
	 * @param array $fields
	 * @param array $condition
	 * @param array $params
	 * @return bool|array
	 * @throws \Exception
	 * @see   DBA::select
	 */
	public static function selectFirst(array $fields = [], array $condition = [], $params = [])
	{
		$params['limit'] = 1;

		$result = self::select($fields, $condition, $params);

		if (is_bool($result)) {
			return $result;
		} else {
			$row = self::fetch($result);
			DBA::close($result);
			return $row;
		}
	}

	/**
	 * Select rows from the item table and returns them as an array
	 *
	 * @param array $selected  Array of selected fields, empty for all
	 * @param array $condition Array of fields for condition
	 * @param array $params    Array of several parameters
	 *
	 * @return array
	 * @throws \Exception
	 */
	public static function selectToArray(array $fields = [], array $condition = [], $params = [])
	{
		$result = self::select($fields, $condition, $params);

		if (is_bool($result)) {
			return [];
		}

		$data = [];
		while ($row = self::fetch($result)) {
			$data[] = $row;
		}
		DBA::close($result);

		return $data;
	}

	/**
	 * Select rows from the item table
	 *
	 * @param array $selected  Array of selected fields, empty for all
	 * @param array $condition Array of fields for condition
	 * @param array $params    Array of several parameters
	 *
	 * @return boolean|object
	 * @throws \Exception
	 */
	public static function select(array $selected = [], array $condition = [], $params = [])
	{
		$uid = 0;
		$usermode = false;

		if (isset($params['uid'])) {
			$uid = $params['uid'];
			$usermode = true;
		}

		$fields = self::fieldlist($usermode);

		$select_fields = self::constructSelectFields($fields, $selected);

		$condition_string = DBA::buildCondition($condition);

		$condition_string = self::addTablesToFields($condition_string, $fields);

		if ($usermode) {
			$condition_string = $condition_string . ' AND ' . self::condition(false);
		}

		$param_string = self::addTablesToFields(DBA::buildParameter($params), $fields);

		$table = "`item` " . self::constructJoins($uid, $select_fields . $condition_string . $param_string, false, $usermode);

		$sql = "SELECT " . $select_fields . " FROM " . $table . $condition_string . $param_string;

		return DBA::p($sql, $condition);
	}

	/**
	 * Select rows from the starting post in the item table
	 *
	 * @param integer $uid       User ID
	 * @param array   $selected
	 * @param array   $condition Array of fields for condition
	 * @param array   $params    Array of several parameters
	 *
	 * @return boolean|object
	 * @throws \Exception
	 */
	public static function selectThreadForUser($uid, array $selected = [], array $condition = [], $params = [])
	{
		$params['uid'] = $uid;

		if (empty($selected)) {
			$selected = Item::DISPLAY_FIELDLIST;
		}

		return self::selectThread($selected, $condition, $params);
	}

	/**
	 * Retrieve a single record from the starting post in the item table and returns it in an associative array
	 *
	 * @param integer $uid User ID
	 * @param array   $selected
	 * @param array   $condition
	 * @param array   $params
	 * @return bool|array
	 * @throws \Exception
	 * @see   DBA::select
	 */
	public static function selectFirstThreadForUser($uid, array $selected = [], array $condition = [], $params = [])
	{
		$params['uid'] = $uid;

		if (empty($selected)) {
			$selected = Item::DISPLAY_FIELDLIST;
		}

		return self::selectFirstThread($selected, $condition, $params);
	}

	/**
	 * Retrieve a single record from the starting post in the item table and returns it in an associative array
	 *
	 * @param array $fields
	 * @param array $condition
	 * @param array $params
	 * @return bool|array
	 * @throws \Exception
	 * @see   DBA::select
	 */
	public static function selectFirstThread(array $fields = [], array $condition = [], $params = [])
	{
		$params['limit'] = 1;
		$result = self::selectThread($fields, $condition, $params);

		if (is_bool($result)) {
			return $result;
		} else {
			$row = self::fetch($result);
			DBA::close($result);
			return $row;
		}
	}

	/**
	 * Select rows from the starting post in the item table
	 *
	 * @param array $selected  Array of selected fields, empty for all
	 * @param array $condition Array of fields for condition
	 * @param array $params    Array of several parameters
	 *
	 * @return boolean|object
	 * @throws \Exception
	 */
	public static function selectThread(array $selected = [], array $condition = [], $params = [])
	{
		$uid = 0;
		$usermode = false;

		if (isset($params['uid'])) {
			$uid = $params['uid'];
			$usermode = true;
		}

		$fields = self::fieldlist($usermode);

		$fields['thread'] = ['mention', 'ignored', 'iid'];

		$threadfields = ['thread' => ['iid', 'uid', 'contact-id', 'owner-id', 'author-id',
			'created', 'edited', 'commented', 'received', 'changed', 'wall', 'private',
			'pubmail', 'moderated', 'visible', 'starred', 'ignored', 'post-type',
			'unseen', 'deleted', 'origin', 'forum_mode', 'mention', 'network']];

		$select_fields = self::constructSelectFields($fields, $selected);

		$condition_string = DBA::buildCondition($condition);

		$condition_string = self::addTablesToFields($condition_string, $threadfields);
		$condition_string = self::addTablesToFields($condition_string, $fields);

		if ($usermode) {
			$condition_string = $condition_string . ' AND ' . self::condition(true);
		}

		$param_string = DBA::buildParameter($params);
		$param_string = self::addTablesToFields($param_string, $threadfields);
		$param_string = self::addTablesToFields($param_string, $fields);

		$table = "`thread` " . self::constructJoins($uid, $select_fields . $condition_string . $param_string, true, $usermode);

		$sql = "SELECT " . $select_fields . " FROM " . $table . $condition_string . $param_string;

		return DBA::p($sql, $condition);
	}

	/**
	 * Returns a list of fields that are associated with the item table
	 *
	 * @param $usermode
	 * @return array field list
	 */
	private static function fieldlist($usermode)
	{
		$fields = [];

		$fields['item'] = ['id', 'uid', 'parent', 'uri', 'parent-uri', 'thr-parent',
			'guid', 'uri-id', 'parent-uri-id', 'thr-parent-id', 'vid',
			'contact-id', 'owner-id', 'author-id', 'type', 'wall', 'gravity', 'extid',
			'created', 'edited', 'commented', 'received', 'changed', 'psid',
			'resource-id', 'event-id', 'attach', 'post-type', 'file',
			'private', 'pubmail', 'moderated', 'visible', 'starred', 'bookmark',
			'unseen', 'deleted', 'origin', 'forum_mode', 'mention', 'global',
			'id' => 'item_id', 'network', 'icid',
			'uri-id' => 'internal-uri-id', 'uid' => 'internal-uid',
			'network' => 'internal-network', 'psid' => 'internal-psid'];

		if ($usermode) {
			$fields['user-item'] = ['pinned', 'notification-type', 'ignored' => 'internal-user-ignored'];
		}

		$fields['item-content'] = array_merge(self::CONTENT_FIELDLIST, self::MIXED_CONTENT_FIELDLIST);

		$fields['post-delivery-data'] = array_merge(Post\DeliveryData::LEGACY_FIELD_LIST, Post\DeliveryData::FIELD_LIST);

		$fields['verb'] = ['name' => 'internal-verb'];

		$fields['permissionset'] = ['allow_cid', 'allow_gid', 'deny_cid', 'deny_gid'];

		$fields['author'] = ['url' => 'author-link', 'name' => 'author-name', 'addr' => 'author-addr',
			'thumb' => 'author-avatar', 'nick' => 'author-nick', 'network' => 'author-network'];

		$fields['owner'] = ['url' => 'owner-link', 'name' => 'owner-name', 'addr' => 'owner-addr',
			'thumb' => 'owner-avatar', 'nick' => 'owner-nick', 'network' => 'owner-network'];

		$fields['contact'] = ['url' => 'contact-link', 'name' => 'contact-name', 'thumb' => 'contact-avatar',
			'writable', 'self', 'id' => 'cid', 'alias', 'uid' => 'contact-uid',
			'photo', 'name-date', 'uri-date', 'avatar-date', 'thumb', 'dfrn-id'];

		$fields['parent-item'] = ['guid' => 'parent-guid', 'network' => 'parent-network'];

		$fields['parent-item-author'] = ['url' => 'parent-author-link', 'name' => 'parent-author-name',
			'network' => 'parent-author-network'];

		$fields['event'] = ['created' => 'event-created', 'edited' => 'event-edited',
			'start' => 'event-start','finish' => 'event-finish',
			'summary' => 'event-summary','desc' => 'event-desc',
			'location' => 'event-location', 'type' => 'event-type',
			'nofinish' => 'event-nofinish','adjust' => 'event-adjust',
			'ignore' => 'event-ignore', 'id' => 'event-id'];

		$fields['diaspora-interaction'] = ['interaction', 'interaction' => 'signed_text'];

		return $fields;
	}

	/**
	 * Returns SQL condition for the "select" functions
	 *
	 * @param boolean $thread_mode Called for the items (false) or for the threads (true)
	 *
	 * @return string SQL condition
	 */
	private static function condition($thread_mode)
	{
		if ($thread_mode) {
			$master_table = "`thread`";
		} else {
			$master_table = "`item`";
		}
		return sprintf("$master_table.`visible` AND NOT $master_table.`deleted` AND NOT $master_table.`moderated`
			AND (`user-item`.`hidden` IS NULL OR NOT `user-item`.`hidden`)
			AND (`user-author`.`blocked` IS NULL OR NOT `user-author`.`blocked`)
			AND (`user-author`.`ignored` IS NULL OR NOT `user-author`.`ignored` OR `item`.`gravity` != %d)
			AND (`user-owner`.`blocked` IS NULL OR NOT `user-owner`.`blocked`)
			AND (`user-owner`.`ignored` IS NULL OR NOT `user-owner`.`ignored` OR `item`.`gravity` != %d) ",
			GRAVITY_PARENT, GRAVITY_PARENT);
	}

	/**
	 * Returns all needed "JOIN" commands for the "select" functions
	 *
	 * @param integer $uid          User ID
	 * @param string  $sql_commands The parts of the built SQL commands in the "select" functions
	 * @param boolean $thread_mode  Called for the items (false) or for the threads (true)
	 *
	 * @param         $user_mode
	 * @return string The SQL joins for the "select" functions
	 */
	private static function constructJoins($uid, $sql_commands, $thread_mode, $user_mode)
	{
		if ($thread_mode) {
			$master_table = "`thread`";
			$master_table_key = "`thread`.`iid`";
			$joins = "STRAIGHT_JOIN `item` ON `item`.`id` = `thread`.`iid` ";
		} else {
			$master_table = "`item`";
			$master_table_key = "`item`.`id`";
			$joins = '';
		}

		if ($user_mode) {
			$joins .= sprintf("STRAIGHT_JOIN `contact` ON `contact`.`id` = $master_table.`contact-id`
				AND NOT `contact`.`blocked`
				AND ((NOT `contact`.`readonly` AND NOT `contact`.`pending` AND (`contact`.`rel` IN (%s, %s)))
				OR `contact`.`self` OR `item`.`gravity` != %d OR `contact`.`uid` = 0)
				STRAIGHT_JOIN `contact` AS `author` ON `author`.`id` = $master_table.`author-id` AND NOT `author`.`blocked`
				STRAIGHT_JOIN `contact` AS `owner` ON `owner`.`id` = $master_table.`owner-id` AND NOT `owner`.`blocked`
				LEFT JOIN `user-item` ON `user-item`.`iid` = $master_table_key AND `user-item`.`uid` = %d
				LEFT JOIN `user-contact` AS `user-author` ON `user-author`.`cid` = $master_table.`author-id` AND `user-author`.`uid` = %d
				LEFT JOIN `user-contact` AS `user-owner` ON `user-owner`.`cid` = $master_table.`owner-id` AND `user-owner`.`uid` = %d",
				Contact::SHARING, Contact::FRIEND, GRAVITY_PARENT, intval($uid), intval($uid), intval($uid));
		} else {
			if (strpos($sql_commands, "`contact`.") !== false) {
				$joins .= "LEFT JOIN `contact` ON `contact`.`id` = $master_table.`contact-id`";
			}
			if (strpos($sql_commands, "`author`.") !== false) {
				$joins .= " LEFT JOIN `contact` AS `author` ON `author`.`id` = $master_table.`author-id`";
			}
			if (strpos($sql_commands, "`owner`.") !== false) {
				$joins .= " LEFT JOIN `contact` AS `owner` ON `owner`.`id` = $master_table.`owner-id`";
			}
		}

		if (strpos($sql_commands, "`group_member`.") !== false) {
			$joins .= " STRAIGHT_JOIN `group_member` ON `group_member`.`contact-id` = $master_table.`contact-id`";
		}

		if (strpos($sql_commands, "`user`.") !== false) {
			$joins .= " STRAIGHT_JOIN `user` ON `user`.`uid` = $master_table.`uid`";
		}

		if (strpos($sql_commands, "`event`.") !== false) {
			$joins .= " LEFT JOIN `event` ON `event-id` = `event`.`id`";
		}

		if (strpos($sql_commands, "`diaspora-interaction`.") !== false) {
			$joins .= " LEFT JOIN `diaspora-interaction` ON `diaspora-interaction`.`uri-id` = `item`.`uri-id`";
		}

		if (strpos($sql_commands, "`item-content`.") !== false) {
			$joins .= " LEFT JOIN `item-content` ON `item-content`.`uri-id` = `item`.`uri-id`";
		}

		if (strpos($sql_commands, "`post-delivery-data`.") !== false) {
			$joins .= " LEFT JOIN `post-delivery-data` ON `post-delivery-data`.`uri-id` = `item`.`uri-id` AND `item`.`origin`";
		}

		if (strpos($sql_commands, "`verb`.") !== false) {
			$joins .= " LEFT JOIN `verb` ON `verb`.`id` = `item`.`vid`";
		}

		if (strpos($sql_commands, "`permissionset`.") !== false) {
			$joins .= " LEFT JOIN `permissionset` ON `permissionset`.`id` = `item`.`psid`";
		}

		if ((strpos($sql_commands, "`parent-item`.") !== false) || (strpos($sql_commands, "`parent-item-author`.") !== false)) {
			$joins .= " STRAIGHT_JOIN `item` AS `parent-item` ON `parent-item`.`id` = `item`.`parent`";

			if (strpos($sql_commands, "`parent-item-author`.") !== false) {
				$joins .= " STRAIGHT_JOIN `contact` AS `parent-item-author` ON `parent-item-author`.`id` = `parent-item`.`author-id`";
			}
		}

		return $joins;
	}

	/**
	 * Add the field list for the "select" functions
	 *
	 * @param array $fields The field definition array
	 * @param array $selected The array with the selected fields from the "select" functions
	 *
	 * @return string The field list
	 */
	private static function constructSelectFields(array $fields, array $selected)
	{
		if (!empty($selected)) {
			$selected = array_merge($selected, ['internal-uri-id', 'internal-uid', 'internal-psid', 'internal-network']);
		}

		if (in_array('verb', $selected)) {
			$selected = array_merge($selected, ['internal-verb']);
		}

		if (in_array('ignored', $selected)) {
			$selected[] = 'internal-user-ignored';
		}

		$legacy_fields = array_merge(Post\DeliveryData::LEGACY_FIELD_LIST, self::MIXED_CONTENT_FIELDLIST);

		$selection = [];
		foreach ($fields as $table => $table_fields) {
			foreach ($table_fields as $field => $select) {
				if (empty($selected) || in_array($select, $selected)) {
					if (self::isLegacyMode() && in_array($select, $legacy_fields)) {
						$selection[] = "`item`.`".$select."` AS `internal-item-" . $select . "`";
					}
					if (is_int($field)) {
						$selection[] = "`" . $table . "`.`" . $select . "`";
					} else {
						$selection[] = "`" . $table . "`.`" . $field . "` AS `" . $select . "`";
					}
				}
			}
		}
		return implode(", ", $selection);
	}

	/**
	 * add table definition to fields in an SQL query
	 *
	 * @param string $query SQL query
	 * @param array $fields The field definition array
	 *
	 * @return string the changed SQL query
	 */
	private static function addTablesToFields($query, $fields)
	{
		foreach ($fields as $table => $table_fields) {
			foreach ($table_fields as $alias => $field) {
				if (is_int($alias)) {
					$replace_field = $field;
				} else {
					$replace_field = $alias;
				}

				$search = "/([^\.])`" . $field . "`/i";
				$replace = "$1`" . $table . "`.`" . $replace_field . "`";
				$query = preg_replace($search, $replace, $query);
			}
		}
		return $query;
	}

	/**
	 * Update existing item entries
	 *
	 * @param array $fields    The fields that are to be changed
	 * @param array $condition The condition for finding the item entries
	 *
	 * In the future we may have to change permissions as well.
	 * Then we had to add the user id as third parameter.
	 *
	 * A return value of "0" doesn't mean an error - but that 0 rows had been changed.
	 *
	 * @return integer|boolean number of affected rows - or "false" if there was an error
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function update(array $fields, array $condition)
	{
		if (empty($condition) || empty($fields)) {
			return false;
		}

		// To ensure the data integrity we do it in an transaction
		DBA::transaction();

		// We cannot simply expand the condition to check for origin entries
		// The condition needn't to be a simple array but could be a complex condition.
		// And we have to execute this query before the update to ensure to fetch the same data.
		$items = DBA::select('item', ['id', 'origin', 'uri', 'uri-id', 'icid', 'uid', 'file'], $condition);

		$content_fields = [];
		foreach (array_merge(self::CONTENT_FIELDLIST, self::MIXED_CONTENT_FIELDLIST) as $field) {
			if (isset($fields[$field])) {
				$content_fields[$field] = $fields[$field];
				if (in_array($field, self::CONTENT_FIELDLIST) || !self::isLegacyMode()) {
					unset($fields[$field]);
				} else {
					$fields[$field] = null;
				}
			}
		}

		$delivery_data = Post\DeliveryData::extractFields($fields);

		$clear_fields = ['bookmark', 'type', 'author-name', 'author-avatar', 'author-link', 'owner-name', 'owner-avatar', 'owner-link', 'postopts', 'inform'];
		foreach ($clear_fields as $field) {
			if (array_key_exists($field, $fields)) {
				$fields[$field] = null;
			}
		}

		if (array_key_exists('file', $fields)) {
			$files = $fields['file'];
			$fields['file'] = null;
		} else {
			$files = null;
		}

		if (!empty($content_fields['verb'])) {
			$fields['vid'] = Verb::getID($content_fields['verb']);
		}

		if (!empty($fields)) {
			$success = DBA::update('item', $fields, $condition);

			if (!$success) {
				DBA::close($items);
				DBA::rollback();
				return false;
			}
		}

		// When there is no content for the "old" item table, this will count the fetched items
		$rows = DBA::affectedRows();

		$notify_items = [];

		while ($item = DBA::fetch($items)) {
			if (empty($content_fields['verb']) || !in_array($content_fields['verb'], self::ACTIVITIES)) {
				self::updateContent($content_fields, ['uri-id' => $item['uri-id']]);

				if (empty($item['icid'])) {
					$item_content = DBA::selectFirst('item-content', [], ['uri-id' => $item['uri-id']]);
					if (DBA::isResult($item_content)) {
						$item_fields = ['icid' => $item_content['id']];
						// Clear all fields in the item table that have a content in the item-content table
						if (self::isLegacyMode()) {
							foreach ($item_content as $field => $content) {
								if (in_array($field, self::MIXED_CONTENT_FIELDLIST) && !empty($content)) {
									$item_fields[$field] = null;
								}
							}
						}
						DBA::update('item', $item_fields, ['id' => $item['id']]);
					}
				}
			}

			if (!is_null($files)) {
				Category::storeTextByURIId($item['uri-id'], $item['uid'], $files);
				if (!empty($item['file'])) {
					DBA::update('item', ['file' => ''], ['id' => $item['id']]);
				}
			}

			Post\DeliveryData::update($item['uri-id'], $delivery_data);

			self::updateThread($item['id']);

			// We only need to notfiy others when it is an original entry from us.
			// Only call the notifier when the item has some content relevant change.
			if ($item['origin'] && in_array('edited', array_keys($fields))) {
				$notify_items[] = $item['id'];
			}
		}

		DBA::close($items);
		DBA::commit();

		foreach ($notify_items as $notify_item) {
			Worker::add(PRIORITY_HIGH, "Notifier", Delivery::POST, $notify_item);
		}

		return $rows;
	}

	/**
	 * Delete an item and notify others about it - if it was ours
	 *
	 * @param array   $condition The condition for finding the item entries
	 * @param integer $priority  Priority for the notification
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function markForDeletion($condition, $priority = PRIORITY_HIGH)
	{
		$items = self::select(['id'], $condition);
		while ($item = self::fetch($items)) {
			self::markForDeletionById($item['id'], $priority);
		}
		DBA::close($items);
	}

	/**
	 * Delete an item for an user and notify others about it - if it was ours
	 *
	 * @param array   $condition The condition for finding the item entries
	 * @param integer $uid       User who wants to delete this item
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function deleteForUser($condition, $uid)
	{
		if ($uid == 0) {
			return;
		}

		$items = self::select(['id', 'uid'], $condition);
		while ($item = self::fetch($items)) {
			// "Deleting" global items just means hiding them
			if ($item['uid'] == 0) {
				DBA::update('user-item', ['hidden' => true], ['iid' => $item['id'], 'uid' => $uid], true);

				// Delete notifications
				DBA::delete('notify', ['iid' => $item['id'], 'uid' => $uid]);
			} elseif ($item['uid'] == $uid) {
				self::markForDeletionById($item['id'], PRIORITY_HIGH);
			} else {
				Logger::log('Wrong ownership. Not deleting item ' . $item['id']);
			}
		}
		DBA::close($items);
	}

	/**
	 * Mark an item for deletion, delete related data and notify others about it - if it was ours
	 *
	 * @param integer $item_id
	 * @param integer $priority Priority for the notification
	 *
	 * @return boolean success
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function markForDeletionById($item_id, $priority = PRIORITY_HIGH)
	{
		Logger::info('Mark item for deletion by id', ['id' => $item_id, 'callstack' => System::callstack()]);
		// locate item to be deleted
		$fields = ['id', 'uri', 'uri-id', 'uid', 'parent', 'parent-uri', 'origin',
			'deleted', 'file', 'resource-id', 'event-id', 'attach',
			'verb', 'object-type', 'object', 'target', 'contact-id',
			'icid', 'psid', 'gravity'];
		$item = self::selectFirst($fields, ['id' => $item_id]);
		if (!DBA::isResult($item)) {
			Logger::info('Item not found.', ['id' => $item_id]);
			return false;
		}

		if ($item['deleted']) {
			Logger::info('Item has already been marked for deletion.', ['id' => $item_id]);
			return false;
		}

		$parent = self::selectFirst(['origin'], ['id' => $item['parent']]);
		if (!DBA::isResult($parent)) {
			$parent = ['origin' => false];
		}

		// clean up categories and tags so they don't end up as orphans

		$matches = [];
		$cnt = preg_match_all('/<(.*?)>/', $item['file'], $matches, PREG_SET_ORDER);

		if ($cnt) {
			foreach ($matches as $mtch) {
				FileTag::unsaveFile($item['uid'], $item['id'], $mtch[1],true);
			}
		}

		$matches = [];

		$cnt = preg_match_all('/\[(.*?)\]/', $item['file'], $matches, PREG_SET_ORDER);

		if ($cnt) {
			foreach ($matches as $mtch) {
				FileTag::unsaveFile($item['uid'], $item['id'], $mtch[1],false);
			}
		}

		/*
		 * If item is a link to a photo resource, nuke all the associated photos
		 * (visitors will not have photo resources)
		 * This only applies to photos uploaded from the photos page. Photos inserted into a post do not
		 * generate a resource-id and therefore aren't intimately linked to the item.
		 */
		/// @TODO: this should first check if photo is used elsewhere
		if (strlen($item['resource-id'])) {
			Photo::delete(['resource-id' => $item['resource-id'], 'uid' => $item['uid']]);
		}

		// If item is a link to an event, delete the event.
		if (intval($item['event-id'])) {
			Event::delete($item['event-id']);
		}

		// If item has attachments, drop them
		/// @TODO: this should first check if attachment is used elsewhere
		foreach (explode(",", $item['attach']) as $attach) {
			preg_match("|attach/(\d+)|", $attach, $matches);
			if (is_array($matches) && count($matches) > 1) {
				Attach::delete(['id' => $matches[1], 'uid' => $item['uid']]);
			}
		}

		// Delete notifications
		DBA::delete('notify', ['iid' => $item['id'], 'uid' => $item['uid']]);

		// Set the item to "deleted"
		$item_fields = ['deleted' => true, 'edited' => DateTimeFormat::utcNow(), 'changed' => DateTimeFormat::utcNow()];
		DBA::update('item', $item_fields, ['id' => $item['id']]);

		Category::storeTextByURIId($item['uri-id'], $item['uid'], '');
		self::deleteThread($item['id'], $item['parent-uri']);

		if (!self::exists(["`uri` = ? AND `uid` != 0 AND NOT `deleted`", $item['uri']])) {
			self::markForDeletion(['uri' => $item['uri'], 'uid' => 0, 'deleted' => false], $priority);
		}

		Post\DeliveryData::delete($item['uri-id']);

		if (!empty($item['icid']) && !self::exists(['icid' => $item['icid'], 'deleted' => false])) {
			DBA::delete('item-content', ['id' => $item['icid']], ['cascade' => false]);
		}
		// When the permission set will be used in photo and events as well,
		// this query here needs to be extended.
		// @todo Currently deactivated. We need the permission set in the deletion process.
		// This is a reminder to add the removal somewhere else.
		//if (!empty($item['psid']) && !self::exists(['psid' => $item['psid'], 'deleted' => false])) {
		//	DBA::delete('permissionset', ['id' => $item['psid']], ['cascade' => false]);
		//}

		// If it's the parent of a comment thread, kill all the kids
		if ($item['gravity'] == GRAVITY_PARENT) {
			self::markForDeletion(['parent' => $item['parent'], 'deleted' => false], $priority);
		}

		// Is it our comment and/or our thread?
		if ($item['origin'] || $parent['origin']) {
			// When we delete the original post we will delete all existing copies on the server as well
			self::markForDeletion(['uri' => $item['uri'], 'deleted' => false], $priority);

			// send the notification upstream/downstream
			Worker::add(['priority' => $priority, 'dont_fork' => true], "Notifier", Delivery::DELETION, intval($item['id']));
		} elseif ($item['uid'] != 0) {

			// When we delete just our local user copy of an item, we have to set a marker to hide it
			$global_item = self::selectFirst(['id'], ['uri' => $item['uri'], 'uid' => 0, 'deleted' => false]);
			if (DBA::isResult($global_item)) {
				DBA::update('user-item', ['hidden' => true], ['iid' => $global_item['id'], 'uid' => $item['uid']], true);
			}
		}

		Logger::info('Item has been marked for deletion.', ['id' => $item_id]);

		return true;
	}


	private static function guid($item, $notify)
	{
		if (!empty($item['guid'])) {
			return Strings::escapeTags(trim($item['guid']));
		}

		if ($notify) {
			// We have to avoid duplicates. So we create the GUID in form of a hash of the plink or uri.
			// We add the hash of our own host because our host is the original creator of the post.
			$prefix_host = DI::baseUrl()->getHostname();
		} else {
			$prefix_host = '';

			// We are only storing the post so we create a GUID from the original hostname.
			if (!empty($item['author-link'])) {
				$parsed = parse_url($item['author-link']);
				if (!empty($parsed['host'])) {
					$prefix_host = $parsed['host'];
				}
			}

			if (empty($prefix_host) && !empty($item['plink'])) {
				$parsed = parse_url($item['plink']);
				if (!empty($parsed['host'])) {
					$prefix_host = $parsed['host'];
				}
			}

			if (empty($prefix_host) && !empty($item['uri'])) {
				$parsed = parse_url($item['uri']);
				if (!empty($parsed['host'])) {
					$prefix_host = $parsed['host'];
				}
			}

			// Is it in the format data@host.tld? - Used for mail contacts
			if (empty($prefix_host) && !empty($item['author-link']) && strstr($item['author-link'], '@')) {
				$mailparts = explode('@', $item['author-link']);
				$prefix_host = array_pop($mailparts);
			}
		}

		if (!empty($item['plink'])) {
			$guid = self::guidFromUri($item['plink'], $prefix_host);
		} elseif (!empty($item['uri'])) {
			$guid = self::guidFromUri($item['uri'], $prefix_host);
		} else {
			$guid = System::createUUID(hash('crc32', $prefix_host));
		}

		return $guid;
	}

	private static function contactId($item)
	{
		if (!empty($item['contact-id']) && DBA::exists('contact', ['self' => true, 'id' => $item['contact-id']])) {
			return $item['contact-id'];
		} elseif (($item['gravity'] == GRAVITY_PARENT) && !empty($item['uid']) && !empty($item['contact-id']) && Contact::isSharing($item['contact-id'], $item['uid'])) {
			return $item['contact-id'];
		} elseif (!empty($item['uid']) && !Contact::isSharing($item['author-id'], $item['uid'])) {
			return $item['author-id'];
		} elseif (!empty($item['contact-id'])) {
			return $item['contact-id'];
		} else {
			$contact_id = Contact::getIdForURL($item['author-link'], $item['uid']);
			if (!empty($contact_id)) {
				return $contact_id;
			}
		}
		return $item['author-id'];
	}

	// This function will finally cover most of the preparation functionality in mod/item.php
	public static function prepare(&$item)
	{
		/*
		 * @TODO: Unused code triggering inspection errors
		 *
		$data = BBCode::getAttachmentData($item['body']);
		if ((preg_match_all("/\[bookmark\=([^\]]*)\](.*?)\[\/bookmark\]/ism", $item['body'], $match, PREG_SET_ORDER) || isset($data["type"]))
			&& ($posttype != Item::PT_PERSONAL_NOTE)) {
			$posttype = Item::PT_PAGE;
			$objecttype = ACTIVITY_OBJ_BOOKMARK;
		}
		 */
	}

	/**
	 * Write an item array into a spool file to be inserted later.
	 * This command is called whenever there are issues storing an item.
	 *
	 * @param array $item The item fields that are to be inserted
	 * @throws \Exception
	 */
	private static function spool($orig_item)
	{
		// Now we store the data in the spool directory
		// We use "microtime" to keep the arrival order and "mt_rand" to avoid duplicates
		$file = 'item-' . round(microtime(true) * 10000) . '-' . mt_rand() . '.msg';

		$spoolpath = get_spoolpath();
		if ($spoolpath != "") {
			$spool = $spoolpath . '/' . $file;

			file_put_contents($spool, json_encode($orig_item));
			Logger::warning("Item wasn't stored - Item was spooled into file", ['file' => $file]);
		}
	}

	/**
	 * Check if the item array is a duplicate
	 *
	 * @param array $item
	 * @return boolean is it a duplicate?
	 */
	private static function isDuplicate(array $item)
	{
		// Checking if there is already an item with the same guid
		$condition = ['guid' => $item['guid'], 'network' => $item['network'], 'uid' => $item['uid']];
		if (self::exists($condition)) {
			Logger::notice('Found already existing item', [
				'guid' => $item['guid'],
				'uid' => $item['uid'],
				'network' => $item['network']
			]);
			return true;
		}

		$condition = ["`uri` = ? AND `network` IN (?, ?) AND `uid` = ?",
			$item['uri'], $item['network'], Protocol::DFRN, $item['uid']];
		if (self::exists($condition)) {
			Logger::notice('duplicated item with the same uri found.', $item);
			return true;
		}

		// On Friendica and Diaspora the GUID is unique
		if (in_array($item['network'], [Protocol::DFRN, Protocol::DIASPORA])) {
			$condition = ['guid' => $item['guid'], 'uid' => $item['uid']];
			if (self::exists($condition)) {
				Logger::notice('duplicated item with the same guid found.', $item);
				return true;
			}
		} elseif ($item['network'] == Protocol::OSTATUS) {
			// Check for an existing post with the same content. There seems to be a problem with OStatus.
			$condition = ["`body` = ? AND `network` = ? AND `created` = ? AND `contact-id` = ? AND `uid` = ?",
					$item['body'], $item['network'], $item['created'], $item['contact-id'], $item['uid']];
			if (self::exists($condition)) {
				Logger::notice('duplicated item with the same body found.', $item);
				return true;
			}
		}

		/*
		 * Check for already added items.
		 * There is a timing issue here that sometimes creates double postings.
		 * An unique index would help - but the limitations of MySQL (maximum size of index values) prevent this.
		 */
		if (($item['uid'] == 0) && self::exists(['uri' => trim($item['uri']), 'uid' => 0])) {
			Logger::notice('Global item already stored.', ['uri' => $item['uri'], 'network' => $item['network']]);
			return true;
		}

		return false;
	}

	/**
	 * Check if the item array is valid
	 *
	 * @param array $item
	 * @return boolean item is valid
	 */
	private static function isValid(array $item)
	{
		// When there is no content then we don't post it
		if ($item['body'].$item['title'] == '') {
			Logger::notice('No body, no title.');
			return false;
		}

		// check for create date and expire time
		$expire_interval = DI::config()->get('system', 'dbclean-expire-days', 0);

		$user = DBA::selectFirst('user', ['expire'], ['uid' => $item['uid']]);
		if (DBA::isResult($user) && ($user['expire'] > 0) && (($user['expire'] < $expire_interval) || ($expire_interval == 0))) {
			$expire_interval = $user['expire'];
		}

		if (($expire_interval > 0) && !empty($item['created'])) {
			$expire_date = time() - ($expire_interval * 86400);
			$created_date = strtotime($item['created']);
			if ($created_date < $expire_date) {
				Logger::notice('Item created before expiration interval.', [
					'created' => date('c', $created_date),
					'expired' => date('c', $expire_date),
					'$item' => $item
				]);
				return false;
			}
		}

		if (Contact::isBlocked($item['author-id'])) {
			Logger::notice('Author is blocked node-wide', ['author-link' => $item['author-link'], 'item-uri' => $item['uri']]);
			return false;
		}

		if (!empty($item['author-link']) && Network::isUrlBlocked($item['author-link'])) {
			Logger::notice('Author server is blocked', ['author-link' => $item['author-link'], 'item-uri' => $item['uri']]);
			return false;
		}

		if (!empty($item['uid']) && Contact::isBlockedByUser($item['author-id'], $item['uid'])) {
			Logger::notice('Author is blocked by user', ['author-link' => $item['author-link'], 'uid' => $item['uid'], 'item-uri' => $item['uri']]);
			return false;
		}

		if (Contact::isBlocked($item['owner-id'])) {
			Logger::notice('Owner is blocked node-wide', ['owner-link' => $item['owner-link'], 'item-uri' => $item['uri']]);
			return false;
		}

		if (!empty($item['owner-link']) && Network::isUrlBlocked($item['owner-link'])) {
			Logger::notice('Owner server is blocked', ['owner-link' => $item['owner-link'], 'item-uri' => $item['uri']]);
			return false;
		}

		if (!empty($item['uid']) && Contact::isBlockedByUser($item['owner-id'], $item['uid'])) {
			Logger::notice('Owner is blocked by user', ['owner-link' => $item['owner-link'], 'uid' => $item['uid'], 'item-uri' => $item['uri']]);
			return false;
		}

		// The causer is set during a thread completion, for example because of a reshare. It countains the responsible actor.
		if (!empty($item['uid']) && !empty($item['causer-id']) && Contact::isBlockedByUser($item['causer-id'], $item['uid'])) {
			Logger::notice('Causer is blocked by user', ['causer-link' => $item['causer-link'], 'uid' => $item['uid'], 'item-uri' => $item['uri']]);
			return false;
		}

		if (!empty($item['uid']) && !empty($item['causer-id']) && ($item['parent-uri'] == $item['uri']) && Contact::isIgnoredByUser($item['causer-id'], $item['uid'])) {
			Logger::notice('Causer is ignored by user', ['causer-link' => $item['causer-link'], 'uid' => $item['uid'], 'item-uri' => $item['uri']]);
			return false;
		}
		
		if ($item['verb'] == Activity::FOLLOW) {
			if (!$item['origin'] && ($item['author-id'] == Contact::getPublicIdByUserId($item['uid']))) {
				// Our own follow request can be relayed to us. We don't store it to avoid notification chaos.
				Logger::info("Follow: Don't store not origin follow request", ['parent-uri' => $item['parent-uri']]);
				return false;
			}

			$condition = ['verb' => Activity::FOLLOW, 'uid' => $item['uid'],
				'parent-uri' => $item['parent-uri'], 'author-id' => $item['author-id']];
			if (self::exists($condition)) {
				// It happens that we receive multiple follow requests by the same author - we only store one.
				Logger::info('Follow: Found existing follow request from author', ['author-id' => $item['author-id'], 'parent-uri' => $item['parent-uri']]);
				return false;
			}
		}

		return true;
	}

	/**
	 * Return the id of the given item array if it has been stored before
	 *
	 * @param array $item
	 * @return integer item id
	 */
	private static function getDuplicateID(array $item)
	{
		if (empty($item['network']) || in_array($item['network'], Protocol::FEDERATED)) {
			$condition = ["`uri` = ? AND `uid` = ? AND `network` IN (?, ?, ?, ?)",
				trim($item['uri']), $item['uid'],
				Protocol::ACTIVITYPUB, Protocol::DIASPORA, Protocol::DFRN, Protocol::OSTATUS];
			$existing = self::selectFirst(['id', 'network'], $condition);
			if (DBA::isResult($existing)) {
				// We only log the entries with a different user id than 0. Otherwise we would have too many false positives
				if ($item['uid'] != 0) {
					Logger::notice('Item already existed for user', [
						'uri' => $item['uri'],
						'uid' => $item['uid'],
						'network' => $item['network'],
						'existing_id' => $existing["id"],
						'existing_network' => $existing["network"]
					]);
				}

				return $existing["id"];
			}
		}
		return 0;
	}

	/**
	 * Fetch parent data for the given item array
	 *
	 * @param array $item
	 * @return array item array with parent data
	 */
	private static function getParentData(array $item)
	{
		// find the parent and snarf the item id and ACLs
		// and anything else we need to inherit

		$fields = ['uri', 'parent-uri', 'id', 'deleted',
			'allow_cid', 'allow_gid', 'deny_cid', 'deny_gid',
			'wall', 'private', 'forum_mode', 'origin', 'author-id'];
		$condition = ['uri' => $item['parent-uri'], 'uid' => $item['uid']];
		$params = ['order' => ['id' => false]];
		$parent = self::selectFirst($fields, $condition, $params);

		if (!DBA::isResult($parent)) {
			Logger::info('item parent was not found - ignoring item', ['parent-uri' => $item['parent-uri'], 'uid' => $item['uid']]);
			return [];
		} else {
			// is the new message multi-level threaded?
			// even though we don't support it now, preserve the info
			// and re-attach to the conversation parent.
			if ($parent['uri'] != $parent['parent-uri']) {
				$item['parent-uri'] = $parent['parent-uri'];

				$condition = ['uri' => $item['parent-uri'],
					'parent-uri' => $item['parent-uri'],
					'uid' => $item['uid']];
				$params = ['order' => ['id' => false]];
				$toplevel_parent = self::selectFirst($fields, $condition, $params);

				if (DBA::isResult($toplevel_parent)) {
					$parent = $toplevel_parent;
				}
			}

			$item['parent']        = $parent['id'];
			$item["deleted"]       = $parent['deleted'];
			$item["allow_cid"]     = $parent['allow_cid'];
			$item['allow_gid']     = $parent['allow_gid'];
			$item['deny_cid']      = $parent['deny_cid'];
			$item['deny_gid']      = $parent['deny_gid'];
			$item['parent_origin'] = $parent['origin'];

			// Don't federate received participation messages
			if ($item['verb'] != Activity::FOLLOW) {
				$item['wall'] = $parent['wall'];
			} else {
				$item['wall'] = false;
			}

			/*
			 * If the parent is private, force privacy for the entire conversation
			 * This differs from the above settings as it subtly allows comments from
			 * email correspondents to be private even if the overall thread is not.
			 */
			if ($parent['private']) {
				$item['private'] = $parent['private'];
			}

			/*
			 * Edge case. We host a public forum that was originally posted to privately.
			 * The original author commented, but as this is a comment, the permissions
			 * weren't fixed up so it will still show the comment as private unless we fix it here.
			 */
			if ((intval($parent['forum_mode']) == 1) && ($parent['private'] != self::PUBLIC)) {
				$item['private'] = self::PUBLIC;
			}

			// If its a post that originated here then tag the thread as "mention"
			if ($item['origin'] && $item['uid']) {
				DBA::update('thread', ['mention' => true], ['iid' => $item['parent']]);
				Logger::info('tagged thread as mention', ['parent' => $item['parent'], 'uid' => $item['uid']]);
			}

			// Update the contact relations
			if ($item['author-id'] != $parent['author-id']) {
				DBA::update('contact-relation', ['last-interaction' => $item['created']], ['cid' => $parent['author-id'], 'relation-cid' => $item['author-id']], true);
			}
		}

		return $item;
	}

	/**
	 * Get the gravity for the given item array
	 *
	 * @param array $item
	 * @return integer gravity
	 */
	private static function getGravity(array $item)
	{
		$activity = DI::activity();

		if (isset($item['gravity'])) {
			return intval($item['gravity']);
		} elseif ($item['parent-uri'] === $item['uri']) {
			return GRAVITY_PARENT;
		} elseif ($activity->match($item['verb'], Activity::POST)) {
			return GRAVITY_COMMENT;
		} elseif ($activity->match($item['verb'], Activity::FOLLOW)) {
			return GRAVITY_ACTIVITY;
		}
		Logger::info('Unknown gravity for verb', ['verb' => $item['verb']]);
		return GRAVITY_UNKNOWN;   // Should not happen
	}

	public static function insert($item, $notify = false, $dontcache = false)
	{
		$orig_item = $item;

		$priority = PRIORITY_HIGH;

		// If it is a posting where users should get notifications, then define it as wall posting
		if ($notify) {
			$item['wall'] = 1;
			$item['origin'] = 1;
			$item['network'] = Protocol::DFRN;
			$item['protocol'] = Conversation::PARCEL_DFRN;

			if (is_int($notify)) {
				$priority = $notify;
			}
		} else {
			$item['network'] = trim(($item['network'] ?? '') ?: Protocol::PHANTOM);
		}

		$uid = intval($item['uid']);

		$item['guid'] = self::guid($item, $notify);
		$item['uri'] = substr(Strings::escapeTags(trim(($item['uri'] ?? '') ?: self::newURI($item['uid'], $item['guid']))), 0, 255);

		// Store URI data
		$item['uri-id'] = ItemURI::insert(['uri' => $item['uri'], 'guid' => $item['guid']]);

		// Store conversation data
		$item = Conversation::insert($item);

		if (!empty($item['thr-parent'])) {
			$item['parent-uri'] = $item['thr-parent'];
		}

		/*
		 * Do we already have this item?
		 * We have to check several networks since Friendica posts could be repeated
		 * via OStatus (maybe Diasporsa as well)
		 */
		$duplicate = self::getDuplicateID($item);
		if ($duplicate) {
			return $duplicate;
		}

		// Additional duplicate checks
		/// @todo Check why the first duplication check returns the item number and the second a 0
		if (self::isDuplicate($item)) {
			return 0;
		}

		$item['wall']          = intval($item['wall'] ?? 0);
		$item['extid']         = trim($item['extid'] ?? '');
		$item['author-name']   = trim($item['author-name'] ?? '');
		$item['author-link']   = trim($item['author-link'] ?? '');
		$item['author-avatar'] = trim($item['author-avatar'] ?? '');
		$item['owner-name']    = trim($item['owner-name'] ?? '');
		$item['owner-link']    = trim($item['owner-link'] ?? '');
		$item['owner-avatar']  = trim($item['owner-avatar'] ?? '');
		$item['received']      = (isset($item['received'])  ? DateTimeFormat::utc($item['received'])  : DateTimeFormat::utcNow());
		$item['created']       = (isset($item['created'])   ? DateTimeFormat::utc($item['created'])   : $item['received']);
		$item['edited']        = (isset($item['edited'])    ? DateTimeFormat::utc($item['edited'])    : $item['created']);
		$item['changed']       = (isset($item['changed'])   ? DateTimeFormat::utc($item['changed'])   : $item['created']);
		$item['commented']     = (isset($item['commented']) ? DateTimeFormat::utc($item['commented']) : $item['created']);
		$item['title']         = substr(trim($item['title'] ?? ''), 0, 255);
		$item['location']      = trim($item['location'] ?? '');
		$item['coord']         = trim($item['coord'] ?? '');
		$item['visible']       = (isset($item['visible']) ? intval($item['visible']) : 1);
		$item['deleted']       = 0;
		$item['parent-uri']    = trim(($item['parent-uri'] ?? '') ?: $item['uri']);
		$item['post-type']     = ($item['post-type'] ?? '') ?: self::PT_ARTICLE;
		$item['verb']          = trim($item['verb'] ?? '');
		$item['object-type']   = trim($item['object-type'] ?? '');
		$item['object']        = trim($item['object'] ?? '');
		$item['target-type']   = trim($item['target-type'] ?? '');
		$item['target']        = trim($item['target'] ?? '');
		$item['plink']         = substr(trim($item['plink'] ?? ''), 0, 255);
		$item['allow_cid']     = trim($item['allow_cid'] ?? '');
		$item['allow_gid']     = trim($item['allow_gid'] ?? '');
		$item['deny_cid']      = trim($item['deny_cid'] ?? '');
		$item['deny_gid']      = trim($item['deny_gid'] ?? '');
		$item['private']       = intval($item['private'] ?? self::PUBLIC);
		$item['body']          = trim($item['body'] ?? '');
		$item['attach']        = trim($item['attach'] ?? '');
		$item['app']           = trim($item['app'] ?? '');
		$item['origin']        = intval($item['origin'] ?? 0);
		$item['postopts']      = trim($item['postopts'] ?? '');
		$item['resource-id']   = trim($item['resource-id'] ?? '');
		$item['event-id']      = intval($item['event-id'] ?? 0);
		$item['inform']        = trim($item['inform'] ?? '');
		$item['file']          = trim($item['file'] ?? '');

		// Items cannot be stored before they happen ...
		if ($item['created'] > DateTimeFormat::utcNow()) {
			$item['created'] = DateTimeFormat::utcNow();
		}

		// We haven't invented time travel by now.
		if ($item['edited'] > DateTimeFormat::utcNow()) {
			$item['edited'] = DateTimeFormat::utcNow();
		}

		$item['plink'] = ($item['plink'] ?? '') ?: DI::baseUrl() . '/display/' . urlencode($item['guid']);

		$item['language'] = self::getLanguage($item);

		$item['gravity'] = self::getGravity($item);

		$default = ['url' => $item['author-link'], 'name' => $item['author-name'],
			'photo' => $item['author-avatar'], 'network' => $item['network']];
		$item['author-id'] = ($item['author-id'] ?? 0) ?: Contact::getIdForURL($item['author-link'], 0, false, $default);

		$default = ['url' => $item['owner-link'], 'name' => $item['owner-name'],
			'photo' => $item['owner-avatar'], 'network' => $item['network']];
		$item['owner-id'] = ($item['owner-id'] ?? 0) ?: Contact::getIdForURL($item['owner-link'], 0, false, $default);

		// The contact-id should be set before "self::insert" was called - but there seems to be issues sometimes
		$item["contact-id"] = self::contactId($item);

		if (!self::isValid($item)) {
			return 0;
		}

		// We don't store the causer, we only have it here for the checks in the function above
		unset($item['causer-id']);
		unset($item['causer-link']);

		// We don't store these fields anymore in the item table
		unset($item['author-link']);
		unset($item['author-name']);
		unset($item['author-avatar']);
		unset($item['author-network']);

		unset($item['owner-link']);
		unset($item['owner-name']);
		unset($item['owner-avatar']);

		$item['thr-parent'] = $item['parent-uri'];

		if ($item['parent-uri'] != $item['uri']) {
			$item = self::getParentData($item);
			if (empty($item)) {
				return 0;
			}

			$parent_id = $item['parent'];
			unset($item['parent']);
			$parent_origin = $item['parent_origin'];
			unset($item['parent_origin']);
		} else {
			$parent_id = 0;
			$parent_origin = $item['origin'];
		}

		$item['parent-uri-id'] = ItemURI::getIdByURI($item['parent-uri']);
		$item['thr-parent-id'] = ItemURI::getIdByURI($item['thr-parent']);

		// Is this item available in the global items (with uid=0)?
		if ($item["uid"] == 0) {
			$item["global"] = true;

			// Set the global flag on all items if this was a global item entry
			DBA::update('item', ['global' => true], ['uri' => $item["uri"]]);
		} else {
			$item["global"] = self::exists(['uid' => 0, 'uri' => $item["uri"]]);
		}

		// ACL settings
		if (!empty($item["allow_cid"] . $item["allow_gid"] . $item["deny_cid"] . $item["deny_gid"])) {
			$item["private"] = self::PRIVATE;
		}

		if ($notify) {
			$item['edit'] = false;
			$item['parent'] = $parent_id;
			Hook::callAll('post_local', $item);
			unset($item['edit']);
			unset($item['parent']);
		} else {
			Hook::callAll('post_remote', $item);
		}

		if (!empty($item['cancel'])) {
			Logger::log('post cancelled by addon.');
			return 0;
		}

		if (empty($item['vid']) && !empty($item['verb'])) {
			$item['vid'] = Verb::getID($item['verb']);
		}

		// Creates or assigns the permission set
		$item['psid'] = PermissionSet::getIdFromACL(
			$item['uid'],
			$item['allow_cid'],
			$item['allow_gid'],
			$item['deny_cid'],
			$item['deny_gid']
		);

		unset($item['allow_cid']);
		unset($item['allow_gid']);
		unset($item['deny_cid']);
		unset($item['deny_gid']);

		// This array field is used to trigger some automatic reactions
		// It is mainly used in the "post_local" hook.
		unset($item['api_source']);


		// Check for hashtags in the body and repair or add hashtag links
		$item['body'] = self::setHashtags($item['body']);

		// Fill the cache field
		self::putInCache($item);

		if (stristr($item['verb'], Activity::POKE)) {
			$notify_type = Delivery::POKE;
		} else {
			$notify_type = Delivery::POST;
		}

		$like_no_comment = DI::config()->get('system', 'like_no_comment');

		DBA::transaction();

		if (!in_array($item['verb'], self::ACTIVITIES)) {
			$item['icid'] = self::insertContent($item);
		}

		$body = $item['body'];

		// We just remove everything that is content
		foreach (array_merge(self::CONTENT_FIELDLIST, self::MIXED_CONTENT_FIELDLIST) as $field) {
			unset($item[$field]);
		}

		unset($item['activity']);

		// Filling item related side tables

		// Diaspora signature
		if (!empty($item['diaspora_signed_text'])) {
			DBA::insert('diaspora-interaction', ['uri-id' => $item['uri-id'], 'interaction' => $item['diaspora_signed_text']], true);
		}

		unset($item['diaspora_signed_text']);

		// Attached file links
		if (!empty($item['file'])) {
			Category::storeTextByURIId($item['uri-id'], $item['uid'], $item['file']);
		}

		unset($item['file']);

		// Delivery relevant data
		$delivery_data = Post\DeliveryData::extractFields($item);
		unset($item['postopts']);
		unset($item['inform']);

		if (!empty($item['origin']) || !empty($item['wall']) || !empty($delivery_data['postopts']) || !empty($delivery_data['inform'])) {
			Post\DeliveryData::insert($item['uri-id'], $delivery_data);
		}

		// Store tags from the body if this hadn't been handled previously in the protocol classes
		if (!Tag::existsForPost($item['uri-id'])) {
			Tag::storeFromBody($item['uri-id'], $body);
		}

		$ret = DBA::insert('item', $item);

		// When the item was successfully stored we fetch the ID of the item.
		if (DBA::isResult($ret)) {
			$current_post = DBA::lastInsertId();
		} else {
			// This can happen - for example - if there are locking timeouts.
			DBA::rollback();

			// Store the data into a spool file so that we can try again later.
			self::spool($orig_item);
			return 0;
		}

		if ($current_post == 0) {
			// This is one of these error messages that never should occur.
			Logger::log("couldn't find created item - we better quit now.");
			DBA::rollback();
			return 0;
		}

		// How much entries have we created?
		// We wouldn't need this query when we could use an unique index - but MySQL has length problems with them.
		$entries = DBA::count('item', ['uri' => $item['uri'], 'uid' => $item['uid'], 'network' => $item['network']]);

		if ($entries > 1) {
			// There are duplicates. We delete our just created entry.
			Logger::info('Delete duplicated item', ['id' => $current_post, 'uri' => $item['uri'], 'uid' => $item['uid'], 'guid' => $item['guid']]);

			// Yes, we could do a rollback here - but we possibly are still having users with MyISAM.
			DBA::delete('item', ['id' => $current_post]);
			DBA::commit();
			return 0;
		} elseif ($entries == 0) {
			// This really should never happen since we quit earlier if there were problems.
			Logger::log("Something is terribly wrong. We haven't found our created entry.");
			DBA::rollback();
			return 0;
		}

		Logger::log('created item '.$current_post);

		if (!$parent_id || ($item['parent-uri'] === $item['uri'])) {
			$parent_id = $current_post;
		}

		// Set parent id
		DBA::update('item', ['parent' => $parent_id], ['id' => $current_post]);

		$item['id'] = $current_post;
		$item['parent'] = $parent_id;

		// update the commented timestamp on the parent
		// Only update "commented" if it is really a comment
		if (($item['gravity'] != GRAVITY_ACTIVITY) || !$like_no_comment) {
			DBA::update('item', ['commented' => DateTimeFormat::utcNow(), 'changed' => DateTimeFormat::utcNow()], ['id' => $parent_id]);
		} else {
			DBA::update('item', ['changed' => DateTimeFormat::utcNow()], ['id' => $parent_id]);
		}

		if ($item['parent-uri'] === $item['uri']) {
			self::addThread($current_post);
		} else {
			self::updateThread($parent_id);
		}
		DBA::commit();

		// In that function we check if this is a forum post. Additionally we delete the item under certain circumstances
		if (self::tagDeliver($item['uid'], $current_post)) {
			// Get the user information for the logging
			$user = User::getById($uid);

			Logger::notice('Item had been deleted', ['id' => $current_post, 'user' => $uid, 'account-type' => $user['account-type']]);
			return 0;
		}

		if (!$dontcache) {
			$posted_item = self::selectFirst(self::ITEM_FIELDLIST, ['id' => $current_post]);
			if (DBA::isResult($posted_item)) {
				if ($notify) {
					Hook::callAll('post_local_end', $posted_item);
				} else {
					Hook::callAll('post_remote_end', $posted_item);
				}
			} else {
				Logger::log('new item not found in DB, id ' . $current_post);
			}
		}

		if ($item['parent-uri'] === $item['uri']) {
			self::addShadow($current_post);
		} else {
			self::addShadowPost($current_post);
		}

		self::updateContact($item);

		UserItem::setNotification($current_post);

		check_user_notification($current_post);

		$transmit = $notify || ($item['visible'] && ($parent_origin || $item['origin']));

		if ($transmit) {
			$transmit_item = Item::selectFirst(['verb', 'origin'], ['id' => $item['id']]);
			// Don't relay participation messages
			if (($transmit_item['verb'] == Activity::FOLLOW) && 
				(!$transmit_item['origin'] || ($item['author-id'] != Contact::getPublicIdByUserId($uid)))) {
				Logger::info('Participation messages will not be relayed', ['item' => $item['id'], 'uri' => $item['uri'], 'verb' => $transmit_item['verb']]);
				$transmit = false;
			}
		}

		if ($transmit) {
			Worker::add(['priority' => $priority, 'dont_fork' => true], 'Notifier', $notify_type, $current_post);
		}

		return $current_post;
	}

	/**
	 * Insert a new item content entry
	 *
	 * @param array $item The item fields that are to be inserted
	 * @throws \Exception
	 */
	private static function insertContent(array $item)
	{
		$fields = ['uri-plink-hash' => (string)$item['uri-id'], 'uri-id' => $item['uri-id']];

		foreach (array_merge(self::CONTENT_FIELDLIST, self::MIXED_CONTENT_FIELDLIST) as $field) {
			if (isset($item[$field])) {
				$fields[$field] = $item[$field];
			}
		}

		$item_content = DBA::selectFirst('item-content', ['id'], ['uri-id' => $item['uri-id']]);
		if (DBA::isResult($item_content)) {
			$icid = $item_content['id'];
			Logger::info('Content found', ['icid' => $icid, 'uri' => $item['uri']]);
			return $icid;
		}

		DBA::insert('item-content', $fields, true);
		$icid = DBA::lastInsertId();
		if ($icid != 0) {
			Logger::info('Content inserted', ['icid' => $icid, 'uri' => $item['uri']]);
			return $icid;
		}

		// Possibly there can be timing issues. Then the same content could be inserted multiple times.
		// Due to the indexes this doesn't happen, but "lastInsertId" will be empty in these situations.
		// So we have to fetch the id manually. This is no bug and there is no data loss.
		$item_content = DBA::selectFirst('item-content', ['id'], ['uri-id' => $item['uri-id']]);
		if (DBA::isResult($item_content)) {
			$icid = $item_content['id'];
			Logger::notice('Content inserted with empty lastInsertId', ['icid' => $icid, 'uri' => $item['uri']]);
			return $icid;
		}

		// This shouldn't happen.
		Logger::error("Content wasn't inserted", $item);
		return null;
	}

	/**
	 * Update existing item content entries
	 *
	 * @param array $item      The item fields that are to be changed
	 * @param array $condition The condition for finding the item content entries
	 * @throws \Exception
	 */
	private static function updateContent($item, $condition)
	{
		// We have to select only the fields from the "item-content" table
		$fields = [];
		foreach (array_merge(self::CONTENT_FIELDLIST, self::MIXED_CONTENT_FIELDLIST) as $field) {
			if (isset($item[$field])) {
				$fields[$field] = $item[$field];
			}
		}

		if (empty($fields)) {
			return;
		}

		DBA::update('item-content', $fields, $condition, true);
		Logger::info('Updated content', ['condition' => $condition]);
	}

	/**
	 * Distributes public items to the receivers
	 *
	 * @param integer $itemid      Item ID that should be added
	 * @param string  $signed_text Original text (for Diaspora signatures), JSON encoded.
	 * @throws \Exception
	 */
	public static function distribute($itemid, $signed_text = '')
	{
		$condition = ["`id` IN (SELECT `parent` FROM `item` WHERE `id` = ?)", $itemid];
		$parent = self::selectFirst(['owner-id'], $condition);
		if (!DBA::isResult($parent)) {
			return;
		}

		// Only distribute public items from native networks
		$condition = ['id' => $itemid, 'uid' => 0,
			'network' => array_merge(Protocol::FEDERATED ,['']),
			'visible' => true, 'deleted' => false, 'moderated' => false, 'private' => [self::PUBLIC, self::UNLISTED]];
		$item = self::selectFirst(self::ITEM_FIELDLIST, $condition);
		if (!DBA::isResult($item)) {
			return;
		}

		$origin = $item['origin'];

		unset($item['id']);
		unset($item['parent']);
		unset($item['mention']);
		unset($item['wall']);
		unset($item['origin']);
		unset($item['starred']);

		$users = [];

		/// @todo add a field "pcid" in the contact table that referrs to the public contact id.
		$owner = DBA::selectFirst('contact', ['url', 'nurl', 'alias'], ['id' => $parent['owner-id']]);
		if (!DBA::isResult($owner)) {
			return;
		}

		$condition = ['nurl' => $owner['nurl'], 'rel' => [Contact::SHARING, Contact::FRIEND]];
		$contacts = DBA::select('contact', ['uid'], $condition);
		while ($contact = DBA::fetch($contacts)) {
			if ($contact['uid'] == 0) {
				continue;
			}

			$users[$contact['uid']] = $contact['uid'];
		}
		DBA::close($contacts);

		$condition = ['alias' => $owner['url'], 'rel' => [Contact::SHARING, Contact::FRIEND]];
		$contacts = DBA::select('contact', ['uid'], $condition);
		while ($contact = DBA::fetch($contacts)) {
			if ($contact['uid'] == 0) {
				continue;
			}

			$users[$contact['uid']] = $contact['uid'];
		}
		DBA::close($contacts);

		if (!empty($owner['alias'])) {
			$condition = ['url' => $owner['alias'], 'rel' => [Contact::SHARING, Contact::FRIEND]];
			$contacts = DBA::select('contact', ['uid'], $condition);
			while ($contact = DBA::fetch($contacts)) {
				if ($contact['uid'] == 0) {
					continue;
				}

				$users[$contact['uid']] = $contact['uid'];
			}
			DBA::close($contacts);
		}

		$origin_uid = 0;

		if ($item['uri'] != $item['parent-uri']) {
			$parents = self::select(['uid', 'origin'], ["`uri` = ? AND `uid` != 0", $item['parent-uri']]);
			while ($parent = self::fetch($parents)) {
				$users[$parent['uid']] = $parent['uid'];
				if ($parent['origin'] && !$origin) {
					$origin_uid = $parent['uid'];
				}
			}
		}

		foreach ($users as $uid) {
			if ($origin_uid == $uid) {
				$item['diaspora_signed_text'] = $signed_text;
			}
			self::storeForUser($itemid, $item, $uid);
		}
	}

	/**
	 * Store public items for the receivers
	 *
	 * @param integer $itemid Item ID that should be added
	 * @param array   $item   The item entry that will be stored
	 * @param integer $uid    The user that will receive the item entry
	 * @throws \Exception
	 */
	private static function storeForUser($itemid, $item, $uid)
	{
		$item['uid'] = $uid;
		$item['origin'] = 0;
		$item['wall'] = 0;
		if ($item['uri'] == $item['parent-uri']) {
			$item['contact-id'] = Contact::getIdForURL($item['owner-link'], $uid);
		} else {
			$item['contact-id'] = Contact::getIdForURL($item['author-link'], $uid);
		}

		if (empty($item['contact-id'])) {
			$self = DBA::selectFirst('contact', ['id'], ['self' => true, 'uid' => $uid]);
			if (!DBA::isResult($self)) {
				return;
			}
			$item['contact-id'] = $self['id'];
		}

		/// @todo Handling of "event-id"

		$notify = false;
		if ($item['uri'] == $item['parent-uri']) {
			$contact = DBA::selectFirst('contact', [], ['id' => $item['contact-id'], 'self' => false]);
			if (DBA::isResult($contact)) {
				$notify = self::isRemoteSelf($contact, $item);
			}
		}

		$distributed = self::insert($item, $notify, true);

		if (!$distributed) {
			Logger::info("Distributed public item wasn't stored", ['id' => $itemid, 'user' => $uid]);
		} else {
			Logger::info('Distributed public item was stored', ['id' => $itemid, 'user' => $uid, 'stored' => $distributed]);
		}
	}

	/**
	 * Add a shadow entry for a given item id that is a thread starter
	 *
	 * We store every public item entry additionally with the user id "0".
	 * This is used for the community page and for the search.
	 * It is planned that in the future we will store public item entries only once.
	 *
	 * @param integer $itemid Item ID that should be added
	 * @throws \Exception
	 */
	public static function addShadow($itemid)
	{
		$fields = ['uid', 'private', 'moderated', 'visible', 'deleted', 'network', 'uri'];
		$condition = ['id' => $itemid, 'parent' => [0, $itemid]];
		$item = self::selectFirst($fields, $condition);

		if (!DBA::isResult($item)) {
			return;
		}

		// is it already a copy?
		if (($itemid == 0) || ($item['uid'] == 0)) {
			return;
		}

		// Is it a visible public post?
		if (!$item["visible"] || $item["deleted"] || $item["moderated"] || ($item["private"] == Item::PRIVATE)) {
			return;
		}

		// is it an entry from a connector? Only add an entry for natively connected networks
		if (!in_array($item["network"], array_merge(Protocol::FEDERATED ,['']))) {
			return;
		}

		if (self::exists(['uri' => $item['uri'], 'uid' => 0])) {
			return;
		}

		$item = self::selectFirst(self::ITEM_FIELDLIST, ['id' => $itemid]);

		if (DBA::isResult($item)) {
			// Preparing public shadow (removing user specific data)
			$item['uid'] = 0;
			unset($item['id']);
			unset($item['parent']);
			unset($item['wall']);
			unset($item['mention']);
			unset($item['origin']);
			unset($item['starred']);
			unset($item['postopts']);
			unset($item['inform']);
			if ($item['uri'] == $item['parent-uri']) {
				$item['contact-id'] = $item['owner-id'];
			} else {
				$item['contact-id'] = $item['author-id'];
			}

			$public_shadow = self::insert($item, false, true);

			Logger::info('Stored public shadow', ['thread' => $itemid, 'id' => $public_shadow]);
		}
	}

	/**
	 * Add a shadow entry for a given item id that is a comment
	 *
	 * This function does the same like the function above - but for comments
	 *
	 * @param integer $itemid Item ID that should be added
	 * @throws \Exception
	 */
	public static function addShadowPost($itemid)
	{
		$item = self::selectFirst(self::ITEM_FIELDLIST, ['id' => $itemid]);
		if (!DBA::isResult($item)) {
			return;
		}

		// Is it a toplevel post?
		if ($item['gravity'] == GRAVITY_PARENT) {
			self::addShadow($itemid);
			return;
		}

		// Is this a shadow entry?
		if ($item['uid'] == 0) {
			return;
		}

		// Is there a shadow parent?
		if (!self::exists(['uri' => $item['parent-uri'], 'uid' => 0])) {
			return;
		}

		// Is there already a shadow entry?
		if (self::exists(['uri' => $item['uri'], 'uid' => 0])) {
			return;
		}

		// Save "origin" and "parent" state
		$origin = $item['origin'];
		$parent = $item['parent'];

		// Preparing public shadow (removing user specific data)
		$item['uid'] = 0;
		unset($item['id']);
		unset($item['parent']);
		unset($item['wall']);
		unset($item['mention']);
		unset($item['origin']);
		unset($item['starred']);
		unset($item['postopts']);
		unset($item['inform']);
		$item['contact-id'] = Contact::getIdForURL($item['author-link']);

		$public_shadow = self::insert($item, false, true);

		Logger::info('Stored public shadow', ['uri' => $item['uri'], 'id' => $public_shadow]);

		// If this was a comment to a Diaspora post we don't get our comment back.
		// This means that we have to distribute the comment by ourselves.
		if ($origin && self::exists(['id' => $parent, 'network' => Protocol::DIASPORA])) {
			self::distribute($public_shadow);
		}
	}

	/**
	 * Adds a language specification in a "language" element of given $arr.
	 * Expects "body" element to exist in $arr.
	 *
	 * @param array $item
	 * @return string detected language
	 * @throws \Text_LanguageDetect_Exception
	 */
	private static function getLanguage(array $item)
	{
		$naked_body = BBCode::toPlaintext($item['body'], false);

		$ld = new Text_LanguageDetect();
		$ld->setNameMode(2);
		$languages = $ld->detect($naked_body, 3);
		if (is_array($languages)) {
			return json_encode($languages);
		}

		return '';
	}

	/**
	 * Creates an unique guid out of a given uri
	 *
	 * @param string $uri uri of an item entry
	 * @param string $host hostname for the GUID prefix
	 * @return string unique guid
	 */
	public static function guidFromUri($uri, $host)
	{
		// Our regular guid routine is using this kind of prefix as well
		// We have to avoid that different routines could accidentally create the same value
		$parsed = parse_url($uri);

		// We use a hash of the hostname as prefix for the guid
		$guid_prefix = hash("crc32", $host);

		// Remove the scheme to make sure that "https" and "http" doesn't make a difference
		unset($parsed["scheme"]);

		// Glue it together to be able to make a hash from it
		$host_id = implode("/", $parsed);

		// We could use any hash algorithm since it isn't a security issue
		$host_hash = hash("ripemd128", $host_id);

		return $guid_prefix.$host_hash;
	}

	/**
	 * generate an unique URI
	 *
	 * @param integer $uid  User id
	 * @param string  $guid An existing GUID (Otherwise it will be generated)
	 *
	 * @return string
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function newURI($uid, $guid = "")
	{
		if ($guid == "") {
			$guid = System::createUUID();
		}

		return DI::baseUrl()->get() . '/objects/' . $guid;
	}

	/**
	 * Set "success_update" and "last-item" to the date of the last time we heard from this contact
	 *
	 * This can be used to filter for inactive contacts.
	 * Only do this for public postings to avoid privacy problems, since poco data is public.
	 * Don't set this value if it isn't from the owner (could be an author that we don't know)
	 *
	 * @param array $arr Contains the just posted item record
	 * @throws \Exception
	 */
	private static function updateContact($arr)
	{
		// Unarchive the author
		$contact = DBA::selectFirst('contact', [], ['id' => $arr["author-id"]]);
		if (DBA::isResult($contact)) {
			Contact::unmarkForArchival($contact);
		}

		// Unarchive the contact if it's not our own contact
		$contact = DBA::selectFirst('contact', [], ['id' => $arr["contact-id"], 'self' => false]);
		if (DBA::isResult($contact)) {
			Contact::unmarkForArchival($contact);
		}

		/// @todo On private posts we could obfuscate the date
		$update = ($arr['private'] != self::PRIVATE);

		// Is it a forum? Then we don't care about the rules from above
		if (!$update && in_array($arr["network"], [Protocol::ACTIVITYPUB, Protocol::DFRN]) && ($arr["parent-uri"] === $arr["uri"])) {
			if (DBA::exists('contact', ['id' => $arr['contact-id'], 'forum' => true])) {
				$update = true;
			}
		}

		if ($update) {
			// The "self" contact id is used (for example in the connectors) when the contact is unknown
			// So we have to ensure to only update the last item when it had been our own post,
			// or it had been done by a "regular" contact.
			if (!empty($arr['wall'])) {
				$condition = ['id' => $arr['contact-id']];
			} else { 
				$condition = ['id' => $arr['contact-id'], 'self' => false];
			}
			DBA::update('contact', ['success_update' => $arr['received'], 'last-item' => $arr['received']], $condition);
		}
		// Now do the same for the system wide contacts with uid=0
		if ($arr['private'] != self::PRIVATE) {
			DBA::update('contact', ['success_update' => $arr['received'], 'last-item' => $arr['received']],
				['id' => $arr['owner-id']]);

			if ($arr['owner-id'] != $arr['author-id']) {
				DBA::update('contact', ['success_update' => $arr['received'], 'last-item' => $arr['received']],
					['id' => $arr['author-id']]);
			}
		}
	}

	public static function setHashtags($body)
	{
		$body = BBCode::performWithEscapedTags($body, ['noparse', 'pre', 'code'], function ($body) {
			$tags = BBCode::getTags($body);

			// No hashtags?
			if (!count($tags)) {
				return $body;
			}

			// This sorting is important when there are hashtags that are part of other hashtags
			// Otherwise there could be problems with hashtags like #test and #test2
			// Because of this we are sorting from the longest to the shortest tag.
			usort($tags, function ($a, $b) {
				return strlen($b) <=> strlen($a);
			});

			$URLSearchString = "^\[\]";

			// All hashtags should point to the home server if "local_tags" is activated
			if (DI::config()->get('system', 'local_tags')) {
				$body = preg_replace("/#\[url\=([$URLSearchString]*)\](.*?)\[\/url\]/ism",
					"#[url=" . DI::baseUrl() . "/search?tag=$2]$2[/url]", $body);
			}

			// mask hashtags inside of url, bookmarks and attachments to avoid urls in urls
			$body = preg_replace_callback("/\[url\=([$URLSearchString]*)\](.*?)\[\/url\]/ism",
				function ($match) {
					return ("[url=" . str_replace("#", "&num;", $match[1]) . "]" . str_replace("#", "&num;", $match[2]) . "[/url]");
				}, $body);

			$body = preg_replace_callback("/\[bookmark\=([$URLSearchString]*)\](.*?)\[\/bookmark\]/ism",
				function ($match) {
					return ("[bookmark=" . str_replace("#", "&num;", $match[1]) . "]" . str_replace("#", "&num;", $match[2]) . "[/bookmark]");
				}, $body);

			$body = preg_replace_callback("/\[attachment (.*)\](.*?)\[\/attachment\]/ism",
				function ($match) {
					return ("[attachment " . str_replace("#", "&num;", $match[1]) . "]" . $match[2] . "[/attachment]");
				}, $body);

			// Repair recursive urls
			$body = preg_replace("/&num;\[url\=([$URLSearchString]*)\](.*?)\[\/url\]/ism",
				"&num;$2", $body);

			foreach ($tags as $tag) {
				if ((strpos($tag, '#') !== 0) || strpos($tag, '[url=') || strlen($tag) < 2 || $tag[1] == '#') {
					continue;
				}

				$basetag = str_replace('_', ' ', substr($tag, 1));
				$newtag = '#[url=' . DI::baseUrl() . '/search?tag=' . $basetag . ']' . $basetag . '[/url]';

				$body = str_replace($tag, $newtag, $body);
			}

			// Convert back the masked hashtags
			$body = str_replace("&num;", "#", $body);

			return $body;
		});

		return $body;
	}

	/**
	 * look for mention tags and setup a second delivery chain for forum/community posts if appropriate
	 *
	 * @param int $uid
	 * @param int $item_id
	 * @return boolean true if item was deleted, else false
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	private static function tagDeliver($uid, $item_id)
	{
		$mention = false;

		$user = DBA::selectFirst('user', [], ['uid' => $uid]);
		if (!DBA::isResult($user)) {
			return false;
		}

		$community_page = (($user['page-flags'] == User::PAGE_FLAGS_COMMUNITY) ? true : false);
		$prvgroup = (($user['page-flags'] == User::PAGE_FLAGS_PRVGROUP) ? true : false);

		$item = self::selectFirst(self::ITEM_FIELDLIST, ['id' => $item_id]);
		if (!DBA::isResult($item)) {
			return false;
		}

		$link = Strings::normaliseLink(DI::baseUrl() . '/profile/' . $user['nickname']);

		/*
		 * Diaspora uses their own hardwired link URL in @-tags
		 * instead of the one we supply with webfinger
		 */
		$dlink = Strings::normaliseLink(DI::baseUrl() . '/u/' . $user['nickname']);

		$cnt = preg_match_all('/[\@\!]\[url\=(.*?)\](.*?)\[\/url\]/ism', $item['body'], $matches, PREG_SET_ORDER);
		if ($cnt) {
			foreach ($matches as $mtch) {
				if (Strings::compareLink($link, $mtch[1]) || Strings::compareLink($dlink, $mtch[1])) {
					$mention = true;
					Logger::log('mention found: ' . $mtch[2]);
				}
			}
		}

		if (!$mention) {
			if (($community_page || $prvgroup) &&
				  !$item['wall'] && !$item['origin'] && ($item['gravity'] == GRAVITY_PARENT)) {
				Logger::info('Delete private group/communiy top-level item without mention', ['id' => $item_id, 'guid'=> $item['guid']]);
				DBA::delete('item', ['id' => $item_id]);
				return true;
			}
			return false;
		}

		$arr = ['item' => $item, 'user' => $user];

		Hook::callAll('tagged', $arr);

		if (!$community_page && !$prvgroup) {
			return false;
		}

		/*
		 * tgroup delivery - setup a second delivery chain
		 * prevent delivery looping - only proceed
		 * if the message originated elsewhere and is a top-level post
		 */
		if ($item['wall'] || $item['origin'] || ($item['id'] != $item['parent'])) {
			return false;
		}

		// now change this copy of the post to a forum head message and deliver to all the tgroup members
		$self = DBA::selectFirst('contact', ['id', 'name', 'url', 'thumb'], ['uid' => $uid, 'self' => true]);
		if (!DBA::isResult($self)) {
			return false;
		}

		$owner_id = Contact::getIdForURL($self['url']);

		// also reset all the privacy bits to the forum default permissions

		$private = ($user['allow_cid'] || $user['allow_gid'] || $user['deny_cid'] || $user['deny_gid']) ? self::PRIVATE : self::PUBLIC;

		$psid = PermissionSet::getIdFromACL(
			$user['uid'],
			$user['allow_cid'],
			$user['allow_gid'],
			$user['deny_cid'],
			$user['deny_gid']
		);

		$forum_mode = ($prvgroup ? 2 : 1);

		$fields = ['wall' => true, 'origin' => true, 'forum_mode' => $forum_mode, 'contact-id' => $self['id'],
			'owner-id' => $owner_id, 'private' => $private, 'psid' => $psid];
		self::update($fields, ['id' => $item_id]);

		self::updateThread($item_id);

		Worker::add(['priority' => PRIORITY_HIGH, 'dont_fork' => true], 'Notifier', Delivery::POST, $item_id);

		return false;
	}

	public static function isRemoteSelf($contact, &$datarray)
	{
		if (!$contact['remote_self']) {
			return false;
		}

		// Prevent the forwarding of posts that are forwarded
		if (!empty($datarray["extid"]) && ($datarray["extid"] == Protocol::DFRN)) {
			Logger::info('Already forwarded');
			return false;
		}

		// Prevent to forward already forwarded posts
		if ($datarray["app"] == DI::baseUrl()->getHostname()) {
			Logger::info('Already forwarded (second test)');
			return false;
		}

		// Only forward posts
		if ($datarray["verb"] != Activity::POST) {
			Logger::info('No post');
			return false;
		}

		if (($contact['network'] != Protocol::FEED) && ($datarray['private'] == self::PRIVATE)) {
			Logger::info('Not public');
			return false;
		}

		$datarray2 = $datarray;
		Logger::info('remote-self start', ['contact' => $contact['url'], 'remote_self'=> $contact['remote_self'], 'item' => $datarray]);
		if ($contact['remote_self'] == 2) {
			$self = DBA::selectFirst('contact', ['id', 'name', 'url', 'thumb'],
					['uid' => $contact['uid'], 'self' => true]);
			if (DBA::isResult($self)) {
				$datarray['contact-id'] = $self["id"];

				$datarray['owner-name'] = $self["name"];
				$datarray['owner-link'] = $self["url"];
				$datarray['owner-avatar'] = $self["thumb"];

				$datarray['author-name']   = $datarray['owner-name'];
				$datarray['author-link']   = $datarray['owner-link'];
				$datarray['author-avatar'] = $datarray['owner-avatar'];

				unset($datarray['edited']);

				unset($datarray['network']);
				unset($datarray['owner-id']);
				unset($datarray['author-id']);
			}

			if ($contact['network'] != Protocol::FEED) {
				$datarray["guid"] = System::createUUID();
				unset($datarray["plink"]);
				$datarray["uri"] = self::newURI($contact['uid'], $datarray["guid"]);
				$datarray["parent-uri"] = $datarray["uri"];
				$datarray["thr-parent"] = $datarray["uri"];
				$datarray["extid"] = Protocol::DFRN;
				$urlpart = parse_url($datarray2['author-link']);
				$datarray["app"] = $urlpart["host"];
			} else {
				$datarray['private'] = self::PUBLIC;
			}
		}

		if ($contact['network'] != Protocol::FEED) {
			// Store the original post
			$result = self::insert($datarray2);
			Logger::info('remote-self post original item', ['contact' => $contact['url'], 'result'=> $result, 'item' => $datarray2]);
		} else {
			$datarray["app"] = "Feed";
			$result = true;
		}

		// Trigger automatic reactions for addons
		$datarray['api_source'] = true;

		// We have to tell the hooks who we are - this really should be improved
		$_SESSION["authenticated"] = true;
		$_SESSION["uid"] = $contact['uid'];

		return $result;
	}

	/**
	 *
	 * @param string $s
	 * @param int    $uid
	 * @param array  $item
	 * @param int    $cid
	 * @return string
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function fixPrivatePhotos($s, $uid, $item = null, $cid = 0)
	{
		if (DI::config()->get('system', 'disable_embedded')) {
			return $s;
		}

		Logger::info('check for photos');
		$site = substr(DI::baseUrl(), strpos(DI::baseUrl(), '://'));

		$orig_body = $s;
		$new_body = '';

		$img_start = strpos($orig_body, '[img');
		$img_st_close = ($img_start !== false ? strpos(substr($orig_body, $img_start), ']') : false);
		$img_len = ($img_start !== false ? strpos(substr($orig_body, $img_start + $img_st_close + 1), '[/img]') : false);

		while (($img_st_close !== false) && ($img_len !== false)) {
			$img_st_close++; // make it point to AFTER the closing bracket
			$image = substr($orig_body, $img_start + $img_st_close, $img_len);

			Logger::info('found photo', ['image' => $image]);

			if (stristr($image, $site . '/photo/')) {
				// Only embed locally hosted photos
				$replace = false;
				$i = basename($image);
				$i = str_replace(['.jpg', '.png', '.gif'], ['', '', ''], $i);
				$x = strpos($i, '-');

				if ($x) {
					$res = substr($i, $x + 1);
					$i = substr($i, 0, $x);
					$photo = Photo::getPhotoForUser($uid, $i, $res);
					if (DBA::isResult($photo)) {
						/*
						 * Check to see if we should replace this photo link with an embedded image
						 * 1. No need to do so if the photo is public
						 * 2. If there's a contact-id provided, see if they're in the access list
						 *    for the photo. If so, embed it.
						 * 3. Otherwise, if we have an item, see if the item permissions match the photo
						 *    permissions, regardless of order but first check to see if they're an exact
						 *    match to save some processing overhead.
						 */
						if (self::hasPermissions($photo)) {
							if ($cid) {
								$recips = self::enumeratePermissions($photo);
								if (in_array($cid, $recips)) {
									$replace = true;
								}
							} elseif ($item) {
								if (self::samePermissions($uid, $item, $photo)) {
									$replace = true;
								}
							}
						}
						if ($replace) {
							$photo_img = Photo::getImageForPhoto($photo);
							// If a custom width and height were specified, apply before embedding
							if (preg_match("/\[img\=([0-9]*)x([0-9]*)\]/is", substr($orig_body, $img_start, $img_st_close), $match)) {
								Logger::info('scaling photo');

								$width = intval($match[1]);
								$height = intval($match[2]);

								$photo_img->scaleDown(max($width, $height));
							}

							$data = $photo_img->asString();
							$type = $photo_img->getType();

							Logger::info('replacing photo');
							$image = 'data:' . $type . ';base64,' . base64_encode($data);
							Logger::debug('replaced', ['image' => $image]);
						}
					}
				}
			}

			$new_body = $new_body . substr($orig_body, 0, $img_start + $img_st_close) . $image . '[/img]';
			$orig_body = substr($orig_body, $img_start + $img_st_close + $img_len + strlen('[/img]'));
			if ($orig_body === false) {
				$orig_body = '';
			}

			$img_start = strpos($orig_body, '[img');
			$img_st_close = ($img_start !== false ? strpos(substr($orig_body, $img_start), ']') : false);
			$img_len = ($img_start !== false ? strpos(substr($orig_body, $img_start + $img_st_close + 1), '[/img]') : false);
		}

		$new_body = $new_body . $orig_body;

		return $new_body;
	}

	private static function hasPermissions($obj)
	{
		return !empty($obj['allow_cid']) || !empty($obj['allow_gid']) ||
			!empty($obj['deny_cid']) || !empty($obj['deny_gid']);
	}

	private static function samePermissions($uid, $obj1, $obj2)
	{
		// first part is easy. Check that these are exactly the same.
		if (($obj1['allow_cid'] == $obj2['allow_cid'])
			&& ($obj1['allow_gid'] == $obj2['allow_gid'])
			&& ($obj1['deny_cid'] == $obj2['deny_cid'])
			&& ($obj1['deny_gid'] == $obj2['deny_gid'])) {
			return true;
		}

		// This is harder. Parse all the permissions and compare the resulting set.
		$recipients1 = self::enumeratePermissions($obj1);
		$recipients2 = self::enumeratePermissions($obj2);
		sort($recipients1);
		sort($recipients2);

		/// @TODO Comparison of arrays, maybe use array_diff_assoc() here?
		return ($recipients1 == $recipients2);
	}

	/**
	 * Returns an array of contact-ids that are allowed to see this object
	 *
	 * @param array $obj        Item array with at least uid, allow_cid, allow_gid, deny_cid and deny_gid
	 * @param bool  $check_dead Prunes unavailable contacts from the result
	 * @return array
	 * @throws \Exception
	 */
	public static function enumeratePermissions(array $obj, bool $check_dead = false)
	{
		$aclFormater = DI::aclFormatter();

		$allow_people = $aclFormater->expand($obj['allow_cid']);
		$allow_groups = Group::expand($obj['uid'], $aclFormater->expand($obj['allow_gid']), $check_dead);
		$deny_people  = $aclFormater->expand($obj['deny_cid']);
		$deny_groups  = Group::expand($obj['uid'], $aclFormater->expand($obj['deny_gid']), $check_dead);
		$recipients   = array_unique(array_merge($allow_people, $allow_groups));
		$deny         = array_unique(array_merge($deny_people, $deny_groups));
		$recipients   = array_diff($recipients, $deny);
		return $recipients;
	}

	public static function expire($uid, $days, $network = "", $force = false)
	{
		if (!$uid || ($days < 1)) {
			return;
		}

		$condition = ["`uid` = ? AND NOT `deleted` AND `gravity` = ?",
			$uid, GRAVITY_PARENT];

		/*
		 * $expire_network_only = save your own wall posts
		 * and just expire conversations started by others
		 */
		$expire_network_only = DI::pConfig()->get($uid, 'expire', 'network_only', false);

		if ($expire_network_only) {
			$condition[0] .= " AND NOT `wall`";
		}

		if ($network != "") {
			$condition[0] .= " AND `network` = ?";
			$condition[] = $network;
		}

		$condition[0] .= " AND `received` < UTC_TIMESTAMP() - INTERVAL ? DAY";
		$condition[] = $days;

		$items = self::select(['file', 'resource-id', 'starred', 'type', 'id', 'post-type'], $condition);

		if (!DBA::isResult($items)) {
			return;
		}

		$expire_items = DI::pConfig()->get($uid, 'expire', 'items', true);

		// Forcing expiring of items - but not notes and marked items
		if ($force) {
			$expire_items = true;
		}

		$expire_notes = DI::pConfig()->get($uid, 'expire', 'notes', true);
		$expire_starred = DI::pConfig()->get($uid, 'expire', 'starred', true);
		$expire_photos = DI::pConfig()->get($uid, 'expire', 'photos', false);

		$expired = 0;

		while ($item = Item::fetch($items)) {
			// don't expire filed items

			if (strpos($item['file'], '[') !== false) {
				continue;
			}

			// Only expire posts, not photos and photo comments

			if (!$expire_photos && strlen($item['resource-id'])) {
				continue;
			} elseif (!$expire_starred && intval($item['starred'])) {
				continue;
			} elseif (!$expire_notes && (($item['type'] == 'note') || ($item['post-type'] == Item::PT_PERSONAL_NOTE))) {
				continue;
			} elseif (!$expire_items && ($item['type'] != 'note') && ($item['post-type'] != Item::PT_PERSONAL_NOTE)) {
				continue;
			}

			self::markForDeletionById($item['id'], PRIORITY_LOW);

			++$expired;
		}
		DBA::close($items);
		Logger::log('User ' . $uid . ": expired $expired items; expire items: $expire_items, expire notes: $expire_notes, expire starred: $expire_starred, expire photos: $expire_photos");
	}

	public static function firstPostDate($uid, $wall = false)
	{
		$condition = ['uid' => $uid, 'wall' => $wall, 'deleted' => false, 'visible' => true, 'moderated' => false];
		$params = ['order' => ['received' => false]];
		$thread = DBA::selectFirst('thread', ['received'], $condition, $params);
		if (DBA::isResult($thread)) {
			return substr(DateTimeFormat::local($thread['received']), 0, 10);
		}
		return false;
	}

	/**
	 * add/remove activity to an item
	 *
	 * Toggle activities as like,dislike,attend of an item
	 *
	 * @param string $item_id
	 * @param string $verb
	 *            Activity verb. One of
	 *            like, unlike, dislike, undislike, attendyes, unattendyes,
	 *            attendno, unattendno, attendmaybe, unattendmaybe
	 * @return bool
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 * @hook  'post_local_end'
	 *            array $arr
	 *            'post_id' => ID of posted item
	 */
	public static function performActivity($item_id, $verb)
	{
		if (!Session::isAuthenticated()) {
			return false;
		}

		Logger::log('like: verb ' . $verb . ' item ' . $item_id);

		$item = self::selectFirst(self::ITEM_FIELDLIST, ['`id` = ? OR `uri` = ?', $item_id, $item_id]);
		if (!DBA::isResult($item)) {
			Logger::log('like: unknown item ' . $item_id);
			return false;
		}

		$item_uri = $item['uri'];

		$uid = $item['uid'];
		if (($uid == 0) && local_user()) {
			$uid = local_user();
		}

		if (!Security::canWriteToUserWall($uid)) {
			Logger::log('like: unable to write on wall ' . $uid);
			return false;
		}

		// Retrieves the local post owner
		$owner_self_contact = DBA::selectFirst('contact', [], ['uid' => $uid, 'self' => true]);
		if (!DBA::isResult($owner_self_contact)) {
			Logger::log('like: unknown owner ' . $uid);
			return false;
		}

		// Retrieve the current logged in user's public contact
		$author_id = public_contact();

		$author_contact = DBA::selectFirst('contact', ['url'], ['id' => $author_id]);
		if (!DBA::isResult($author_contact)) {
			Logger::log('like: unknown author ' . $author_id);
			return false;
		}

		// Contact-id is the uid-dependant author contact
		if (local_user() == $uid) {
			$item_contact_id = $owner_self_contact['id'];
		} else {
			$item_contact_id = Contact::getIdForURL($author_contact['url'], $uid, true);
			$item_contact = DBA::selectFirst('contact', [], ['id' => $item_contact_id]);
			if (!DBA::isResult($item_contact)) {
				Logger::log('like: unknown item contact ' . $item_contact_id);
				return false;
			}
		}

		$activity = null;
		switch ($verb) {
			case 'like':
			case 'unlike':
				$activity = Activity::LIKE;
				break;
			case 'dislike':
			case 'undislike':
				$activity = Activity::DISLIKE;
				break;
			case 'attendyes':
			case 'unattendyes':
				$activity = Activity::ATTEND;
				break;
			case 'attendno':
			case 'unattendno':
				$activity = Activity::ATTENDNO;
				break;
			case 'attendmaybe':
			case 'unattendmaybe':
				$activity = Activity::ATTENDMAYBE;
				break;
			case 'follow':
			case 'unfollow':
				$activity = Activity::FOLLOW;
				break;
			default:
				Logger::log('like: unknown verb ' . $verb . ' for item ' . $item_id);
				return false;
		}

		$mode = Strings::startsWith($verb, 'un') ? 'delete' : 'create';

		// Enable activity toggling instead of on/off
		$event_verb_flag = $activity === Activity::ATTEND || $activity === Activity::ATTENDNO || $activity === Activity::ATTENDMAYBE;

		// Look for an existing verb row
		// Event participation activities are mutually exclusive, only one of them can exist at all times.
		if ($event_verb_flag) {
			$verbs = [Activity::ATTEND, Activity::ATTENDNO, Activity::ATTENDMAYBE];

			// Translate to the index based activity index
			$vids = [];
			foreach ($verbs as $verb) {
				$vids[] = Verb::getID($verb);
			}
		} else {
			$vids = Verb::getID($activity);
		}

		$condition = ['vid' => $vids, 'deleted' => false, 'gravity' => GRAVITY_ACTIVITY,
			'author-id' => $author_id, 'uid' => $item['uid'], 'thr-parent' => $item_uri];
		$like_item = self::selectFirst(['id', 'guid', 'verb'], $condition);

		if (DBA::isResult($like_item)) {
			/**
			 * Truth table for existing activities
			 *
			 * |          Inputs            ||      Outputs      |
			 * |----------------------------||-------------------|
			 * |  Mode  | Event | Same verb || Delete? | Return? |
			 * |--------|-------|-----------||---------|---------|
			 * | create |  Yes  |    Yes    ||   No    |   Yes   |
			 * | create |  Yes  |    No     ||   Yes   |   No    |
			 * | create |  No   |    Yes    ||   No    |   Yes   |
			 * | create |  No   |    No     ||        N/A†       |
			 * | delete |  Yes  |    Yes    ||   Yes   |   N/A‡  |
			 * | delete |  Yes  |    No     ||   No    |   N/A‡  |
			 * | delete |  No   |    Yes    ||   Yes   |   N/A‡  |
			 * | delete |  No   |    No     ||        N/A†       |
			 * |--------|-------|-----------||---------|---------|
			 * |   A    |   B   |     C     || A xor C | !B or C |
			 *
			 * † Can't happen: It's impossible to find an existing non-event activity without
			 *                 the same verb because we are only looking for this single verb.
			 *
			 * ‡ The "mode = delete" is returning early whether an existing activity was found or not.
			 */
			if ($mode == 'create' xor $like_item['verb'] == $activity) {
				self::markForDeletionById($like_item['id']);
			}

			if (!$event_verb_flag || $like_item['verb'] == $activity) {
				return true;
			}
		}

		// No need to go further if we aren't creating anything
		if ($mode == 'delete') {
			return true;
		}

		$objtype = $item['resource-id'] ? Activity\ObjectType::IMAGE : Activity\ObjectType::NOTE;

		$new_item = [
			'guid'          => System::createUUID(),
			'uri'           => self::newURI($item['uid']),
			'uid'           => $item['uid'],
			'contact-id'    => $item_contact_id,
			'wall'          => $item['wall'],
			'origin'        => 1,
			'network'       => Protocol::DFRN,
			'gravity'       => GRAVITY_ACTIVITY,
			'parent'        => $item['id'],
			'parent-uri'    => $item['uri'],
			'thr-parent'    => $item['uri'],
			'owner-id'      => $author_id,
			'author-id'     => $author_id,
			'body'          => $activity,
			'verb'          => $activity,
			'object-type'   => $objtype,
			'allow_cid'     => $item['allow_cid'],
			'allow_gid'     => $item['allow_gid'],
			'deny_cid'      => $item['deny_cid'],
			'deny_gid'      => $item['deny_gid'],
			'visible'       => 1,
			'unseen'        => 1,
		];

		$signed = Diaspora::createLikeSignature($uid, $new_item);
		if (!empty($signed)) {
			$new_item['diaspora_signed_text'] = json_encode($signed);
		}

		$new_item_id = self::insert($new_item);

		// If the parent item isn't visible then set it to visible
		if (!$item['visible']) {
			self::update(['visible' => true], ['id' => $item['id']]);
		}

		$new_item['id'] = $new_item_id;

		Hook::callAll('post_local_end', $new_item);

		return true;
	}

	private static function addThread($itemid, $onlyshadow = false)
	{
		$fields = ['uid', 'created', 'edited', 'commented', 'received', 'changed', 'wall', 'private', 'pubmail',
			'moderated', 'visible', 'starred', 'contact-id', 'post-type', 'uri-id',
			'deleted', 'origin', 'forum_mode', 'mention', 'network', 'author-id', 'owner-id'];
		$condition = ["`id` = ? AND (`parent` = ? OR `parent` = 0)", $itemid, $itemid];
		$item = self::selectFirst($fields, $condition);

		if (!DBA::isResult($item)) {
			return;
		}

		$item['iid'] = $itemid;

		if (!$onlyshadow) {
			$result = DBA::insert('thread', $item);

			Logger::info('Add thread', ['item' => $itemid, 'result' => $result]);
		}
	}

	private static function updateThread($itemid, $setmention = false)
	{
		$fields = ['uid', 'guid', 'created', 'edited', 'commented', 'received', 'changed', 'post-type',
			'wall', 'private', 'pubmail', 'moderated', 'visible', 'starred', 'contact-id', 'uri-id',
			'deleted', 'origin', 'forum_mode', 'network', 'author-id', 'owner-id'];
		$condition = ["`id` = ? AND (`parent` = ? OR `parent` = 0)", $itemid, $itemid];

		$item = self::selectFirst($fields, $condition);
		if (!DBA::isResult($item)) {
			return;
		}

		if ($setmention) {
			$item["mention"] = 1;
		}

		$fields = [];

		foreach ($item as $field => $data) {
			if (!in_array($field, ["guid"])) {
				$fields[$field] = $data;
			}
		}

		$result = DBA::update('thread', $fields, ['iid' => $itemid]);

		Logger::info('Update thread', ['item' => $itemid, 'guid' => $item["guid"], 'result' => $result]);
	}

	private static function deleteThread($itemid, $itemuri = "")
	{
		$item = DBA::selectFirst('thread', ['uid'], ['iid' => $itemid]);
		if (!DBA::isResult($item)) {
			Logger::info('No thread found', ['id' => $itemid]);
			return;
		}

		$result = DBA::delete('thread', ['iid' => $itemid], ['cascade' => false]);

		Logger::info('Deleted thread', ['item' => $itemid, 'result' => $result]);

		if ($itemuri != "") {
			$condition = ["`uri` = ? AND NOT `deleted` AND NOT (`uid` IN (?, 0))", $itemuri, $item["uid"]];
			if (!self::exists($condition)) {
				DBA::delete('item', ['uri' => $itemuri, 'uid' => 0]);
				Logger::debug('Deleted shadow item', ['id' => $itemid, 'uri' => $itemuri]);
			}
		}
	}

	public static function getPermissionsSQLByUserId($owner_id)
	{
		$local_user = local_user();
		$remote_user = Session::getRemoteContactID($owner_id);

		/*
		 * Construct permissions
		 *
		 * default permissions - anonymous user
		 */
		$sql = sprintf(" AND `item`.`private` != %d", self::PRIVATE);

		// Profile owner - everything is visible
		if ($local_user && ($local_user == $owner_id)) {
			$sql = '';
		} elseif ($remote_user) {
			/*
			 * Authenticated visitor. Unless pre-verified,
			 * check that the contact belongs to this $owner_id
			 * and load the groups the visitor belongs to.
			 * If pre-verified, the caller is expected to have already
			 * done this and passed the groups into this function.
			 */
			$set = PermissionSet::get($owner_id, $remote_user);

			if (!empty($set)) {
				$sql_set = sprintf(" OR (`item`.`private` = %d AND `item`.`wall` AND `item`.`psid` IN (", self::PRIVATE) . implode(',', $set) . "))";
			} else {
				$sql_set = '';
			}

			$sql = sprintf(" AND (`item`.`private` != %d", self::PRIVATE) . $sql_set . ")";
		}

		return $sql;
	}

	/**
	 * get translated item type
	 *
	 * @param $item
	 * @return string
	 */
	public static function postType($item)
	{
		if (!empty($item['event-id'])) {
			return DI::l10n()->t('event');
		} elseif (!empty($item['resource-id'])) {
			return DI::l10n()->t('photo');
		} elseif ($item['gravity'] == GRAVITY_ACTIVITY) {
			return DI::l10n()->t('activity');
		} elseif ($item['gravity'] == GRAVITY_COMMENT) {
			return DI::l10n()->t('comment');
		}

		return DI::l10n()->t('post');
	}

	/**
	 * Sets the "rendered-html" field of the provided item
	 *
	 * Body is preserved to avoid side-effects as we modify it just-in-time for spoilers and private image links
	 *
	 * @param array $item
	 * @param bool  $update
	 *
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @todo Remove reference, simply return "rendered-html" and "rendered-hash"
	 */
	public static function putInCache(&$item, $update = false)
	{
		$body = $item["body"];

		$rendered_hash = $item['rendered-hash'] ?? '';
		$rendered_html = $item['rendered-html'] ?? '';

		if ($rendered_hash == ''
			|| $rendered_html == ""
			|| $rendered_hash != hash("md5", $item["body"])
			|| DI::config()->get("system", "ignore_cache")
		) {
			self::addRedirToImageTags($item);

			$item["rendered-html"] = BBCode::convert($item["body"]);
			$item["rendered-hash"] = hash("md5", $item["body"]);

			$hook_data = ['item' => $item, 'rendered-html' => $item['rendered-html'], 'rendered-hash' => $item['rendered-hash']];
			Hook::callAll('put_item_in_cache', $hook_data);
			$item['rendered-html'] = $hook_data['rendered-html'];
			$item['rendered-hash'] = $hook_data['rendered-hash'];
			unset($hook_data);

			// Force an update if the generated values differ from the existing ones
			if ($rendered_hash != $item["rendered-hash"]) {
				$update = true;
			}

			// Only compare the HTML when we forcefully ignore the cache
			if (DI::config()->get("system", "ignore_cache") && ($rendered_html != $item["rendered-html"])) {
				$update = true;
			}

			if ($update && !empty($item["id"])) {
				self::update(
					[
						'rendered-html' => $item["rendered-html"],
						'rendered-hash' => $item["rendered-hash"]
					],
					['id' => $item["id"]]
				);
			}
		}

		$item["body"] = $body;
	}

	/**
	 * Find any non-embedded images in private items and add redir links to them
	 *
	 * @param array &$item The field array of an item row
	 */
	private static function addRedirToImageTags(array &$item)
	{
		$app = DI::app();

		$matches = [];
		$cnt = preg_match_all('|\[img\](http[^\[]*?/photo/[a-fA-F0-9]+?(-[0-9]\.[\w]+?)?)\[\/img\]|', $item['body'], $matches, PREG_SET_ORDER);
		if ($cnt) {
			foreach ($matches as $mtch) {
				if (strpos($mtch[1], '/redir') !== false) {
					continue;
				}

				if ((local_user() == $item['uid']) && ($item['private'] == self::PRIVATE) && ($item['contact-id'] != $app->contact['id']) && ($item['network'] == Protocol::DFRN)) {
					$img_url = 'redir/' . $item['contact-id'] . '?url=' . urlencode($mtch[1]);
					$item['body'] = str_replace($mtch[0], '[img]' . $img_url . '[/img]', $item['body']);
				}
			}
		}
	}

	/**
	 * Given an item array, convert the body element from bbcode to html and add smilie icons.
	 * If attach is true, also add icons for item attachments.
	 *
	 * @param array   $item
	 * @param boolean $attach
	 * @param boolean $is_preview
	 * @return string item body html
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 * @hook  prepare_body_init item array before any work
	 * @hook  prepare_body_content_filter ('item'=>item array, 'filter_reasons'=>string array) before first bbcode to html
	 * @hook  prepare_body ('item'=>item array, 'html'=>body string, 'is_preview'=>boolean, 'filter_reasons'=>string array) after first bbcode to html
	 * @hook  prepare_body_final ('item'=>item array, 'html'=>body string) after attach icons and blockquote special case handling (spoiler, author)
	 */
	public static function prepareBody(array &$item, $attach = false, $is_preview = false)
	{
		$a = DI::app();
		Hook::callAll('prepare_body_init', $item);

		// In order to provide theme developers more possibilities, event items
		// are treated differently.
		if ($item['object-type'] === Activity\ObjectType::EVENT && isset($item['event-id'])) {
			$ev = Event::getItemHTML($item);
			return $ev;
		}

		$tags = Tag::populateFromItem($item);

		$item['tags'] = $tags['tags'];
		$item['hashtags'] = $tags['hashtags'];
		$item['mentions'] = $tags['mentions'];

		// Compile eventual content filter reasons
		$filter_reasons = [];
		if (!$is_preview && public_contact() != $item['author-id']) {
			if (!empty($item['content-warning']) && (!local_user() || !DI::pConfig()->get(local_user(), 'system', 'disable_cw', false))) {
				$filter_reasons[] = DI::l10n()->t('Content warning: %s', $item['content-warning']);
			}

			$hook_data = [
				'item' => $item,
				'filter_reasons' => $filter_reasons
			];
			Hook::callAll('prepare_body_content_filter', $hook_data);
			$filter_reasons = $hook_data['filter_reasons'];
			unset($hook_data);
		}

		// Update the cached values if there is no "zrl=..." on the links.
		$update = (!Session::isAuthenticated() && ($item["uid"] == 0));

		// Or update it if the current viewer is the intented viewer.
		if (($item["uid"] == local_user()) && ($item["uid"] != 0)) {
			$update = true;
		}

		self::putInCache($item, $update);
		$s = $item["rendered-html"];

		$hook_data = [
			'item' => $item,
			'html' => $s,
			'preview' => $is_preview,
			'filter_reasons' => $filter_reasons
		];
		Hook::callAll('prepare_body', $hook_data);
		$s = $hook_data['html'];
		unset($hook_data);

		if (!$attach) {
			// Replace the blockquotes with quotes that are used in mails.
			$mailquote = '<blockquote type="cite" class="gmail_quote" style="margin:0 0 0 .8ex;border-left:1px #ccc solid;padding-left:1ex;">';
			$s = str_replace(['<blockquote>', '<blockquote class="spoiler">', '<blockquote class="author">'], [$mailquote, $mailquote, $mailquote], $s);
			return $s;
		}

		$as = '';
		$vhead = false;
		$matches = [];
		preg_match_all('|\[attach\]href=\"(.*?)\" length=\"(.*?)\" type=\"(.*?)\"(?: title=\"(.*?)\")?|', $item['attach'], $matches, PREG_SET_ORDER);
		foreach ($matches as $mtch) {
			$mime = $mtch[3];

			$the_url = Contact::magicLinkById($item['author-id'], $mtch[1]);

			if (strpos($mime, 'video') !== false) {
				if (!$vhead) {
					$vhead = true;
					DI::page()['htmlhead'] .= Renderer::replaceMacros(Renderer::getMarkupTemplate('videos_head.tpl'));
				}

				$url_parts = explode('/', $the_url);
				$id = end($url_parts);
				$as .= Renderer::replaceMacros(Renderer::getMarkupTemplate('video_top.tpl'), [
					'$video' => [
						'id'     => $id,
						'title'  => DI::l10n()->t('View Video'),
						'src'    => $the_url,
						'mime'   => $mime,
					],
				]);
			}

			$filetype = strtolower(substr($mime, 0, strpos($mime, '/')));
			if ($filetype) {
				$filesubtype = strtolower(substr($mime, strpos($mime, '/') + 1));
				$filesubtype = str_replace('.', '-', $filesubtype);
			} else {
				$filetype = 'unkn';
				$filesubtype = 'unkn';
			}

			$title = Strings::escapeHtml(trim(($mtch[4] ?? '') ?: $mtch[1]));
			$title .= ' ' . $mtch[2] . ' ' . DI::l10n()->t('bytes');

			$icon = '<div class="attachtype icon s22 type-' . $filetype . ' subtype-' . $filesubtype . '"></div>';
			$as .= '<a href="' . strip_tags($the_url) . '" title="' . $title . '" class="attachlink" target="_blank" rel="noopener noreferrer" >' . $icon . '</a>';
		}

		if ($as != '') {
			$s .= '<div class="body-attach">'.$as.'<div class="clear"></div></div>';
		}

		// Map.
		if (strpos($s, '<div class="map">') !== false && !empty($item['coord'])) {
			$x = Map::byCoordinates(trim($item['coord']));
			if ($x) {
				$s = preg_replace('/\<div class\=\"map\"\>/', '$0' . $x, $s);
			}
		}

		// Replace friendica image url size with theme preference.
		if (!empty($a->theme_info['item_image_size'])) {
			$ps = $a->theme_info['item_image_size'];
			$s = preg_replace('|(<img[^>]+src="[^"]+/photo/[0-9a-f]+)-[0-9]|', "$1-" . $ps, $s);
		}

		$s = HTML::applyContentFilter($s, $filter_reasons);

		$hook_data = ['item' => $item, 'html' => $s];
		Hook::callAll('prepare_body_final', $hook_data);

		return $hook_data['html'];
	}

	/**
	 * get private link for item
	 *
	 * @param array $item
	 * @return boolean|array False if item has not plink, otherwise array('href'=>plink url, 'title'=>translated title)
	 * @throws \Exception
	 */
	public static function getPlink($item)
	{
		if (local_user()) {
			$ret = [
				'href' => "display/" . $item['guid'],
				'orig' => "display/" . $item['guid'],
				'title' => DI::l10n()->t('View on separate page'),
				'orig_title' => DI::l10n()->t('view on separate page'),
			];

			if (!empty($item['plink'])) {
				$ret["href"] = DI::baseUrl()->remove($item['plink']);
				$ret["title"] = DI::l10n()->t('link to source');
			}
		} elseif (!empty($item['plink']) && ($item['private'] != self::PRIVATE)) {
			$ret = [
				'href' => $item['plink'],
				'orig' => $item['plink'],
				'title' => DI::l10n()->t('link to source'),
			];
		} else {
			$ret = [];
		}

		return $ret;
	}

	/**
	 * Is the given item array a post that is sent as starting post to a forum?
	 *
	 * @param array $item
	 * @param array $owner
	 *
	 * @return boolean "true" when it is a forum post
	 */
	public static function isForumPost(array $item, array $owner = [])
	{
		if (empty($owner)) {
			$owner = User::getOwnerDataById($item['uid']);
			if (empty($owner)) {
				return false;
			}
		}

		if (($item['author-id'] == $item['owner-id']) ||
			($owner['id'] == $item['contact-id']) ||
			($item['uri'] != $item['parent-uri']) ||
			$item['origin']) {
			return false;
		}

		return Contact::isForum($item['contact-id']);
	}

	/**
	 * Search item id for given URI or plink
	 *
	 * @param string $uri
	 * @param integer $uid
	 *
	 * @return integer item id
	 */
	public static function searchByLink($uri, $uid = 0)
	{
		$ssl_uri = str_replace('http://', 'https://', $uri);
		$uris = [$uri, $ssl_uri, Strings::normaliseLink($uri)];

		$item = DBA::selectFirst('item', ['id'], ['uri' => $uris, 'uid' => $uid]);
		if (DBA::isResult($item)) {
			return $item['id'];
		}

		$itemcontent = DBA::selectFirst('item-content', ['uri-id'], ['plink' => $uris]);
		if (!DBA::isResult($itemcontent)) {
			return 0;
		}

		$itemuri = DBA::selectFirst('item-uri', ['uri'], ['id' => $itemcontent['uri-id']]);
		if (!DBA::isResult($itemuri)) {
			return 0;
		}

		$item = DBA::selectFirst('item', ['id'], ['uri' => $itemuri['uri'], 'uid' => $uid]);
		if (DBA::isResult($item)) {
			return $item['id'];
		}

		return 0;
	}

	/**
	 * Return the URI for a link to the post 
	 * 
	 * @param string $uri URI or link to post
	 *
	 * @return string URI
	 */
	public static function getURIByLink(string $uri)
	{
		$ssl_uri = str_replace('http://', 'https://', $uri);
		$uris = [$uri, $ssl_uri, Strings::normaliseLink($uri)];

		$item = DBA::selectFirst('item', ['uri'], ['uri' => $uris]);
		if (DBA::isResult($item)) {
			return $item['uri'];
		}

		$itemcontent = DBA::selectFirst('item-content', ['uri-id'], ['plink' => $uris]);
		if (!DBA::isResult($itemcontent)) {
			return '';
		}

		$itemuri = DBA::selectFirst('item-uri', ['uri'], ['id' => $itemcontent['uri-id']]);
		if (DBA::isResult($itemuri)) {
			return $itemuri['uri'];
		}

		return '';
	}

	/**
	 * Fetches item for given URI or plink
	 *
	 * @param string $uri
	 * @param integer $uid
	 *
	 * @return integer item id
	 */
	public static function fetchByLink($uri, $uid = 0)
	{
		$item_id = self::searchByLink($uri, $uid);
		if (!empty($item_id)) {
			return $item_id;
		}

		if ($fetched_uri = ActivityPub\Processor::fetchMissingActivity($uri)) {
			$item_id = self::searchByLink($fetched_uri, $uid);
		} else {
			$item_id = Diaspora::fetchByURL($uri);
		}

		if (!empty($item_id)) {
			return $item_id;
		}

		return 0;
	}

	/**
	 * Return share data from an item array (if the item is shared item)
	 * We are providing the complete Item array, because at some time in the future
	 * we hopefully will define these values not in the body anymore but in some item fields.
	 * This function is meant to replace all similar functions in the system.
	 *
	 * @param array $item
	 *
	 * @return array with share information
	 */
	public static function getShareArray($item)
	{
		if (!preg_match("/(.*?)\[share(.*?)\]\s?(.*?)\s?\[\/share\]\s?/ism", $item['body'], $matches)) {
			return [];
		}

		$attribute_string = $matches[2];
		$attributes = ['comment' => trim($matches[1]), 'shared' => trim($matches[3])];
		foreach (['author', 'profile', 'avatar', 'guid', 'posted', 'link'] as $field) {
			if (preg_match("/$field=(['\"])(.+?)\\1/ism", $attribute_string, $matches)) {
				$attributes[$field] = trim(html_entity_decode($matches[2] ?? '', ENT_QUOTES, 'UTF-8'));
			}
		}
		return $attributes;
	}

	/**
	 * Fetch item information for shared items from the original items and adds it.
	 *
	 * @param array $item
	 *
	 * @return array item array with data from the original item
	 */
	public static function addShareDataFromOriginal($item)
	{
		$shared = self::getShareArray($item);
		if (empty($shared)) {
			return $item;
		}

		// Real reshares always have got a GUID.
		if (empty($shared['guid'])) {
			return $item;
		}

		$uid = $item['uid'] ?? 0;

		// first try to fetch the item via the GUID. This will work for all reshares that had been created on this system
		$shared_item = self::selectFirst(['title', 'body', 'attach'], ['guid' => $shared['guid'], 'uid' => [0, $uid]]);
		if (!DBA::isResult($shared_item)) {
			if (empty($shared['link'])) {
				return $item;
			}

			// Otherwhise try to find (and possibly fetch) the item via the link. This should work for Diaspora and ActivityPub posts
			$id = self::fetchByLink($shared['link'], $uid);
			if (empty($id)) {
				Logger::info('Original item not found', ['url' => $shared['link'], 'callstack' => System::callstack()]);
				return $item;
			}

			$shared_item = self::selectFirst(['title', 'body', 'attach'], ['id' => $id]);
			if (!DBA::isResult($shared_item)) {
				return $item;
			}
			Logger::info('Got shared data from url', ['url' => $shared['link'], 'callstack' => System::callstack()]);
		} else {
			Logger::info('Got shared data from guid', ['guid' => $shared['guid'], 'callstack' => System::callstack()]);
		}

		if (!empty($shared_item['title'])) {
			$body = '[h3]' . $shared_item['title'] . "[/h3]\n" . $shared_item['body'];
			unset($shared_item['title']);
		} else {
			$body = $shared_item['body'];
		}

		$item['body'] = preg_replace("/\[share ([^\[\]]*)\].*\[\/share\]/ism", '[share $1]' . $body . '[/share]', $item['body']);
		unset($shared_item['body']);

		return array_merge($item, $shared_item);
	}
}
