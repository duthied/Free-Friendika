<?php

/**
 * @file src/Model/Attach.php
 * @brief This file contains the Attach class for database interface
 */
namespace Friendica\Model;

use Friendica\BaseObject;
use Friendica\Core\StorageManager;
use Friendica\Database\DBA;
use Friendica\Database\DBStructure;
use Friendica\Util\Security;


/**
 * Class to handle attach dabatase table
 */
class Attach extends BaseObject
{

	/**
	 * @brief Return a list of fields that are associated with the attach table
	 *
	 * @return array field list
	 */
	private static function getFields()
	{
		$allfields = DBStructure::definition(false);
		$fields = array_keys($allfields['attach']['fields']);
		array_splice($fields, array_search('data', $fields), 1);
		return $fields;
	}

	/**
	 * @brief Select rows from the attach table
	 *
	 * @param array  $fields     Array of selected fields, empty for all
	 * @param array  $conditions Array of fields for conditions
	 * @param array  $params     Array of several parameters
	 *
	 * @return boolean|array
	 *
	 * @see \Friendica\Database\DBA::select
	 */
	public static function select(array $fields = [], array $conditions = [], array $params = [])
	{
		if (empty($fields)) {
			$selected = self::getFields();
		}

		$r = DBA::select('attach', $fields, $conditions, $params);
		return DBA::toArray($r);
	}

	/**
	 * @brief Retrieve a single record from the attach table
	 *
	 * @param array  $fields     Array of selected fields, empty for all
	 * @param array  $conditions Array of fields for conditions
	 * @param array  $params     Array of several parameters
	 *
	 * @return bool|array
	 *
	 * @see \Friendica\Database\DBA::select
	 */
	public static function selectFirst(array $fields = [], array $conditions = [], array $params = [])
	{
		if (empty($fields)) {
			$fields = self::getFields();
		}

		return DBA::selectFirst('attach', $fields, $conditions, $params);
	}

	/**
	 * @brief Check if attachment with given conditions exists
	 *
	 * @param array   $conditions  Array of extra conditions
	 *
	 * @return boolean
	 */
	public static function exists(array $conditions)
	{
		return DBA::exists('attach', $conditions);
	}

	/**
	 * @brief Retrive a single record given the ID
	 * 
	 * @param int  $id  Row id of the record
	 * 
	 * @return bool|array
	 *
	 * @see \Friendica\Database\DBA::select
	 */
	public static function getById($id)
	{
		return self::selectFirst([], ['id' => $id]);
	}

	/**
	 * @brief Retrive a single record given the ID 
	 * 
	 * @param int  $id  Row id of the record
	 * 
	 * @return bool|array
	 *
	 * @see \Friendica\Database\DBA::select
	 */
	public static function getByIdWithPermission($id)
	{
		$r = self::selectFirst(['uid'], ['id' => $id]);
		if ($r === false) {
			return false;
		}

		$sql_acl = Security::getPermissionsSQLByUserId($r['uid']);

		$conditions = [
			'`id` = ?' . $sql_acl,
			$id
		];

		$item = self::selectFirst([], $conditions);

		return $item;
	}

	/**
	 * @brief Get file data for given row id. null if row id does not exist
	 * 
	 * @param array  $item  Attachment data. Needs at least 'id', 'backend-class', 'backend-ref'
	 * 
	 * @return string  file data
	 */
	public static function getData($item)
	{
		if ($item['backend-class'] == '') {
			// legacy data storage in 'data' column
			$i = self::selectFirst(['data'], ['id' => $item['id']]);
			if ($i === false) {
				return null;
			}
			return $i['data'];
		} else {
			$backendClass = $item['backend-class'];
			$backendRef = $item['backend-ref'];
			return $backendClass::get($backendRef);
		}
	}
}