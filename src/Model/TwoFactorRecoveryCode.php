<?php

namespace Friendica\Model;

use Friendica\BaseObject;
use Friendica\Database\DBA;
use Friendica\Util\DateTimeFormat;
use PragmaRX\Random\Random;
use PragmaRX\Recovery\Recovery;

class TwoFactorRecoveryCode extends BaseObject
{
	public static function countValidForUser($uid)
	{
		return DBA::count('2fa_recovery_codes', ['uid' => $uid, 'used' => null]);
	}

	public static function existsForUser($uid, $code)
	{
		return DBA::exists('2fa_recovery_codes', ['uid' => $uid, 'code' => $code, 'used' => null]);
	}

	public static function getListForUser($uid)
	{
		$codesStmt = DBA::select('2fa_recovery_codes', ['code', 'used'], ['uid' => $uid]);

		return DBA::toArray($codesStmt);
	}

	public static function markUsedForUser($uid, $code)
	{
		DBA::update('2fa_recovery_codes', ['used' => DateTimeFormat::utcNow()], ['uid' => $uid, 'code' => $code]);

		return DBA::affectedRows() > 0;
	}

	public static function generateForUser($uid)
	{
		$Random = (new Random())->pattern('[a-z0-9]');

		$RecoveryGenerator = new Recovery($Random);

		$codes = $RecoveryGenerator
			->setCount(12)
			->setBlocks(2)
			->setChars(6)
			->lowercase(true)
			->toArray();

		$generated = DateTimeFormat::utcNow();
		foreach ($codes as $code) {
			DBA::insert('2fa_recovery_codes', [
				'uid' => $uid,
				'code' => $code,
				'generated' => $generated
			]);
		}
	}

	public static function deleteForUser($uid)
	{
		DBA::delete('2fa_recovery_codes', ['uid' => $uid]);
	}

	public static function regenerateForUser($uid)
	{
		self::deleteForUser($uid);
		self::generateForUser($uid);
	}
}
