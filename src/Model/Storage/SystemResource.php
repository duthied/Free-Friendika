<?php
/**
 * @file src/Model/Storage/SystemStorage.php
 */

namespace Friendica\Model\Storage;

/**
 * @brief System resource storage class
 *
 * This class is used to load system resources, like images.
 * Is not itended to be selectable by admins as default storage class.
 */
class SystemResource
{
	// Valid folders to look for resources
	const VALID_FOLDERS = [ "images" ];

	/**
	 * @brief get data
	 *
	 * @param string  $resourceid
	 *
	 * @return string
	 */
	static function get($filename)
	{
		$folder = dirname($filename);
		if (!in_array($folder, self::VALID_FOLDERS)) return "";
		if (!file_exists($filename)) return "";
		return file_get_contents($filename);
	}

	static function put($filename, $data)
	{
		throw new \BadMethodCallException();
	}
}

