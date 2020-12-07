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

namespace Friendica\Module\Debug;

use Friendica\BaseModule;
use Friendica\Core\Renderer;
use Friendica\DI;
use Friendica\Network\Probe;

/**
 * Web based module to perform webfinger probing
 */
class WebFinger extends BaseModule
{
	public static function content(array $parameters = [])
	{
		if (!local_user()) {
			$e           = new \Friendica\Network\HTTPException\ForbiddenException(DI::l10n()->t('Only logged in users are permitted to perform a probing.'));
			$e->httpdesc = DI::l10n()->t('Public access denied.');
			throw $e;
		}

		$addr = $_GET['addr'] ?? '';
		$res  = '';

		if (!empty($addr)) {
			$res = Probe::lrdd($addr);
			$res = print_r($res, true);
		}

		$tpl = Renderer::getMarkupTemplate('webfinger.tpl');
		return Renderer::replaceMacros($tpl, [
			'$addr' => $addr,
			'$res'  => $res,
		]);
	}
}
