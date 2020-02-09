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
		$a = DI::app();

		if ($a->argc > 2) {
			// @TODO: Replace with parameter from router
			$theme = $a->argv[2];
			$theme = Strings::sanitizeFilePathItem($theme);
			if (is_file("view/theme/$theme/config.php")) {
				$a->setCurrentTheme($theme);
			}
		}
	}

	public static function post(array $parameters = [])
	{
		parent::post($parameters);

		$a = DI::app();

		if ($a->argc > 2) {
			// @TODO: Replace with parameter from router
			$theme = $a->argv[2];
			$theme = Strings::sanitizeFilePathItem($theme);
			if (is_file("view/theme/$theme/config.php")) {
				self::checkFormSecurityTokenRedirectOnError('/admin/themes/' . $theme . '/embed?mode=minimal', 'admin_theme_settings');

				require_once "view/theme/$theme/config.php";

				if (function_exists('theme_admin_post')) {
					theme_admin_post($a);
				}
			}

			info(DI::l10n()->t('Theme settings updated.'));

			if (DI::mode()->isAjax()) {
				return;
			}

			DI::baseUrl()->redirect('admin/themes/' . $theme . '/embed?mode=minimal');
		}
	}

	public static function content(array $parameters = [])
	{
		parent::content($parameters);

		$a = DI::app();

		if ($a->argc > 2) {
			// @TODO: Replace with parameter from router
			$theme = $a->argv[2];
			$theme = Strings::sanitizeFilePathItem($theme);
			if (!is_dir("view/theme/$theme")) {
				notice(DI::l10n()->t('Unknown theme.'));
				return '';
			}

			$admin_form = '';
			if (is_file("view/theme/$theme/config.php")) {
				require_once "view/theme/$theme/config.php";

				if (function_exists('theme_admin')) {
					$admin_form = theme_admin($a);
				}
			}

			// Overrides normal theme style include to strip user param to show embedded theme settings
			Renderer::$theme['stylesheet'] = 'view/theme/' . $theme . '/style.pcss';

			$t = Renderer::getMarkupTemplate('admin/addons/embed.tpl');
			return Renderer::replaceMacros($t, [
				'$action' => '/admin/themes/' . $theme . '/embed?mode=minimal',
				'$form' => $admin_form,
				'$form_security_token' => parent::getFormSecurityToken("admin_theme_settings"),
			]);
		}

		return '';
	}
}
