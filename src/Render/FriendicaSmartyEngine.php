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

namespace Friendica\Render;

use Friendica\Core\Hook;
use Friendica\DI;

/**
 * Smarty implementation of the Friendica template engine interface
 */
class FriendicaSmartyEngine implements ITemplateEngine
{
	static $name = "smarty3";

	public function __construct()
	{
		if (!is_writable(__DIR__ . '/../../view/smarty3/')) {
			echo "<b>ERROR:</b> folder <tt>view/smarty3/</tt> must be writable by webserver.";
			exit();
		}
	}

	// ITemplateEngine interface
	public function replaceMacros($s, $r)
	{
		$template = '';
		if (gettype($s) === 'string') {
			$template = $s;
			$s = new FriendicaSmarty();
		}

		$r['$APP'] = DI::app();

		// "middleware": inject variables into templates
		$arr = [
			"template" => basename($s->filename),
			"vars" => $r
		];
		Hook::callAll("template_vars", $arr);
		$r = $arr['vars'];

		foreach ($r as $key => $value) {
			if ($key[0] === '$') {
				$key = substr($key, 1);
			}

			$s->assign($key, $value);
		}
		return $s->parsed($template);
	}

	public function getTemplateFile($file, $root = '')
	{
		$a = DI::app();
		$template = new FriendicaSmarty();

		// Make sure $root ends with a slash /
		if ($root !== '' && substr($root, -1, 1) !== '/') {
			$root = $root . '/';
		}

		$theme = $a->getCurrentTheme();
		$filename = $template::SMARTY3_TEMPLATE_FOLDER . '/' . $file;

		if (file_exists("{$root}view/theme/$theme/$filename")) {
			$template_file = "{$root}view/theme/$theme/$filename";
		} elseif (!empty($a->theme_info['extends']) && file_exists(sprintf('%sview/theme/%s}/%s', $root, $a->theme_info['extends'], $filename))) {
			$template_file = sprintf('%sview/theme/%s}/%s', $root, $a->theme_info['extends'], $filename);
		} elseif (file_exists("{$root}/$filename")) {
			$template_file = "{$root}/$filename";
		} else {
			$template_file = "{$root}view/$filename";
		}

		$template->filename = $template_file;

		return $template;
	}
}
