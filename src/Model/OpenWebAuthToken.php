<?php

/**
 * @file src/Model/OpenWebAuthToken.php
 */
namespace Friendica\Model;

use Friendica\Database\DBM;
use Friendica\Util\DateTimeFormat;
use dba;

/**
 * Methods to deal with entries of the 'openwebauth_token' table.
 */
class OpenWebAuthToken
{
	/**
	 * Create an entry in the 'openwebauth_token' table.
	 * 
	 * @param string $type   Verify type.
	 * @param int    $uid    The user ID.
	 * @param string $token
	 * @param string $meta
	 * 
	 * @return boolean
	 */
	public static function create($type, $uid, $token, $meta)
	{
		$fields = [
			"type" => $type,
			"uid" => $uid,
			"token" => $token,
			"meta" => $meta,
			"created" => DateTimeFormat::utcNow()
		];
		return dba::insert("openwebauth_token", $fields);
	}

	/**
	 * Get the "meta" field of an entry in the openwebauth_token table.
	 * 
	 * @param string $type   Verify type.
	 * @param int    $uid    The user ID.
	 * @param string $token
	 * 
	 * @return string|boolean The meta enry or false if not found.
	 */
	public static function getMeta($type, $uid, $token)
	{
		$condition = ["type" => $type, "uid" => $uid, "token" => $token];

		$entry = dba::selectFirst("openwebauth_token", ["id", "meta"], $condition);
		if (DBM::is_result($entry)) {
			dba::delete("openwebauth_token", ["id" => $entry["id"]]);

			return $entry["meta"];
		}
		return false;
	}

	/**
	 * Purge entries of a verify-type older than interval.
	 * 
	 * @param string $type     Verify type.
	 * @param string $interval SQL compatible time interval
	 */
	public static function purge($type, $interval)
	{
		$condition = ["`type` = ? AND `created` < ?", $type, DateTimeFormat::utcNow() . " - INTERVAL " . $interval];
		dba::delete("openwebauth_token", $condition);
	}

}
