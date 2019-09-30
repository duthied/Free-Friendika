<?php

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
