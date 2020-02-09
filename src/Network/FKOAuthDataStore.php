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

namespace Friendica\Network;

use Friendica\Core\Logger;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Util\Strings;
use OAuthConsumer;
use OAuthDataStore;
use OAuthToken;

define('REQUEST_TOKEN_DURATION', 300);
define('ACCESS_TOKEN_DURATION', 31536000);

/**
 * OAuthDataStore class
 */
class FKOAuthDataStore extends OAuthDataStore
{
	/**
	 * @return string
	 * @throws \Exception
	 */
	private static function genToken()
	{
		return Strings::getRandomHex(32);
	}

	/**
	 * @param string $consumer_key key
	 * @return OAuthConsumer|null
	 * @throws \Exception
	 */
	public function lookup_consumer($consumer_key)
	{
		Logger::log(__function__ . ":" . $consumer_key);

		$s = DBA::select('clients', ['client_id', 'pw', 'redirect_uri'], ['client_id' => $consumer_key]);
		$r = DBA::toArray($s);

		if (DBA::isResult($r)) {
			return new OAuthConsumer($r[0]['client_id'], $r[0]['pw'], $r[0]['redirect_uri']);
		}

		return null;
	}

	/**
	 * @param OAuthConsumer $consumer
	 * @param string        $token_type
	 * @param string        $token_id
	 * @return OAuthToken|null
	 * @throws \Exception
	 */
	public function lookup_token(OAuthConsumer $consumer, $token_type, $token_id)
	{
		Logger::log(__function__ . ":" . $consumer . ", " . $token_type . ", " . $token_id);

		$s = DBA::select('tokens', ['id', 'secret', 'scope', 'expires', 'uid'], ['client_id' => $consumer->key, 'scope' => $token_type, 'id' => $token_id]);
		$r = DBA::toArray($s);

		if (DBA::isResult($r)) {
			$ot = new OAuthToken($r[0]['id'], $r[0]['secret']);
			$ot->scope = $r[0]['scope'];
			$ot->expires = $r[0]['expires'];
			$ot->uid = $r[0]['uid'];
			return $ot;
		}

		return null;
	}

	/**
	 * @param OAuthConsumer $consumer
	 * @param OAuthToken    $token
	 * @param string        $nonce
	 * @param int           $timestamp
	 * @return mixed
	 * @throws \Exception
	 */
	public function lookup_nonce(OAuthConsumer $consumer, OAuthToken $token, $nonce, int $timestamp)
	{
		$token = DBA::selectFirst('tokens', ['id', 'secret'], ['client_id' => $consumer->key, 'id' => $nonce, 'expires' => $timestamp]);
		if (DBA::isResult($token)) {
			return new OAuthToken($token['id'], $token['secret']);
		}

		return null;
	}

	/**
	 * @param OAuthConsumer $consumer
	 * @param string        $callback
	 * @return OAuthToken|null
	 * @throws \Exception
	 */
	public function new_request_token(OAuthConsumer $consumer, $callback = null)
	{
		Logger::log(__function__ . ":" . $consumer . ", " . $callback);
		$key = self::genToken();
		$sec = self::genToken();

		if ($consumer->key) {
			$k = $consumer->key;
		} else {
			$k = $consumer;
		}

		$r = DBA::insert(
			'tokens',
			[
				'id' => $key,
				'secret' => $sec,
				'client_id' => $k,
				'scope' => 'request',
				'expires' => time() + REQUEST_TOKEN_DURATION
			]
		);

		if (!$r) {
			return null;
		}

		return new OAuthToken($key, $sec);
	}

	/**
	 * @param OAuthToken    $token    token
	 * @param OAuthConsumer $consumer consumer
	 * @param string        $verifier optional, defult null
	 * @return OAuthToken
	 * @throws \Exception
	 */
	public function new_access_token(OAuthToken $token, OAuthConsumer $consumer, $verifier = null)
	{
		Logger::log(__function__ . ":" . $token . ", " . $consumer . ", " . $verifier);

		// return a new access token attached to this consumer
		// for the user associated with this token if the request token
		// is authorized
		// should also invalidate the request token

		$ret = null;

		// get user for this verifier
		$uverifier = DI::config()->get("oauth", $verifier);
		Logger::log(__function__ . ":" . $verifier . "," . $uverifier);

		if (is_null($verifier) || ($uverifier !== false)) {
			$key = self::genToken();
			$sec = self::genToken();
			$r = DBA::insert(
				'tokens',
				[
					'id' => $key,
					'secret' => $sec,
					'client_id' => $consumer->key,
					'scope' => 'access',
					'expires' => time() + ACCESS_TOKEN_DURATION,
					'uid' => $uverifier
				]
			);

			if ($r) {
				$ret = new OAuthToken($key, $sec);
			}
		}

		DBA::delete('tokens', ['id' => $token->key]);

		if (!is_null($ret) && !is_null($uverifier)) {
			DI::config()->delete("oauth", $verifier);
		}

		return $ret;
	}
}
