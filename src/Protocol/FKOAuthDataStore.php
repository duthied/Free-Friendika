<?php
/**
 * @file src/Protocol/FKOAuthDataStore.php
 * OAuth server
 * Based on oauth2-php <http://code.google.com/p/oauth2-php/>
 *
 */
namespace Friendica\Protocol;

use Friendica\App;
use Friendica\Core\Config;
use Friendica\Core\System;
use Friendica\Database\DBM;
use dba;

define('REQUEST_TOKEN_DURATION', 300);
define('ACCESS_TOKEN_DURATION', 31536000);

require_once "library/OAuth1.php";
require_once "library/oauth2-php/lib/OAuth2.inc";

/**
 * @brief OAuthDataStore class
 */
class FKOAuthDataStore extends OAuthDataStore
{
	function gen_token()
	{
		return md5(base64_encode(pack('N6', mt_rand(), mt_rand(), mt_rand(), mt_rand(), mt_rand(), uniqid())));
	}

	function lookup_consumer($consumer_key)
	{
		logger(__function__.":".$consumer_key);
		
		$r = q("SELECT client_id, pw, redirect_uri FROM clients WHERE client_id='%s'",
			dbesc($consumer_key)
		);

		if (DBM::is_result($r)) {
			return new OAuthConsumer($r[0]['client_id'], $r[0]['pw'], $r[0]['redirect_uri']);
		}

		return null;
	}

	function lookup_token($consumer, $token_type, $token)
	{
		logger(__function__.":".$consumer.", ". $token_type.", ".$token);
		$r = q("SELECT id, secret,scope, expires, uid  FROM tokens WHERE client_id='%s' AND scope='%s' AND id='%s'",
			dbesc($consumer->key),
			dbesc($token_type),
			dbesc($token)
		);
		if (DBM::is_result($r)) {
			$ot=new OAuthToken($r[0]['id'], $r[0]['secret']);
			$ot->scope=$r[0]['scope'];
			$ot->expires = $r[0]['expires'];
			$ot->uid = $r[0]['uid'];
			return $ot;
		}
		return null;
	}

	function lookup_nonce($consumer, $token, $nonce, $timestamp)
	{
		//echo __file__.":".__line__."<pre>"; var_dump($consumer,$key); killme();
		$r = q("SELECT id, secret  FROM tokens WHERE client_id='%s' AND id='%s' AND expires=%d",
			dbesc($consumer->key),
			dbesc($nonce),
			intval($timestamp)
		);
		
		if (DBM::is_result($r)) {
			return new OAuthToken($r[0]['id'], $r[0]['secret']);
		}

		return null;
	}

	function new_request_token($consumer, $callback = null)
	{
		logger(__function__.":".$consumer.", ". $callback);
		$key = $this->gen_token();
		$sec = $this->gen_token();

		if ($consumer->key) {
			$k = $consumer->key;
		} else {
			$k = $consumer;
		}

		$r = q("INSERT INTO tokens (id, secret, client_id, scope, expires) VALUES ('%s','%s','%s','%s', UNIX_TIMESTAMP()+%d)",
			dbesc($key),
			dbesc($sec),
			dbesc($k),
			'request',
			intval(REQUEST_TOKEN_DURATION)
		);

		if (!$r) {
			return null;
		}

		return new OAuthToken($key, $sec);
	}

	function new_access_token($token, $consumer, $verifier = null)
	{
		logger(__function__.":".$token.", ". $consumer.", ". $verifier);

		// return a new access token attached to this consumer
		// for the user associated with this token if the request token
		// is authorized
		// should also invalidate the request token

		$ret = null;

		// get user for this verifier
		$uverifier = Config::get("oauth", $verifier);
		logger(__function__.":".$verifier.",".$uverifier);

		if (is_null($verifier) || ($uverifier!==false)) {
			$key = $this->gen_token();
			$sec = $this->gen_token();
			$r = q("INSERT INTO tokens (id, secret, client_id, scope, expires, uid) VALUES ('%s','%s','%s','%s', UNIX_TIMESTAMP()+%d, %d)",
				dbesc($key),
				dbesc($sec),
				dbesc($consumer->key),
				'access',
				intval(ACCESS_TOKEN_DURATION),
				intval($uverifier)
			);

			if ($r) {
				$ret = new OAuthToken($key, $sec);
			}
		}


		dba::delete('tokens', array('id' => $token->key));


		if (!is_null($ret) && $uverifier !== false) {
			Config::delete("oauth", $verifier);
		}

		return $ret;
	}
}
