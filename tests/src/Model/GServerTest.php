<?php
/**
 * @copyright Copyright (C) 2010-2023, the Friendica project
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

namespace Friendica\Test\src\Model;

use Friendica\Model\GServer;
use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\UriInterface;

class GServerTest extends \PHPUnit\Framework\TestCase
{
	public function dataCleanUri(): array
	{
		return [
			'full-monty' => [
				'expected' => new Uri('https://example.com/path'),
				'dirtyUri' => new Uri('https://user:password@example.com/path?query=string#fragment'),
			],
			'index.php' => [
				'expected' => new Uri('https://example.com'),
				'dirtyUri' => new Uri('https://example.com/index.php'),
			],
			'index.php-2' => [
				'expected' => new Uri('https://example.com/path/to/resource'),
				'dirtyUri' => new Uri('https://example.com/index.php/path/to/resource'),
			],
			'index.php-path' => [
				'expected' => new Uri('https://example.com/path/to'),
				'dirtyUri' => new Uri('https://example.com/path/to/index.php'),
			],
			'index.php-path-2' => [
				'expected' => new Uri('https://example.com/path/to/path/to/resource'),
				'dirtyUri' => new Uri('https://example.com/path/to/index.php/path/to/resource'),
			],
			'index.php-slash' => [
				'expected' => new Uri('https://example.com'),
				'dirtyUri' => new Uri('https://example.com/index.php/'),
			],
			'index.php-slash-2' => [
				'expected' => new Uri('https://example.com/path/to/resource'),
				'dirtyUri' => new Uri('https://example.com/index.php/path/to/resource/'),
			],
		];
	}

	/**
	 * @dataProvider dataCleanUri
	 *
	 * @param UriInterface $expected
	 * @param UriInterface $dirtyUri
	 * @return void
	 * @throws \Exception
	 */
	public function testCleanUri(UriInterface $expected, UriInterface $dirtyUri)
	{
		$this->assertEquals($expected, GServer::cleanUri($dirtyUri));
	}
}
