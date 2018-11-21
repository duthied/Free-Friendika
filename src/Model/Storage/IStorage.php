<?php
/**
 * @file src/Model/Storage/IStorage.php
 * @brief Storage backend system
 */

namespace Friendica\Model\Storage;

/**
 * @brief Interface for storage backends
 */
interface IStorage
{
	/**
	 * @brief Get data from backend
	 * @param string  $ref  Data reference
	 * @return string
     */
	public static function get($ref);

	/**
	 * @brief Put data in backend as $ref. If $ref is not defiend a new reference is created.
	 * @param string  $data  Data to save
	 * @param string  $ref   Data referece. Optional.
	 * @return string Saved data referece
	 */
	public static function put($data, $ref = "");

	/**
	 * @brief Remove data from backend
	 * @param string  $ref  Data referece
	 * @return boolean  True on success
	 */
	public static function delete($ref);
}
