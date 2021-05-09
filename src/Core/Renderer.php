<?php
/**
 * @copyright Copyright (C) 2010-2021, the Friendica project
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

namespace Friendica\Core;

use Exception;
use Friendica\DI;
use Friendica\Network\HTTPException\InternalServerErrorException;
use Friendica\Render\TemplateEngine;

/**
 * This class handles Renderer related functions.
 */
class Renderer
{
	/**
	 * An array of registered template engines ('name'=>'class name')
	 */
	public static $template_engines = [];

	/**
	 * An array of instanced template engines ('name'=>'instance')
	 */
	public static $template_engine_instance = [];

	/**
	 * An array for all theme-controllable parameters
	 *
	 * Mostly unimplemented yet. Only options 'template_engine' and
	 * beyond are used.
	 */
	public static $theme = [
		'sourcename' => '',
		'videowidth' => 425,
		'videoheight' => 350,
		'stylesheet' => '',
		'template_engine' => 'smarty3',
	];

	private static $ldelim = [
		'internal' => '',
		'smarty3' => '{{'
	];
	private static $rdelim = [
		'internal' => '',
		'smarty3' => '}}'
	];

	/**
	 * Returns the rendered template output from the template string and variables
	 *
	 * @param string $template
	 * @param array  $vars
	 * @return string
	 * @throws InternalServerErrorException
	 */
	public static function replaceMacros(string $template, array $vars = [])
	{
		$stamp1 = microtime(true);

		// pass $baseurl to all templates if it isn't set
		$vars = array_merge(['$baseurl' => DI::baseUrl()->get(), '$APP' => DI::app()], $vars);

		$t = self::getTemplateEngine();

		try {
			$output = $t->replaceMacros($template, $vars);
		} catch (Exception $e) {
			DI::logger()->critical($e->getMessage(), ['template' => $template, 'vars' => $vars]);
			$message = is_site_admin() ?
				$e->getMessage() :
				DI::l10n()->t('Friendica can\'t display this page at the moment, please contact the administrator.');
			throw new InternalServerErrorException($message);
		}

		DI::profiler()->saveTimestamp($stamp1, "rendering");

		return $output;
	}

	/**
	 * Load a given template $s
	 *
	 * @param string $file   Template to load.
	 * @param string $subDir Subdirectory (Optional)
	 *
	 * @return string template.
	 * @throws InternalServerErrorException
	 */
	public static function getMarkupTemplate($file, $subDir = '')
	{
		$stamp1 = microtime(true);
		$t = self::getTemplateEngine();

		try {
			$template = $t->getTemplateFile($file, $subDir);
		} catch (Exception $e) {
			DI::logger()->critical($e->getMessage(), ['file' => $file, 'subDir' => $subDir]);
			$message = is_site_admin() ?
				$e->getMessage() :
				DI::l10n()->t('Friendica can\'t display this page at the moment, please contact the administrator.');
			throw new InternalServerErrorException($message);
		}

		DI::profiler()->saveTimestamp($stamp1, "file");

		return $template;
	}

	/**
	 * Register template engine class
	 *
	 * @param string $class
	 * @throws InternalServerErrorException
	 */
	public static function registerTemplateEngine($class)
	{
		$v = get_class_vars($class);

		if (!empty($v['name'])) {
			$name = $v['name'];
			self::$template_engines[$name] = $class;
		} else {
			$admin_message = DI::l10n()->t('template engine cannot be registered without a name.');
			DI::logger()->critical($admin_message, ['class' => $class]);
			$message = is_site_admin() ?
				$admin_message :
				DI::l10n()->t('Friendica can\'t display this page at the moment, please contact the administrator.');
			throw new InternalServerErrorException($message);
		}
	}

	/**
	 * Return template engine instance.
	 *
	 * If $name is not defined, return engine defined by theme,
	 * or default
	 *
	 * @return TemplateEngine Template Engine instance
	 * @throws InternalServerErrorException
	 */
	public static function getTemplateEngine()
	{
		$template_engine = (self::$theme['template_engine'] ?? '') ?: 'smarty3';

		if (isset(self::$template_engines[$template_engine])) {
			if (isset(self::$template_engine_instance[$template_engine])) {
				return self::$template_engine_instance[$template_engine];
			} else {
				$a = DI::app();
				$class = self::$template_engines[$template_engine];
				$obj = new $class($a->getCurrentTheme(), $a->theme_info);
				self::$template_engine_instance[$template_engine] = $obj;
				return $obj;
			}
		}

		$admin_message = DI::l10n()->t('template engine is not registered!');
		DI::logger()->critical($admin_message, ['template_engine' => $template_engine]);
		$message = is_site_admin() ?
			$admin_message :
			DI::l10n()->t('Friendica can\'t display this page at the moment, please contact the administrator.');
		throw new InternalServerErrorException($message);
	}

	/**
	 * Returns the active template engine.
	 *
	 * @return string the active template engine
	 */
	public static function getActiveTemplateEngine()
	{
		return self::$theme['template_engine'];
	}

	/**
	 * sets the active template engine
	 *
	 * @param string $engine the template engine (default is Smarty3)
	 */
	public static function setActiveTemplateEngine($engine = 'smarty3')
	{
		self::$theme['template_engine'] = $engine;
	}

	/**
	 * Gets the right delimiter for a template engine
	 *
	 * Currently:
	 * Internal = ''
	 * Smarty3 = '{{'
	 *
	 * @param string $engine The template engine (default is Smarty3)
	 *
	 * @return string the right delimiter
	 */
	public static function getTemplateLeftDelimiter($engine = 'smarty3')
	{
		return self::$ldelim[$engine];
	}

	/**
	 * Gets the left delimiter for a template engine
	 *
	 * Currently:
	 * Internal = ''
	 * Smarty3 = '}}'
	 *
	 * @param string $engine The template engine (default is Smarty3)
	 *
	 * @return string the left delimiter
	 */
	public static function getTemplateRightDelimiter($engine = 'smarty3')
	{
		return self::$rdelim[$engine];
	}
}
