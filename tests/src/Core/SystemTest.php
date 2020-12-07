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

namespace Friendica\Test\src\Core;

use Dice\Dice;
use Friendica\App\BaseURL;
use Friendica\Core\System;
use Friendica\DI;
use PHPUnit\Framework\TestCase;

class SystemTest extends TestCase
{
	private function useBaseUrl()
	{
		$baseUrl = \Mockery::mock(BaseURL::class);
		$baseUrl->shouldReceive('getHostname')->andReturn('friendica.local')->once();
		$dice = \Mockery::mock(Dice::class);
		$dice->shouldReceive('create')->with(BaseURL::class)->andReturn($baseUrl);

		DI::init($dice);
	}

	private function assertGuid($guid, $length, $prefix = '')
	{
		$length -= strlen($prefix);
		$this->assertRegExp("/^" . $prefix . "[a-z0-9]{" . $length . "}?$/", $guid);
	}

	function testGuidWithoutParameter()
	{
		$this->useBaseUrl();
		$guid = System::createGUID();
		$this->assertGuid($guid, 16);
	}

	function testGuidWithSize32()
	{
		$this->useBaseUrl();
		$guid = System::createGUID(32);
		$this->assertGuid($guid, 32);
	}

	function testGuidWithSize64()
	{
		$this->useBaseUrl();
		$guid = System::createGUID(64);
		$this->assertGuid($guid, 64);
	}

	function testGuidWithPrefix()
	{
		$guid = System::createGUID(23, 'test');
		$this->assertGuid($guid, 23, 'test');
	}
}
