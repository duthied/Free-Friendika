<?php
/**
 * @copyright Copyright (C) 2010-2021, the Friendica project
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

use Friendica\Test\MockedTest;
use Friendica\Test\Util\HTTPInputDataDouble;
use Friendica\Util\HTTPInputData;

/**
 * Testing HTTPInputData
 *
 * @see	HTTPInputData
 */
class HTTPInputDataTest extends MockedTest
{
	/**
	 * Returns the data stream for the unit test
	 * Each array element of the first hierarchy represents one test run
	 * Each array element of the second hierarchy represents the parameters, passed to the test function
	 *
	 * @return array[]
	 */
	public function dataStream()
	{
		return [
			'example' => [
				'contenttype' => 'multipart/form-data;boundary=43395968-f65c-437e-b536-5b33e3e3c7e5;charset=utf8',
				'input'       => file_get_contents(__DIR__ . '/../../datasets/http/example1.httpinput'),
				'expected'    => [
					'variables' => [
						'display_name'      => 'User Name',
						'note'              => 'About me',
						'locked'            => 'false',
						'fields_attributes' => [
							0 => [
								'name'  => 'variable 1',
								'value' => 'value 1',
							],
							1 => [
								'name'  => 'variable 2',
								'value' => 'value 2',
							]
						]
					],
					'files' => []
				]
			]
		];
	}

	/**
	 * Tests the HTTPInputData::process() method
	 *
	 * @param string $contentType The content typer of the transmitted data
	 * @param string $input       The input, we got from the data stream
	 * @param array  $expected    The expected output
	 *
	 * @dataProvider dataStream
	 * @see HTTPInputData::process()
	 */
	public function testHttpInput(string $contentType, string $input, array $expected)
	{
		HTTPInputDataDouble::setPhpInputContentType($contentType);
		HTTPInputDataDouble::setPhpInputContent($input);
		$stream = fopen('php://memory', 'r+');
		fwrite($stream, $input);
		rewind($stream);

		HTTPInputDataDouble::setPhpInputStream($stream);
		$output = HTTPInputDataDouble::process();
		$this->assertEqualsCanonicalizing($expected, $output);
	}
}
