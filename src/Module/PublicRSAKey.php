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

use ASN_BASE;
use Friendica\BaseModule;
use Friendica\DI;
use Friendica\Model\User;
use Friendica\Network\HTTPException\BadRequestException;

/**
 * prints the public RSA key of a user
 */
class PublicRSAKey extends BaseModule
{
	public static function rawContent(array $parameters = [])
	{
		$app = DI::app();

		// @TODO: Replace with parameter from router
		if ($app->argc !== 2) {
			throw new BadRequestException();
		}

		// @TODO: Replace with parameter from router
		$nick = $app->argv[1];

		$user = User::getByNickname($nick, ['spubkey']);
		if (empty($user) || empty($user['spubkey'])) {
			throw new BadRequestException();
		}

		$lines = explode("\n", $user['spubkey']);
		unset($lines[0]);
		unset($lines[count($lines)]);

		$asnString = base64_decode(implode('', $lines));
		$asnBase = ASN_BASE::parseASNString($asnString);

		$m = $asnBase[0]->asnData[1]->asnData[0]->asnData[0]->asnData;
		$e = $asnBase[0]->asnData[1]->asnData[0]->asnData[1]->asnData;

		header('Content-type: application/magic-public-key');
		echo 'RSA' . '.' . $m . '.' . $e;

		exit();
	}
}
