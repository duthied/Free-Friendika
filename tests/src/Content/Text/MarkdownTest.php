<?php

namespace Friendica\Test\src\Content\Text;

use Friendica\Content\Text\Markdown;
use Friendica\Test\MockedTest;
use Friendica\Test\Util\AppMockTrait;
use Friendica\Test\Util\VFSTrait;

class MarkdownTest extends MockedTest
{
	use VFSTrait;
	use AppMockTrait;

	protected function setUp()
	{
		parent::setUp();
		$this->setUpVfsDir();
		$this->mockApp($this->root);
	}

	public function dataMarkdown()
	{
		$inputFiles = glob(__DIR__ . '/../../../datasets/content/text/markdown/*.md');

		$data = [];

		foreach ($inputFiles as $file) {
			$data[str_replace('.md', '', $file)] = [
				'input'    => file_get_contents($file),
				'expected' => file_get_contents(str_replace('.md', '.html', $file))
			];
		}

		return $data;
	}

	/**
	 * Test convert different input Markdown text into HTML
	 * @dataProvider dataMarkdown
	 *
	 * @param string $input    The Markdown text to test
	 * @param string $expected The expected HTML output
	 * @throws \Exception
	 */
	public function testConvert($input, $expected)
	{
		$output = Markdown::convert($input);

		$this->assertEquals($expected, $output);
	}
}
