<?php

namespace Friendica\Test\src\Core;

use Friendica\Core\System;
use PHPUnit\Framework\TestCase;

class SystemTest extends TestCase
{
	private function assertGuid($guid, $length)
	{
		$this->assertRegExp("/^[a-z0-9]{" . $length . "}?$/", $guid);
	}

	function testGuidWithoutParameter()
	{
		$guid = System::createGUID();
		$this->assertGuid($guid, 16);
	}

	function testGuidWithSize() {
		$guid = System::createGUID(20);
		$this->assertGuid($guid, 20);
	}
}