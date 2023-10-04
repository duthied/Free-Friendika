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

namespace Friendica\Test\src\Network;

use Friendica\Network\Entity;
use Friendica\Network\Factory;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class MimeTypeTest extends TestCase
{
	public function dataCreateFromContentType(): array
	{
		return [
			'image/jpg' => [
				'expected' => new Entity\MimeType('image', 'jpg'),
				'contentType' => 'image/jpg',
			],
			'image/jpg;charset=utf8' => [
				'expected' => new Entity\MimeType('image', 'jpg', ['charset' => 'utf8']),
				'contentType' => 'image/jpg; charset=utf8',
			],
			'image/jpg; charset=utf8' => [
				'expected' => new Entity\MimeType('image', 'jpg', ['charset' => 'utf8']),
				'contentType' => 'image/jpg; charset=utf8',
			],
			'image/jpg; charset = utf8' => [
				'expected' => new Entity\MimeType('image', 'jpg', ['charset' => 'utf8']),
				'contentType' => 'image/jpg; charset=utf8',
			],
			'image/jpg; charset="utf8"' => [
				'expected' => new Entity\MimeType('image', 'jpg', ['charset' => 'utf8']),
				'contentType' => 'image/jpg; charset="utf8"',
			],
			'image/jpg; charset="\"utf8\""' => [
				'expected' => new Entity\MimeType('image', 'jpg', ['charset' => '"utf8"']),
				'contentType' => 'image/jpg; charset="\"utf8\""',
			],
			'image/jpg; charset="\"utf8\" (comment)"' => [
				'expected' => new Entity\MimeType('image', 'jpg', ['charset' => '"utf8"']),
				'contentType' => 'image/jpg; charset="\"utf8\" (comment)"',
			],
			'image/jpg; charset=utf8 (comment)' => [
				'expected' => new Entity\MimeType('image', 'jpg', ['charset' => 'utf8']),
				'contentType' => 'image/jpg; charset="utf8 (comment)"',
			],
			'image/jpg; charset=utf8; attribute=value' => [
				'expected' => new Entity\MimeType('image', 'jpg', ['charset' => 'utf8', 'attribute' => 'value']),
				'contentType' => 'image/jpg; charset=utf8; attribute=value',
			],
			'empty' => [
				'expected' => new Entity\MimeType('unkn', 'unkn'),
				'contentType' => '',
			],
			'unknown' => [
				'expected' => new Entity\MimeType('unkn', 'unkn'),
				'contentType' => 'unknown',
			],
		];
	}

	/**
	 * @dataProvider dataCreateFromContentType
	 * @param Entity\MimeType $expected
	 * @param string          $contentType
	 * @return void
	 */
	public function testCreateFromContentType(Entity\MimeType $expected, string $contentType)
	{
		$factory = new Factory\MimeType(new NullLogger());

		$this->assertEquals($expected, $factory->createFromContentType($contentType));
	}

	public function dataToString(): array
	{
		return [
			'image/jpg' => [
				'expected' => 'image/jpg',
				'mimeType' => new Entity\MimeType('image', 'jpg'),
			],
			'image/jpg;charset=utf8' => [
				'expected' => 'image/jpg; charset=utf8',
				'mimeType' => new Entity\MimeType('image', 'jpg', ['charset' => 'utf8']),
			],
			'image/jpg; charset="\"utf8\""' => [
				'expected' => 'image/jpg; charset="\"utf8\""',
				'mimeType' => new Entity\MimeType('image', 'jpg', ['charset' => '"utf8"']),
			],
			'image/jpg; charset=utf8; attribute=value' => [
				'expected' => 'image/jpg; charset=utf8; attribute=value',
				'mimeType' => new Entity\MimeType('image', 'jpg', ['charset' => 'utf8', 'attribute' => 'value']),
			],
			'empty' => [
				'expected' => 'unkn/unkn',
				'mimeType' => new Entity\MimeType('unkn', 'unkn'),
			],
		];
	}

	/**
	 * @dataProvider dataToString
	 * @param string          $expected
	 * @param Entity\MimeType $mimeType
	 * @return void
	 */
	public function testToString(string $expected, Entity\MimeType $mimeType)
	{
		$this->assertEquals($expected, $mimeType->__toString());
	}

	public function dataRoundtrip(): array
	{
		return [
			['image/jpg'],
			['image/jpg; charset=utf8'],
			['image/jpg; charset="\"utf8\""'],
			['image/jpg; charset=utf8; attribute=value'],
		];
	}

	/**
	 * @dataProvider dataRoundtrip
	 * @param string $expected
	 * @return void
	 */
	public function testRoundtrip(string $expected)
	{
		$factory = new Factory\MimeType(new NullLogger());

		$this->assertEquals($expected, $factory->createFromContentType($expected)->__toString());
	}
}
