<?php
/**
 * @file src/Model/Storage/SystemStorage.php
 * Storage backend system
 */

namespace Friendica\Model\Storage;

use \BadMethodCallException;

/**
 * System resource storage class
 *
 * This class is used to load system resources, like images.
 * Is not intended to be selectable by admins as default storage class.
 */
class SystemResource implements IStorage
{
	const NAME = 'SystemResource';

	// Valid folders to look for resources
	const VALID_FOLDERS = ["images"];

	/**
	 * @inheritDoc
	 */
	public function get(string $filename)
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

	/**
	 * @inheritDoc
	 */
	public function put(string $data, string $filename = '')
	{
		throw new BadMethodCallException();
	}

	public function delete(string $filename)
	{
		throw new BadMethodCallException();
	}

	/**
	 * @inheritDoc
	 */
	public function getOptions()
	{
		return [];
	}

	/**
	 * @inheritDoc
	 */
	public function saveOptions(array $data)
	{
		return [];
	}

	/**
	 * @inheritDoc
	 */
	public function __toString()
	{
		return self::NAME;
	}

	/**
	 * @inheritDoc
	 */
	public static function getName()
	{
		return self::NAME;
	}
}
