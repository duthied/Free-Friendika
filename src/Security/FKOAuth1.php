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

namespace Friendica\Security;

use Friendica\Core\Logger;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Security\OAuth1\OAuthServer;
use Friendica\Security\OAuth1\Signature\OAuthSignatureMethod_HMAC_SHA1;
use Friendica\Security\OAuth1\Signature\OAuthSignatureMethod_PLAINTEXT;

/**
 * OAuth protocol
 */
class FKOAuth1 extends OAuthServer
{
	/**
	 * Constructor
	 */
	public function __construct()
	{
		parent::__construct(new FKOAuthDataStore());
		$this->add_signature_method(new OAuthSignatureMethod_PLAINTEXT());
		$this->add_signature_method(new OAuthSignatureMethod_HMAC_SHA1());
	}

	/**
	 * @param string $uid user id
	 * @return void
	 * @throws HTTPException\ForbiddenException
	 * @throws HTTPException\InternalServerErrorException
	 */
	public function loginUser($uid)
	{
		Logger::notice("FKOAuth1::loginUser $uid");
		$a = DI::app();
		$record = DBA::selectFirst('user', [], ['uid' => $uid, 'blocked' => 0, 'account_expired' => 0, 'account_removed' => 0, 'verified' => 1]);

		if (!DBA::isResult($record) || empty($uid)) {
			Logger::info('FKOAuth1::loginUser failure', ['server' => $_SERVER]);
			header('HTTP/1.0 401 Unauthorized');
			die('This api requires login');
		}

		DI::auth()->setForUser($a, $record, true);
	}
}
