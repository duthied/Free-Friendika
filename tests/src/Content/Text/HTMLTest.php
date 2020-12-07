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

namespace Friendica\Test\src\Content\Text;

use Friendica\Content\Text\HTML;
use Friendica\Test\MockedTest;
use Friendica\Test\Util\AppMockTrait;
use Friendica\Test\Util\VFSTrait;

class HTMLTest extends MockedTest
{
	use VFSTrait;
	use AppMockTrait;

	protected function setUp()
	{
		parent::setUp();
		$this->setUpVfsDir();
		$this->mockApp($this->root);
	}

	public function dataHTML()
	{
		$inputFiles = glob(__DIR__ . '/../../../datasets/content/text/html/*.html');

		$data = [];

		foreach ($inputFiles as $file) {
			$data[str_replace('.html', '', $file)] = [
				'input'    => file_get_contents($file),
				'expected' => file_get_contents(str_replace('.html', '.txt', $file))
			];
		}

		return $data;
	}

	/**
	 * Test convert different input Markdown text into HTML
	 *
	 * @dataProvider dataHTML
	 *
	 * @param string $input    The Markdown text to test
	 * @param string $expected The expected HTML output
	 * @throws \Exception
	 */
	public function testToPlaintext($input, $expected)
	{
		$output = HTML::toPlaintext($input, 0);

		$this->assertEquals($expected, $output);
	}

	public function dataHTMLText()
	{
		return [
			'bug-7665-audio-tag' => [
				'expectedBBCode' => '[audio]http://www.cendrones.fr/colloque2017/jonathanbocquet.mp3[/audio]',
				'html' => '<audio src="http://www.cendrones.fr/colloque2017/jonathanbocquet.mp3" controls="controls"><a href="http://www.cendrones.fr/colloque2017/jonathanbocquet.mp3">http://www.cendrones.fr/colloque2017/jonathanbocquet.mp3</a></audio>',
			],
			'bug-8075-html-tags' => [
				'expectedBBCode' => "<big rant here> I don't understand tests",
				'html' => "&lt;big rant here&gt; I don't understand tests",
			],
		];
	}

	/**
	 * Test convert bbcodes to HTML
	 *
	 * @dataProvider dataHTMLText
	 *
	 * @param string $expectedBBCode Expected BBCode output
	 * @param string $html           HTML text
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public function testToBBCode($expectedBBCode, $html)
	{
		$actual = HTML::toBBCode($html);

		$this->assertEquals($expectedBBCode, $actual);
	}
}
