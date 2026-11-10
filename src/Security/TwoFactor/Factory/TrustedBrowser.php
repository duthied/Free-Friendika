<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Security\TwoFactor\Factory;

use Friendica\BaseFactory;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Strings;

class TrustedBrowser extends BaseFactory
{
	/**
	 * Creates a new Trusted Browser based on the current user environment
	 *
	 * @throws \Exception In case something really unexpected happens
	 */
	public function createForUserWithUserAgent(int $uid, string $userAgent, bool $trusted): \Friendica\Security\TwoFactor\Model\TrustedBrowser
	{
		$trustedHash = Strings::getRandomHex();

		return new \Friendica\Security\TwoFactor\Model\TrustedBrowser(
			$trustedHash,
			$uid,
			$userAgent,
			$trusted,
			DateTimeFormat::utcNow(),
		);
	}

	public function createFromTableRow(array $row): \Friendica\Security\TwoFactor\Model\TrustedBrowser
	{
		return new \Friendica\Security\TwoFactor\Model\TrustedBrowser(
			$row['cookie_hash'],
			$row['uid'],
			$row['user_agent'],
			$row['trusted'],
			$row['created'],
			$row['last_used'],
		);
	}
}
