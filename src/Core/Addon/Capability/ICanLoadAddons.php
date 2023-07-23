<?php
/**
 * @copyright Copyright (C) 2010-2023, the Friendica project
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

namespace Friendica\Core\Addon\Capability;

/**
 * Interface for loading Addons specific content
 */
interface ICanLoadAddons
{
	/**
	 * Returns a merged config array of all active addons for a given config-name
	 *
	 * @param string $configName The config-name (config-file at the static directory, like 'hooks' => '{addon}/static/hooks.config.php)
	 *
	 * @return array the merged array
	 */
	public function getActiveAddonConfig(string $configName): array;
}
