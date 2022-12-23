<?php
/**
 * @copyright Copyright (C) 2010-2022, the Friendica project
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

use Exception;
use Friendica\Content\Text\HTML;
use Friendica\Network\HTTPException\InternalServerErrorException;
use Friendica\Test\FixtureTest;
use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\UriInterface;

class HTMLTest extends FixtureTest
{
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
	 *
	 * @throws Exception
	 */
	public function testToPlaintext(string $input, string $expected)
	{
		$output = HTML::toPlaintext($input, 0);

		self::assertEquals($expected, $output);
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
			'bug-10877-code-entities' => [
				'expectedBBCode' => "Now playing
[code]
echo \"main(i){for(i=0;;i++)putchar(((i*(i>>8|i>>9)&46&i>>8))^(i&i>>13|i>>6));}\" | gcc -o a.out -x c - 2> /dev/null
./a.out | aplay -q 2> /dev/null
[/code]
its surprisingly good",
				'html' => "<p>Now playing</p><pre><code>echo &quot;main(i){for(i=0;;i++)putchar(((i*(i&gt;&gt;8|i&gt;&gt;9)&amp;46&amp;i&gt;&gt;8))^(i&amp;i&gt;&gt;13|i&gt;&gt;6));}&quot; | gcc -o a.out -x c - 2&gt; /dev/null
./a.out | aplay -q 2&gt; /dev/null</code></pre><p>its surprisingly good</p>",
			],
			'bug-11851-content-0' => [
				'expectedBBCode' => '[url=https://dev-friendica.mrpetovan.com/profile/hypolite]@hypolite[/url] 0',
				'html' => '<p><span class="h-card"><a href="https://dev-friendica.mrpetovan.com/profile/hypolite" class="u-url mention">@<span>hypolite</span></a></span> 0</p>',
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
	 *
	 * @throws InternalServerErrorException
	 */
	public function testToBBCode(string $expectedBBCode, string $html)
	{
		$actual = HTML::toBBCode($html);

		self::assertEquals($expectedBBCode, $actual);
	}

	public function dataXpathQuote(): array
	{
		return [
			'no quotes' => [
				'value' => "foo",
			],
			'double quotes only' => [
				'value' => "\"foo",
			],
			'single quotes only' => [
				'value' => "'foo",
			],
			'both; double quotes in mid-string' => [
				'value' => "'foo\"bar",
			],
			'multiple double quotes in mid-string' => [
				'value' => "'foo\"bar\"baz",
			],
			'string ends with double quotes' => [
				'value' => "'foo\"",
			],
			'string ends with run of double quotes' => [
				'value' => "'foo\"\"",
			],
			'string begins with double quotes' => [
				'value' => "\"'foo",
			],
			'string begins with run of double quotes' => [
				'value' => "\"\"'foo",
			],
			'run of double quotes in mid-string' => [
				'value' => "'foo\"\"bar",
			],
		];
	}

	/**
	 * @dataProvider dataXpathQuote
	 * @param string $value
	 * @return void
	 * @throws \DOMException
	 */
	public function testXpathQuote(string $value)
	{
		$dom = new \DOMDocument();
		$element = $dom->createElement('test');
		$attribute = $dom->createAttribute('value');
		$attribute->value = $value;
		$element->appendChild($attribute);
		$dom->appendChild($element);

		$xpath = new \DOMXPath($dom);

		$result = $xpath->query('//test[@value = ' . HTML::xpathQuote($value) . ']');

		$this->assertInstanceOf(\DOMNodeList::class, $result);
		$this->assertEquals(1, $result->length);
	}

	public function dataCheckRelMeLink(): array
	{
		$aSingleRelValue = new \DOMDocument();
		$aSingleRelValue->load(__DIR__ . '/../../../datasets/dom/relme/a-single-rel-value.html');

		$aMultipleRelValueStart = new \DOMDocument();
		$aMultipleRelValueStart->load(__DIR__ . '/../../../datasets/dom/relme/a-multiple-rel-value-start.html');

		$aMultipleRelValueMiddle = new \DOMDocument();
		$aMultipleRelValueMiddle->load(__DIR__ . '/../../../datasets/dom/relme/a-multiple-rel-value-middle.html');

		$aMultipleRelValueEnd = new \DOMDocument();
		$aMultipleRelValueEnd->load(__DIR__ . '/../../../datasets/dom/relme/a-multiple-rel-value-end.html');

		$linkSingleRelValue = new \DOMDocument();
		$linkSingleRelValue->load(__DIR__ . '/../../../datasets/dom/relme/link-single-rel-value.html');

		$meUrl = new Uri('https://example.com/profile/me');

		return [
			'a-single-rel-value' => [
				'doc' => $aSingleRelValue,
				'meUrl' => $meUrl
			],
			'a-multiple-rel-value-start' => [
				'doc' => $aMultipleRelValueStart,
				'meUrl' => $meUrl
			],
			'a-multiple-rel-value-middle' => [
				'doc' => $aMultipleRelValueMiddle,
				'meUrl' => $meUrl
			],
			'a-multiple-rel-value-end' => [
				'doc' => $aMultipleRelValueEnd,
				'meUrl' => $meUrl
			],
			'link-single-rel-value' => [
				'doc' => $linkSingleRelValue,
				'meUrl' => $meUrl
			],
		];
	}


	/**
	 * @dataProvider dataCheckRelMeLink
	 * @param \DOMDocument $doc
	 * @param UriInterface $meUrl
	 * @return void
	 */
	public function testCheckRelMeLink(\DOMDocument $doc, UriInterface $meUrl)
	{
		$this->assertTrue(HTML::checkRelMeLink($doc, $meUrl));
	}

	public function dataCheckRelMeLinkFail(): array
	{
		$aSingleRelValueFail = new \DOMDocument();
		$aSingleRelValueFail->load(__DIR__ . '/../../../datasets/dom/relme/a-single-rel-value-fail.html');

		$linkSingleRelValueFail = new \DOMDocument();
		$linkSingleRelValueFail->load(__DIR__ . '/../../../datasets/dom/relme/link-single-rel-value-fail.html');

		$meUrl = new Uri('https://example.com/profile/me');

		return [
			'a-single-rel-value-fail' => [
				'doc' => $aSingleRelValueFail,
				'meUrl' => $meUrl
			],
			'link-single-rel-value-fail' => [
				'doc' => $linkSingleRelValueFail,
				'meUrl' => $meUrl
			],
		];
	}


	/**
	 * @dataProvider dataCheckRelMeLinkFail
	 * @param \DOMDocument $doc
	 * @param UriInterface $meUrl
	 * @return void
	 */
	public function testCheckRelMeLinkFail(\DOMDocument $doc, UriInterface $meUrl)
	{
		$this->assertFalse(HTML::checkRelMeLink($doc, $meUrl));
	}
}
