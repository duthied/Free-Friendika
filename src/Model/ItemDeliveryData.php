<?php

/**
 * @file src/Model/ItemDeliveryData.php
 */

namespace Friendica\Model;

use Friendica\Database\DBA;
use \BadMethodCallException;

class ItemDeliveryData
{
	const LEGACY_FIELD_LIST = [
		// Legacy fields moved from item table
		'postopts',
		'inform',
	];

	const FIELD_LIST = [
		// New delivery fields with virtual field name in item fields
		'queue_count' => 'delivery_queue_count',
		'queue_done'  => 'delivery_queue_done',
		'queue_failed'  => 'delivery_queue_failed',
	];

	const ACTIVITYPUB = 1;
	const DFRN = 2;
	const LEGACY_DFRN = 3;
	const DIASPORA = 4;
	const OSTATUS = 5;
	const MAIL = 6;

	/**
	 * Extract delivery data from the provided item fields
	 *
	 * @param array $fields
	 * @return array
	 */
	public static function extractFields(array &$fields)
	{
		$delivery_data = [];
		foreach (array_merge(ItemDeliveryData::FIELD_LIST, ItemDeliveryData::LEGACY_FIELD_LIST) as $key => $field) {
			if (is_int($key) && isset($fields[$field])) {
				// Legacy field moved from item table
				$delivery_data[$field] = $fields[$field];
				$fields[$field] = null;
			} elseif (isset($fields[$field])) {
				// New delivery field with virtual field name in item fields
				$delivery_data[$key] = $fields[$field];
				unset($fields[$field]);
			}
		}

		return $delivery_data;
	}

	/**
	 * Increments the queue_done for the given item ID.
	 *
	 * Avoids racing condition between multiple delivery threads.
	 *
	 * @param integer $item_id
	 * @param integer $protocol
	 * @return bool
	 * @throws \Exception
	 */
	public static function incrementQueueDone($item_id, $protocol = 0)
	{
		$sql = '';

		switch ($protocol) {
			case self::ACTIVITYPUB:
				$sql = ", `activitypub` = `activitypub` + 1";
				break;
			case self::DFRN:
				$sql = ", `dfrn` = `dfrn` + 1";
				break;
			case self::LEGACY_DFRN:
				$sql = ", `legacy_dfrn` = `legacy_dfrn` + 1";
				break;
			case self::DIASPORA:
				$sql = ", `diaspora` = `diaspora` + 1";
				break;
			case self::OSTATUS:
				$sql = ", `ostatus` = `ostatus` + 1";
				break;
		}

		return DBA::e('UPDATE `item-delivery-data` SET `queue_done` = `queue_done` + 1' . $sql . ' WHERE `iid` = ?', $item_id);
	}

	/**
	 * Increments the queue_failed for the given item ID.
	 *
	 * Avoids racing condition between multiple delivery threads.
	 *
	 * @param integer $item_id
	 * @return bool
	 * @throws \Exception
	 */
	public static function incrementQueueFailed($item_id)
	{
		return DBA::e('UPDATE `item-delivery-data` SET `queue_failed` = `queue_failed` + 1 WHERE `iid` = ?', $item_id);
	}

	/**
	 * Increments the queue_count for the given item ID.
	 *
	 * @param integer $item_id
	 * @param integer $increment
	 * @return bool
	 * @throws \Exception
	 */
	public static function incrementQueueCount(int $item_id, int $increment = 1)
	{
		return DBA::e('UPDATE `item-delivery-data` SET `queue_count` = `queue_count` + ? WHERE `iid` = ?', $increment, $item_id);
	}

	/**
	 * Insert a new item delivery data entry
	 *
	 * @param integer $item_id
	 * @param array   $fields
	 * @return bool
	 * @throws \Exception
	 */
	public static function insert($item_id, array $fields)
	{
		if (empty($item_id)) {
			throw new BadMethodCallException('Empty item_id');
		}

		$fields['iid'] = $item_id;

		return DBA::insert('item-delivery-data', $fields);
	}

	/**
	 * Update/Insert item delivery data
	 *
	 * If you want to update queue_done, please use incrementQueueDone instead.
	 *
	 * @param integer $item_id
	 * @param array   $fields
	 * @return bool
	 * @throws \Exception
	 */
	public static function update($item_id, array $fields)
	{
		if (empty($item_id)) {
			throw new BadMethodCallException('Empty item_id');
		}

		if (empty($fields)) {
			// Nothing to do, update successful
			return true;
		}

		return DBA::update('item-delivery-data', $fields, ['iid' => $item_id], true);
	}

	/**
	 * Delete item delivery data
	 *
	 * @param integer $item_id
	 * @return bool
	 * @throws \Exception
	 */
	public static function delete($item_id)
	{
		if (empty($item_id)) {
			throw new BadMethodCallException('Empty item_id');
		}

		return DBA::delete('item-delivery-data', ['iid' => $item_id]);
	}
}
