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
use Friendica\Core\Theme;

/**
 * Prints theme specific details as a JSON string
 */
class ThemeDetails extends BaseModule
{
	public static function rawContent(array $parameters = [])
	{
		if (!empty($_REQUEST['theme'])) {
			$theme = $_REQUEST['theme'];
			$info = Theme::getInfo($theme);

			// Unfortunately there will be no translation for this string
			$description = $info['description'] ?? '';
			$version     = $info['version']     ?? '';
			$credits     = $info['credits']     ?? '';

			echo json_encode([
				'img'     => Theme::getScreenshot($theme),
				'desc'    => $description,
				'version' => $version,
				'credits' => $credits,
			]);
		}
		exit();
	}
}
