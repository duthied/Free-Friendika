<?php

namespace Friendica\Security\TwoFactor\Factory;

use Friendica\BaseFactory;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Strings;

class TrustedBrowser extends BaseFactory
{
	public function createForUserWithUserAgent($uid, $userAgent): \Friendica\Security\TwoFactor\Model\TrustedBrowser
	{
		$trustedHash = Strings::getRandomHex();

		return new \Friendica\Security\TwoFactor\Model\TrustedBrowser(
			$trustedHash,
			$uid,
			$userAgent,
			DateTimeFormat::utcNow()
		);
	}

	public function createFromTableRow(array $row): \Friendica\Security\TwoFactor\Model\TrustedBrowser
	{
		return new \Friendica\Security\TwoFactor\Model\TrustedBrowser(
			$row['cookie_hash'],
			$row['uid'],
			$row['user_agent'],
			$row['created'],
			$row['last_used']
		);
	}
}
