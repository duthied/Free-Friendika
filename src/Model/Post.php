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

namespace Friendica\Model;

use BadMethodCallException;
use Friendica\Core\Logger;
use Friendica\Core\System;
use Friendica\Database\Database;
use Friendica\Database\DBA;
use Friendica\Database\DBStructure;
use Friendica\Protocol\Activity;

class Post
{
	/**
	 * Insert a new post entry
	 *
	 * @param integer $uri_id
	 * @param array   $fields
	 * @return int    ID of inserted post
	 * @throws \Exception
	 */
	public static function insert(int $uri_id, array $data = [])
	{
		if (empty($uri_id)) {
			throw new BadMethodCallException('Empty URI_id');
		}

		$fields = DBStructure::getFieldsForTable('post', $data);

		// Additionally assign the key fields
		$fields['uri-id'] = $uri_id;

		if (!DBA::insert('post', $fields, Database::INSERT_IGNORE)) {
			return 0;
		}

		return DBA::lastInsertId();
	}

	/**
	 * Fetch a single post row
	 *
	 * @param mixed $stmt statement object
	 * @return array|false current row or false
	 * @throws \Exception
	 */
	public static function fetch($stmt)
	{
		$row = DBA::fetch($stmt);

		if (!is_array($row)) {
			return $row;
		}

		if (array_key_exists('verb', $row)) {
			if (in_array($row['verb'], Item::ACTIVITIES)) {
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

		if (array_key_exists('extid', $row) && is_null($row['extid'])) {
			$row['extid'] = '';
		}

		return $row;
	}

	/**
	 * Fills an array with data from an post query
	 *
	 * @param object $stmt statement object
	 * @param bool   $do_close
	 * @return array Data array
	 */
	public static function toArray($stmt, $do_close = true) {
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
	 * Check if post data exists
	 *
	 * @param array $condition array of fields for condition
	 *
	 * @return boolean Are there rows for that condition?
	 * @throws \Exception
	 */
	public static function exists($condition) {
		return DBA::exists('post-user-view', $condition);
	}

	/**
	 * Counts the posts satisfying the provided condition
	 *
	 * @param array        $condition array of fields for condition
	 * @param array        $params    Array of several parameters
	 *
	 * @return int
	 *
	 * Example:
	 * $condition = ["uid" => 1, "network" => 'dspr'];
	 * or:
	 * $condition = ["`uid` = ? AND `network` IN (?, ?)", 1, 'dfrn', 'dspr'];
	 *
	 * $count = Post::count($condition);
	 * @throws \Exception
	 */
	public static function count(array $condition = [], array $params = [])
	{
		return DBA::count('post-user-view', $condition, $params);
	}

	/**
	 * Retrieve a single record from the post table and returns it in an associative array
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
	 * Retrieve a single record from the post-thread table and returns it in an associative array
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
	 * Select rows from the post table and returns them as an array
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
	 * Select rows from the given view
	 *
	 * @param string $view      View (post-user-view or post-thread-user-view)
	 * @param array  $selected  Array of selected fields, empty for all
	 * @param array  $condition Array of fields for condition
	 * @param array  $params    Array of several parameters
	 *
	 * @return boolean|object
	 * @throws \Exception
	 */
	private static function selectView(string $view, array $selected = [], array $condition = [], $params = [])
	{
		if (empty($selected)) {
			$selected = array_merge(Item::DISPLAY_FIELDLIST, Item::ITEM_FIELDLIST);

			if ($view == 'post-thread-user-view') {
				$selected = array_merge($selected, ['ignored']);
			}
		}

		$selected = array_unique($selected);

		return DBA::select($view, $selected, $condition, $params);
	}

	/**
	 * Select rows from the post table
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
		return self::selectView('post-user-view', $selected, $condition, $params);
	}

	/**
	 * Select rows from the post table
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
		return self::selectView('post-thread-user-view', $selected, $condition, $params);
	}

	/**
	 * Select rows from the given view for a given user
	 *
	 * @param string  $view      View (post-user-view or post-thread-user-view)
	 * @param integer $uid       User ID
	 * @param array   $selected  Array of selected fields, empty for all
	 * @param array   $condition Array of fields for condition
	 * @param array   $params    Array of several parameters
	 *
	 * @return boolean|object
	 * @throws \Exception
	 */
	private static function selectViewForUser(string $view, $uid, array $selected = [], array $condition = [], $params = [])
	{
		if (empty($selected)) {
			$selected = Item::DISPLAY_FIELDLIST;
		}

		$condition = DBA::mergeConditions($condition,
			["`visible` AND NOT `deleted`
			AND NOT `author-blocked` AND NOT `owner-blocked`
			AND (NOT `causer-blocked` OR `causer-id` = ? OR `causer-id` IS NULL) AND NOT `contact-blocked`
			AND ((NOT `contact-readonly` AND NOT `contact-pending` AND (`contact-rel` IN (?, ?)))
				OR `self` OR `gravity` != ? OR `contact-uid` = ?)
			AND NOT EXISTS (SELECT `uri-id` FROM `post-user` WHERE `uid` = ? AND `uri-id` = `" . $view . "`.`uri-id` AND `hidden`)
			AND NOT EXISTS (SELECT `cid` FROM `user-contact` WHERE `uid` = ? AND `cid` = `author-id` AND `blocked`)
			AND NOT EXISTS (SELECT `cid` FROM `user-contact` WHERE `uid` = ? AND `cid` = `owner-id` AND `blocked`)
			AND NOT EXISTS (SELECT `cid` FROM `user-contact` WHERE `uid` = ? AND `cid` = `author-id` AND `ignored` AND `gravity` = ?)
			AND NOT EXISTS (SELECT `cid` FROM `user-contact` WHERE `uid` = ? AND `cid` = `owner-id` AND `ignored` AND `gravity` = ?)",
			0, Contact::SHARING, Contact::FRIEND, GRAVITY_PARENT, 0, $uid, $uid, $uid, $uid, GRAVITY_PARENT, $uid, GRAVITY_PARENT]);

		$select_string = implode(', ', array_map([DBA::class, 'quoteIdentifier'], $selected));

		$condition_string = DBA::buildCondition($condition);
		$param_string = DBA::buildParameter($params);

		$sql = "SELECT " . $select_string . " FROM `" . $view . "` " . $condition_string . $param_string;
		$sql = DBA::cleanQuery($sql);

		return DBA::p($sql, $condition);
	}

	/**
	 * Select rows from the post view for a given user
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
		return self::selectViewForUser('post-user-view', $uid, $selected, $condition, $params);
	}

		/**
	 * Select rows from the post view for a given user
	 *
	 * @param integer $uid       User ID
	 * @param array   $selected  Array of selected fields, empty for all
	 * @param array   $condition Array of fields for condition
	 * @param array   $params    Array of several parameters
	 *
	 * @return boolean|object
	 * @throws \Exception
	 */
	public static function selectThreadForUser($uid, array $selected = [], array $condition = [], $params = [])
	{
		return self::selectViewForUser('post-thread-user-view', $uid, $selected, $condition, $params);
	}

	/**
	 * Retrieve a single record from the post view for a given user and returns it in an associative array
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
		$params['limit'] = 1;

		$result = self::selectForUser($uid, $selected, $condition, $params);

		if (is_bool($result)) {
			return $result;
		} else {
			$row = self::fetch($result);
			DBA::close($result);
			return $row;
		}
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
		$postthreaduser = DBA::select('post-thread-user', ['uri-id'], ['uid' => $uid, 'pinned' => true]);
		if (!DBA::isResult($postthreaduser)) {
			return $postthreaduser;
		}
	
		$pinned = [];
		while ($useritem = DBA::fetch($postthreaduser)) {
			$pinned[] = $useritem['uri-id'];
		}
		DBA::close($postthreaduser);

		if (empty($pinned)) {
			return [];
		}

		$condition = DBA::mergeConditions(['uri-id' => $pinned, 'uid' => $uid, 'gravity' => GRAVITY_PARENT], $condition);

		return self::selectForUser($uid, $selected, $condition, $params);
	}

	/**
	 * Update existing post entries
	 *
	 * @param array $fields    The fields that are to be changed
	 * @param array $condition The condition for finding the item entries
	 *
	 * A return value of "0" doesn't mean an error - but that 0 rows had been changed.
	 *
	 * @return integer|boolean number of affected rows - or "false" if there was an error
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function update(array $fields, array $condition)
	{
		$affected = 0;

		Logger::info('Start Update', ['fields' => $fields, 'condition' => $condition, 'uid' => local_user(),'callstack' => System::callstack(10)]);

		// Don't allow changes to fields that are responsible for the relation between the records
		unset($fields['id']);
		unset($fields['parent']);
		unset($fields['uid']);
		unset($fields['uri']);
		unset($fields['uri-id']);
		unset($fields['thr-parent']);
		unset($fields['thr-parent-id']);
		unset($fields['parent-uri']);
		unset($fields['parent-uri-id']);

		$thread_condition = DBA::mergeConditions($condition, ['gravity' => GRAVITY_PARENT]);

		// To ensure the data integrity we do it in an transaction
		DBA::transaction();

		$update_fields = DBStructure::getFieldsForTable('post-user', $fields);
		if (!empty($update_fields)) {
			$affected_count = 0;
			$posts = DBA::select('post-user-view', ['post-user-id'], $condition);
			while ($rows = DBA::toArray($posts, false, 100)) {
				$puids = array_column($rows, 'post-user-id');
				if (!DBA::update('post-user', $update_fields, ['id' => $puids])) {
					DBA::rollback();
					Logger::notice('Updating post-user failed', ['fields' => $update_fields, 'condition' => $condition]);
					return false;
				}
				$affected_count += DBA::affectedRows();
			}
			DBA::close($posts);
			$affected = $affected_count;
		}

		$update_fields = DBStructure::getFieldsForTable('post-content', $fields);
		if (!empty($update_fields)) {
			$affected_count = 0;
			$posts = DBA::select('post-user-view', ['uri-id'], $condition, ['group_by' => ['uri-id']]);
			while ($rows = DBA::toArray($posts, false, 100)) {
				$uriids = array_column($rows, 'uri-id');
				if (!DBA::update('post-content', $update_fields, ['uri-id' => $uriids])) {
					DBA::rollback();
					Logger::notice('Updating post-content failed', ['fields' => $update_fields, 'condition' => $condition]);
					return false;
				}
				$affected_count += DBA::affectedRows();
			}
			DBA::close($posts);
			$affected = max($affected, $affected_count);
		}

		$update_fields = DBStructure::getFieldsForTable('post', $fields);
		if (!empty($update_fields)) {
			$affected_count = 0;
			$posts = DBA::select('post-user-view', ['uri-id'], $condition, ['group_by' => ['uri-id']]);
			while ($rows = DBA::toArray($posts, false, 100)) {
				$uriids = array_column($rows, 'uri-id');
				if (!DBA::update('post', $update_fields, ['uri-id' => $uriids])) {
					DBA::rollback();
					Logger::notice('Updating post failed', ['fields' => $update_fields, 'condition' => $condition]);
					return false;
				}
				$affected_count += DBA::affectedRows();
			}
			DBA::close($posts);
			$affected = max($affected, $affected_count);
		}

		$update_fields = Post\DeliveryData::extractFields($fields);
		if (!empty($update_fields)) {
			$affected_count = 0;
			$posts = DBA::select('post-user-view', ['uri-id'], $condition, ['group_by' => ['uri-id']]);
			while ($rows = DBA::toArray($posts, false, 100)) {
				$uriids = array_column($rows, 'uri-id');
				if (!DBA::update('post-delivery-data', $update_fields, ['uri-id' => $uriids])) {
					DBA::rollback();
					Logger::notice('Updating post-delivery-data failed', ['fields' => $update_fields, 'condition' => $condition]);
					return false;
				}
				$affected_count += DBA::affectedRows();
			}
			DBA::close($posts);
			$affected = max($affected, $affected_count);
		}

		$update_fields = DBStructure::getFieldsForTable('post-thread', $fields);
		if (!empty($update_fields)) {
			$affected_count = 0;
			$posts = DBA::select('post-user-view', ['uri-id'], $thread_condition, ['group_by' => ['uri-id']]);
			while ($rows = DBA::toArray($posts, false, 100)) {
				$uriids = array_column($rows, 'uri-id');
				if (!DBA::update('post-thread', $update_fields, ['uri-id' => $uriids])) {
					DBA::rollback();
					Logger::notice('Updating post-thread failed', ['fields' => $update_fields, 'condition' => $condition]);
					return false;
				}
				$affected_count += DBA::affectedRows();
			}
			DBA::close($posts);
			$affected = max($affected, $affected_count);
		}

		$update_fields = DBStructure::getFieldsForTable('post-thread-user', $fields);
		if (!empty($update_fields)) {
			$affected_count = 0;
			$posts = DBA::select('post-user-view', ['post-user-id'], $thread_condition);
			while ($rows = DBA::toArray($posts, false, 100)) {
				$thread_puids = array_column($rows, 'post-user-id');
				if (!DBA::update('post-thread-user', $update_fields, ['post-user-id' => $thread_puids])) {
					DBA::rollback();
					Logger::notice('Updating post-thread-user failed', ['fields' => $update_fields, 'condition' => $condition]);
					return false;
				}
				$affected_count += DBA::affectedRows();
			}
			DBA::close($posts);
			$affected = max($affected, $affected_count);
		}

		DBA::commit();

		Logger::info('Updated posts', ['rows' => $affected]);
		return $affected;
	}

	/**
	 * Delete a row from the post table
	 *
	 * @param array        $conditions Field condition(s)
	 * @param array        $options
	 *                           - cascade: If true we delete records in other tables that depend on the one we're deleting through
	 *                           relations (default: true)
	 *
	 * @return boolean was the delete successful?
	 * @throws \Exception
	 */
	public static function delete(array $conditions, array $options = [])
	{
		return DBA::delete('post', $conditions, $options);
	}
}
