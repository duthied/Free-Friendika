<?php

namespace Friendica\Test\src\Security\TwoFactor\Model;

use Friendica\Security\TwoFactor\Model\TrustedBrowser;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Strings;

class TrustedBrowserTest extends \PHPUnit_Framework_TestCase
{
	public function test__construct()
	{
		$hash = Strings::getRandomHex();

		$trustedBrowser = new TrustedBrowser(
			$hash,
			42,
			'PHPUnit',
			DateTimeFormat::utcNow()
		);

		$this->assertEquals($hash, $trustedBrowser->cookie_hash);
		$this->assertEquals(42, $trustedBrowser->uid);
		$this->assertEquals('PHPUnit', $trustedBrowser->user_agent);
		$this->assertNotEmpty($trustedBrowser->created);
	}

	public function testRecordUse()
	{
		$hash = Strings::getRandomHex();
		$past = DateTimeFormat::utc('now - 5 minutes');

		$trustedBrowser = new TrustedBrowser(
			$hash,
			42,
			'PHPUnit',
			$past,
			$past
		);

		$trustedBrowser->recordUse();

		$this->assertEquals($past, $trustedBrowser->created);
		$this->assertGreaterThan($past, $trustedBrowser->last_used);
	}
}
