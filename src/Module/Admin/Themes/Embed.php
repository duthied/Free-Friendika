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

namespace Friendica\Module\Admin\Themes;

use Friendica\Core\Renderer;
use Friendica\DI;
use Friendica\Module\BaseAdmin;
use Friendica\Util\Strings;

class Embed extends BaseAdmin
{
	public static function init(array $parameters = [])
	{
		$theme = Strings::sanitizeFilePathItem($parameters['theme']);
		if (is_file("view/theme/$theme/config.php")) {
			DI::app()->setCurrentTheme($theme);
		}
	}

	public static function post(array $parameters = [])
	{
		self::checkAdminAccess();

		$theme = Strings::sanitizeFilePathItem($parameters['theme']);
		if (is_file("view/theme/$theme/config.php")) {
			require_once "view/theme/$theme/config.php";
			if (function_exists('theme_admin_post')) {
				self::checkFormSecurityTokenRedirectOnError('/admin/themes/' . $theme . '/embed?mode=minimal', 'admin_theme_settings');
				theme_admin_post(DI::app());
			}
		}

		if (DI::mode()->isAjax()) {
			return;
		}

		DI::baseUrl()->redirect('admin/themes/' . $theme . '/embed?mode=minimal');
	}

	public static function content(array $parameters = [])
	{
		parent::content($parameters);

		$theme = Strings::sanitizeFilePathItem($parameters['theme']);
		if (!is_dir("view/theme/$theme")) {
			notice(DI::l10n()->t('Unknown theme.'));
			return '';
		}

		$admin_form = '';
		if (is_file("view/theme/$theme/config.php")) {
			require_once "view/theme/$theme/config.php";

			if (function_exists('theme_admin')) {
				$admin_form = theme_admin(DI::app());
			}
		}

		// Overrides normal theme style include to strip user param to show embedded theme settings
		Renderer::$theme['stylesheet'] = 'view/theme/' . $theme . '/style.pcss';

		$t = Renderer::getMarkupTemplate('admin/addons/embed.tpl');
		return Renderer::replaceMacros($t, [
			'$action' => '/admin/themes/' . $theme . '/embed?mode=minimal',
			'$form' => $admin_form,
			'$form_security_token' => self::getFormSecurityToken("admin_theme_settings"),
		]);
	}
}
