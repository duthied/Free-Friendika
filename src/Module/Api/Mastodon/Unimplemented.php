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

namespace Friendica\Module\Api\Mastodon;

use Friendica\Core\Logger;
use Friendica\Core\System;
use Friendica\DI;
use Friendica\Module\BaseApi;

/**
 * Dummy class for all currently unimplemented endpoints
 */
class Unimplemented extends BaseApi
{
	/**
	 * @param array $parameters
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function rawContent(array $parameters = [])
	{
		$path = DI::args()->getQueryString();
		Logger::info('Unimplemented API call', ['path' => $path]);
		$error = DI::l10n()->t('API endpoint "%s" is not implemented', $path);
		$error_description = DI::l10n()->t('The API endpoint is currently not implemented but might be in the future.');;
		$errorobj = new \Friendica\Object\Api\Mastodon\Error($error, $error_description);
		System::jsonError(501, $errorobj->toArray());
	}
}
