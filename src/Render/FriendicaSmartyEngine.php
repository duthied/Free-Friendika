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
use Friendica\Util\Strings;

/**
 * Smarty implementation of the Friendica template abstraction
 */
final class FriendicaSmartyEngine extends TemplateEngine
{
	static $name = "smarty3";

	const FILE_PREFIX = 'file:';
	const STRING_PREFIX = 'string:';

	/** @var FriendicaSmarty */
	private $smarty;

	/**
	 * @inheritDoc
	 */
	public function __construct(string $theme, array $theme_info)
	{
		$this->theme = $theme;
		$this->theme_info = $theme_info;
		$this->smarty = new FriendicaSmarty($this->theme, $this->theme_info);

		if (!is_writable(DI::basePath() . '/view/smarty3')) {
			echo "<b>ERROR:</b> folder <tt>view/smarty3/</tt> must be writable by webserver.";
			exit();
		}
	}

	/**
	 * @inheritDoc
	 */
	public function replaceMacros(string $template, array $vars)
	{
		if (!Strings::startsWith($template, self::FILE_PREFIX)) {
			$template = self::STRING_PREFIX . $template;
		}

		// "middleware": inject variables into templates
		$arr = [
			'template' => basename($this->smarty->filename),
			'vars' => $vars
		];
		Hook::callAll('template_vars', $arr);
		$vars = $arr['vars'];

		foreach ($vars as $key => $value) {
			if ($key[0] === '$') {
				$key = substr($key, 1);
			}

			$this->smarty->assign($key, $value);
		}

		return $this->smarty->fetch($template);
	}

	/**
	 * @inheritDoc
	 */
	public function getTemplateFile(string $file, string $subDir = '')
	{
		// Make sure $root ends with a slash /
		if ($subDir !== '' && substr($subDir, -1, 1) !== '/') {
			$subDir = $subDir . '/';
		}

		$root = DI::basePath() . '/' . $subDir;

		$filename = $this->smarty::SMARTY3_TEMPLATE_FOLDER . '/' . $file;

		if (file_exists("{$root}view/theme/$this->theme/$filename")) {
			$template_file = "{$root}view/theme/$this->theme/$filename";
		} elseif (!empty($this->theme_info['extends']) && file_exists(sprintf('%sview/theme/%s}/%s', $root, $this->theme_info['extends'], $filename))) {
			$template_file = sprintf('%sview/theme/%s}/%s', $root, $this->theme_info['extends'], $filename);
		} elseif (file_exists("{$root}/$filename")) {
			$template_file = "{$root}/$filename";
		} else {
			$template_file = "{$root}view/$filename";
		}

		$this->smarty->filename = $template_file;

		return self::FILE_PREFIX . $template_file;
	}
}
