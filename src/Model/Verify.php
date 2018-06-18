<?php

/**
 * @file src/Model/Verify.php
 */
namespace Friendica\Model;

use Friendica\Database\DBM;
use Friendica\Util\DateTimeFormat;
use dba;

/**
 * Methods to deal with entries of the 'verify' table.
 */
class Verify
{
	/**
	 * Create an entry in the 'verify' table.
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
		return dba::insert("verify", $fields);
	}

	/**
	 * Get the "meta" field of an entry in the verify table.
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

		$entry = dba::selectFirst("verify", ["id", "meta"], $condition);
		if (DBM::is_result($entry)) {
			dba::delete("verify", ["id" => $entry["id"]]);

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
		dba::delete("verify", $condition);
	}

}
