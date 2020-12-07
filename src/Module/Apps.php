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

namespace Friendica\Module;

use Friendica\BaseModule;
use Friendica\Content\Nav;
use Friendica\Core\Renderer;
use Friendica\DI;

/**
 * Shows the App menu
 */
class Apps extends BaseModule
{
	public static function init(array $parameters = [])
	{
		$privateaddons = DI::config()->get('config', 'private_addons');
		if ($privateaddons === "1" && !local_user()) {
			DI::baseUrl()->redirect();
		}
	}

	public static function content(array $parameters = [])
	{
		$apps = Nav::getAppMenu();

		if (count($apps) == 0) {
			notice(DI::l10n()->t('No installed applications.') . EOL);
		}

		$tpl = Renderer::getMarkupTemplate('apps.tpl');
		return Renderer::replaceMacros($tpl, [
			'$title' => DI::l10n()->t('Applications'),
			'$apps'  => $apps,
		]);
	}
}
