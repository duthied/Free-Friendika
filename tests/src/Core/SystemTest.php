<?php

namespace Friendica\Test\src\Core;

use Friendica\Core\System;
use PHPUnit\Framework\TestCase;

class SystemTest extends TestCase
{
	private function assertGuid($guid, $length)
	{
		print $guid;
		$this->assertRegExp("/^[a-z0-9]{" . $length . "}?$/", $guid);
	}

	function testGuidWithoutParameter()
	{
		$guid = System::createGUID();
		$this->assertGuid($guid, 16);
	}

	function testGuidWithSize32() {
		$guid = System::createGUID(32);
		$this->assertGuid($guid, 32);
	}

	function testGuidWithSize64() {
		$guid = System::createGUID(64);
		$this->assertGuid($guid, 64);
	}
}