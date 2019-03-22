<?php
/**
 * @file src/Model/Storage/SystemStorage.php
 * @brief Storage backend system
 */

namespace Friendica\Model\Storage;

use \BadMethodCallException;

/**
 * @brief System resource storage class
 *
 * This class is used to load system resources, like images.
 * Is not intended to be selectable by admins as default storage class.
 */
class SystemResource implements IStorage
{
	// Valid folders to look for resources
	const VALID_FOLDERS = ["images"];

	public static function get($filename)
	{
		$folder = dirname($filename);
		if (!in_array($folder, self::VALID_FOLDERS)) {
			return "";
		}
		if (!file_exists($filename)) {
			return "";
		}
		return file_get_contents($filename);
	}


	public static function put($data, $filename = "")
	{
		throw new BadMethodCallException();
	}

	public static function delete($filename)
	{
		throw new BadMethodCallException();
	}

	public static function getOptions()
	{
		return [];
	}

	public static function saveOptions($data)
	{
		return [];
	}
}

