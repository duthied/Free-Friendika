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

namespace Friendica\Test\src\Util;

use Friendica\Util\JsonLD;
use PHPUnit\Framework\TestCase;

/**
 * JsonLD utility test class
 */
class JsonLDTest extends TestCase
{	
	public function testFetchElementArrayNotFound()
	{
		$object = [];

		$data = JsonLD::fetchElementArray($object, 'field');
		$this->assertNull($data);
	}

	public function testFetchElementArrayFoundEmptyArray()
	{
		$object = ['field' => []];

		$data = JsonLD::fetchElementArray($object, 'field');
		$this->assertSame([[]], $data);
	}

	public function testFetchElementArrayFoundID()
	{
		$object = ['field' => ['value1', ['@id' => 'value2'], ['@id' => 'value3']]];

		$data = JsonLD::fetchElementArray($object, 'field', '@id');
		$this->assertSame(['value1', 'value2', 'value3'], $data);
	}

	public function testFetchElementArrayFoundArrays()
	{
		$object = ['field' => [['subfield11' => 'value11', 'subfield12' => 'value12'],
			['subfield21' => 'value21', 'subfield22' => 'value22']]];

		$expect = [['subfield11' => 'value11', 'subfield12' => 'value12'],
			['subfield21' => 'value21', 'subfield22' => 'value22']];

		$data = JsonLD::fetchElementArray($object, 'field');
		$this->assertSame($expect, $data);
	}

	public function testFetchElementNotFound()
	{
		$object = [];

		$data = JsonLD::fetchElement($object, 'field');
		$this->assertNull($data);
	}

	public function testFetchElementFound()
	{
		$object = ['field' => 'value'];

		$data = JsonLD::fetchElement($object, 'field');
		$this->assertSame('value', $data);
	}

	public function testFetchElementFoundEmptyString()
	{
		$object = ['field' => ''];

		$data = JsonLD::fetchElement($object, 'field');
		$this->assertSame('', $data);
	}

	public function testFetchElementFoundID()
	{
		$object = ['field' => ['field2' => 'value2', '@id' => 'value', 'field3' => 'value3']];

		$data = JsonLD::fetchElement($object, 'field');
		$this->assertSame('value', $data);
	}

	public function testFetchElementType()
	{
		$object = ['source' => ['content' => 'body', 'mediaType' => 'text/bbcode']];

		$data = JsonLD::fetchElement($object, 'source', 'content', 'mediaType', 'text/bbcode');
		$this->assertSame('body', $data);
	}

	public function testFetchElementTypeArray()
	{
		$object = ['source' => [['content' => 'body2', 'mediaType' => 'text/html'],
			['content' => 'body', 'mediaType' => 'text/bbcode']]];

		$data = JsonLD::fetchElement($object, 'source', 'content', 'mediaType', 'text/bbcode');
		$this->assertSame('body', $data);
	}
}
