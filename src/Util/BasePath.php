<?php

namespace Friendica\Util;

use Friendica\Core;

class BasePath
{
	/**
	 * @brief Returns the base filesystem path of the App
	 *
	 * It first checks for the internal variable, then for DOCUMENT_ROOT and
	 * finally for PWD
	 *
	 * @param string|null $basepath
	 *
	 * @return string
	 *
	 * @throws \Exception if directory isn't usable
	 */
	public static function create($basepath)
	{
		if (!$basepath && !empty($_SERVER['DOCUMENT_ROOT'])) {
			$basepath = $_SERVER['DOCUMENT_ROOT'];
		}

		if (!$basepath && !empty($_SERVER['PWD'])) {
			$basepath = $_SERVER['PWD'];
		}

		return self::getRealPath($basepath);
	}

	/**
	 * @brief Returns a normalized file path
	 *
	 * This is a wrapper for the "realpath" function.
	 * That function cannot detect the real path when some folders aren't readable.
	 * Since this could happen with some hosters we need to handle this.
	 *
	 * @param string $path The path that is about to be normalized
	 * @return string normalized path - when possible
	 */
	public static function getRealPath($path)
	{
		$normalized = realpath($path);

		if (!is_bool($normalized)) {
			return $normalized;
		} else {
			return $path;
		}
	}


	/**
	 * @brief Checks if a given directory is usable for the system
	 *
	 * @param      $directory
	 * @param bool $check_writable
	 *
	 * @return boolean the directory is usable
	 */
	public static function isDirectoryUsable($directory, $check_writable = true)
	{
		if ($directory == '') {
			Core\Logger::log('Directory is empty. This shouldn\'t happen.', Core\Logger::DEBUG);
			return false;
		}

		if (!file_exists($directory)) {
			Core\Logger::log('Path "' . $directory . '" does not exist for user ' . Core\System::getUser(), Core\Logger::DEBUG);
			return false;
		}

		if (is_file($directory)) {
			Core\Logger::log('Path "' . $directory . '" is a file for user ' . Core\System::getUser(), Core\Logger::DEBUG);
			return false;
		}

		if (!is_dir($directory)) {
			Core\Logger::log('Path "' . $directory . '" is not a directory for user ' . Core\System::getUser(), Core\Logger::DEBUG);
			return false;
		}

		if ($check_writable && !is_writable($directory)) {
			Core\Logger::log('Path "' . $directory . '" is not writable for user ' . Core\System::getUser(), Core\Logger::DEBUG);
			return false;
		}

		return true;
	}
}
