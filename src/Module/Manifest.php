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
use Friendica\Core;
use Friendica\DI;

class Manifest extends BaseModule
{
	public static function rawContent(array $parameters = [])
	{
		$config = DI::config();

		header('Content-type: application/manifest+json');

		$touch_icon = $config->get('system', 'touch_icon') ?: 'images/friendica-128.png';

		$theme = DI::config()->get('system', 'theme');

		$tpl = Core\Renderer::getMarkupTemplate('manifest.tpl');
		$output = Core\Renderer::replaceMacros($tpl, [
			'$touch_icon'       => $touch_icon,
			'$title'            => $config->get('config', 'sitename', 'Friendica'),
			'$description'      => $config->get('config', 'info', DI::l10n()->t('A Decentralized Social Network')),
			'$background_color' => Core\Theme::getBackgroundColor($theme),
			'$theme_color'      => Core\Theme::getThemeColor($theme),
		]);

		echo $output;

		exit();
	}
}
