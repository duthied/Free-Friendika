<?php

/**
 * @file src/Core/Theme.php
 */

namespace Friendica\Core;

use Friendica\BaseObject;
use Friendica\Model\Profile;
use Friendica\Util\Strings;

require_once 'boot.php';

/**
 * Some functions to handle themes
 */
class Theme
{
	public static function getAllowedList()
	{
		$allowed_themes_str = Config::get('system', 'allowed_themes');
		$allowed_themes_raw = explode(',', str_replace(' ', '', $allowed_themes_str));
		$allowed_themes = [];
		if (count($allowed_themes_raw)) {
			foreach ($allowed_themes_raw as $theme) {
				$theme = Strings::sanitizeFilePathItem(trim($theme));
				if (strlen($theme) && is_dir("view/theme/$theme")) {
					$allowed_themes[] = $theme;
				}
			}
		}

		return $allowed_themes;
	}

	public static function setAllowedList(array $allowed_themes)
	{
		Config::set('system', 'allowed_themes', implode(',', $allowed_themes));
	}

	/**
	 * @brief Parse theme comment in search of theme infos.
	 *
	 * like
	 * \code
	 * ..* Name: My Theme
	 *   * Description: My Cool Theme
	 * . * Version: 1.2.3
	 *   * Author: John <profile url>
	 *   * Maintainer: Jane <profile url>
	 *   *
	 * \endcode
	 * @param string $theme the name of the theme
	 * @return array
	 */
	public static function getInfo($theme)
	{
		$theme = Strings::sanitizeFilePathItem($theme);

		$info = [
			'name' => $theme,
			'description' => "",
			'author' => [],
			'maintainer' => [],
			'version' => "",
			'credits' => "",
			'experimental' => file_exists("view/theme/$theme/experimental"),
			'unsupported' => file_exists("view/theme/$theme/unsupported")
		];

		if (!is_file("view/theme/$theme/theme.php")) {
			return $info;
		}

		$a = \get_app();
		$stamp1 = microtime(true);
		$theme_file = file_get_contents("view/theme/$theme/theme.php");
		$a->getProfiler()->saveTimestamp($stamp1, "file", System::callstack());

		$result = preg_match("|/\*.*\*/|msU", $theme_file, $matches);

		if ($result) {
			$comment_lines = explode("\n", $matches[0]);
			foreach ($comment_lines as $comment_line) {
				$comment_line = trim($comment_line, "\t\n\r */");
				if ($comment_line != "") {
					list($key, $value) = array_map("trim", explode(":", $comment_line, 2));
					$key = strtolower($key);
					if ($key == "author") {
						$result = preg_match("|([^<]+)<([^>]+)>|", $value, $matches);
						if ($result) {
							$info['author'][] = ['name' => $matches[1], 'link' => $matches[2]];
						} else {
							$info['author'][] = ['name' => $value];
						}
					} elseif ($key == "maintainer") {
						$result = preg_match("|([^<]+)<([^>]+)>|", $value, $matches);
						if ($result) {
							$info['maintainer'][] = ['name' => $matches[1], 'link' => $matches[2]];
						} else {
							$info['maintainer'][] = ['name' => $value];
						}
					} elseif (array_key_exists($key, $info)) {
						$info[$key] = $value;
					}
				}
			}
		}
		return $info;
	}

	/**
	 * @brief Returns the theme's screenshot.
	 *
	 * The screenshot is expected as view/theme/$theme/screenshot.[png|jpg].
	 *
	 * @param string $theme The name of the theme
	 * @return string
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function getScreenshot($theme)
	{
		$theme = Strings::sanitizeFilePathItem($theme);

		$exts = ['.png', '.jpg'];
		foreach ($exts as $ext) {
			if (file_exists('view/theme/' . $theme . '/screenshot' . $ext)) {
				return System::baseUrl() . '/view/theme/' . $theme . '/screenshot' . $ext;
			}
		}
		return System::baseUrl() . '/images/blank.png';
	}

	public static function uninstall($theme)
	{
		$theme = Strings::sanitizeFilePathItem($theme);

		// silently fail if theme was removed or if $theme is funky
		if (file_exists("view/theme/$theme/theme.php")) {
			include_once "view/theme/$theme/theme.php";

			$func = "{$theme}_uninstall";
			if (function_exists($func)) {
				$func();
			}
		}

		$allowed_themes = Theme::getAllowedList();
		$key = array_search($theme, $allowed_themes);
		if ($key !== false) {
			unset($allowed_themes[$key]);
			Theme::setAllowedList($allowed_themes);
		}
	}

	public static function install($theme)
	{
		$theme = Strings::sanitizeFilePathItem($theme);

		// silently fail if theme was removed or if $theme is funky
		if (!file_exists("view/theme/$theme/theme.php")) {
			return false;
		}

		try {
			include_once "view/theme/$theme/theme.php";

			$func = "{$theme}_install";
			if (function_exists($func)) {
				$func();
			}

			$allowed_themes = Theme::getAllowedList();
			$allowed_themes[] = $theme;
			Theme::setAllowedList($allowed_themes);

			return true;
		} catch (\Exception $e) {
			Logger::error('Theme installation failed', ['theme' => $theme, 'error' => $e->getMessage()]);
			return false;
		}
	}

	/**
	 * @brief Get the full path to relevant theme files by filename
	 *
	 * This function search in the theme directory (and if not present in global theme directory)
	 * if there is a directory with the file extension and  for a file with the given
	 * filename.
	 *
	 * @param string $file Filename
	 * @param string $root Full root path
	 * @return string Path to the file or empty string if the file isn't found
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function getPathForFile($file, $root = '')
	{
		$file = basename($file);

		// Make sure $root ends with a slash / if it's not blank
		if ($root !== '' && $root[strlen($root) - 1] !== '/') {
			$root = $root . '/';
		}
		$theme_info = \get_app()->theme_info;
		if (is_array($theme_info) && array_key_exists('extends', $theme_info)) {
			$parent = $theme_info['extends'];
		} else {
			$parent = 'NOPATH';
		}
		$theme = \get_app()->getCurrentTheme();
		$parent = Strings::sanitizeFilePathItem($parent);
		$ext = substr($file, strrpos($file, '.') + 1);
		$paths = [
			"{$root}view/theme/$theme/$ext/$file",
			"{$root}view/theme/$parent/$ext/$file",
			"{$root}view/$ext/$file",
		];
		foreach ($paths as $p) {
			// strpos() is faster than strstr when checking if one string is in another (http://php.net/manual/en/function.strstr.php)
			if (strpos($p, 'NOPATH') !== false) {
				continue;
			} elseif (file_exists($p)) {
				return $p;
			}
		}
		return '';
	}

	/**
	 * @brief Return relative path to theme stylesheet file
	 *
	 * Provide a sane default if nothing is chosen or the specified theme does not exist.
	 *
	 * @param string $theme Theme name
	 *
	 * @return string
	 */
	public static function getStylesheetPath($theme)
	{
		$theme = Strings::sanitizeFilePathItem($theme);

		if (!file_exists('view/theme/' . $theme . '/style.php')) {
			return 'view/theme/' . $theme . '/style.css';
		}

		$a = BaseObject::getApp();

		$query_params = [];

		$puid = Profile::getThemeUid($a);
		if ($puid) {
			$query_params['puid'] = $puid;
		}

		return 'view/theme/' . $theme . '/style.pcss' . (!empty($query_params) ? '?' . http_build_query($query_params) : '');
	}
}
