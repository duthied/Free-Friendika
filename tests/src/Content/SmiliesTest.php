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
 * Created by PhpStorm.
 * User: benlo
 * Date: 25/03/19
 * Time: 21:36
 */

namespace Friendica\Test\src\Content;

use Friendica\Content\Smilies;
use Friendica\Core\Hook;
use Friendica\DI;
use Friendica\Network\HTTPException\InternalServerErrorException;
use Friendica\Test\FixtureTest;

class SmiliesTest extends FixtureTest
{
	protected function setUp(): void
	{
		parent::setUp();

		DI::config()->set('system', 'no_smilies', false);

		Hook::register('smilie', 'tests/Util/SmileyWhitespaceAddon.php', 'add_test_unicode_smilies');
		Hook::loadHooks();
	}

	public function dataLinks()
	{
		return [
			/** @see https://github.com/friendica/friendica/pull/6933 */
			'bug-6933-1' => [
				'data' => '<code>/</code>',
				'smilies' => ['texts' => [], 'icons' => []],
				'expected' => '<code>/</code>',
			],
			'bug-6933-2' => [
				'data' => '<code>code</code>',
				'smilies' => ['texts' => [], 'icons' => []],
				'expected' => '<code>code</code>',
			],
		];
	}

	/**
	 * Test replace smilies in different texts
	 *
	 * @dataProvider dataLinks
	 *
	 * @param string $text     Test string
	 * @param array  $smilies  List of smilies to replace
	 * @param string $expected Expected result
	 *
	 * @throws InternalServerErrorException
	 */
	public function testReplaceFromArray(string $text, array $smilies, string $expected)
	{
		$output = Smilies::replaceFromArray($text, $smilies);
		self::assertEquals($expected, $output);
	}

	public function dataIsEmojiPost(): array
	{
		return [
			'emoji' => [
				'expected' => true,
				'body' => '👀',
			],
			'emojis' => [
				'expected' => true,
				'body' => '👀🤷',
			],
			'emoji+whitespace' => [
				'expected' => true,
				'body' => ' 👀 ',
			],
			'empty' => [
				'expected' => false,
				'body' => '',
			],
			'whitespace' => [
				'expected' => false,
				'body' => '
				',
			],
			'emoji+ASCII' => [
				'expected' => false,
				'body' => '🤷a',
			],
			'HTML entity whitespace' => [
				'expected' => false,
				'body' => '&nbsp;',
			],
			'HTML entity else' => [
				'expected' => false,
				'body' => '&deg;',
			],
			'emojis+HTML whitespace' => [
				'expected' => true,
				'body' => '👀&nbsp;🤷',
			],
			'emojis+HTML else' => [
				'expected' => false,
				'body' => '👀&lt;🤷',
			],
			'zwj' => [
				'expected' => true,
				'body' => '👨‍👨‍👧‍',
			],
			'zwj+whitespace' => [
				'expected' => true,
				'body' => ' 👨‍👨‍👧‍ ',
			],
			'zwj+HTML whitespace' => [
				'expected' => true,
				'body' => '&nbsp;👨‍👨‍👧‍&nbsp;',
			],
		];
	}

	/**
	 * @dataProvider dataIsEmojiPost
	 *
	 * @param bool   $expected
	 * @param string $body
	 * @return void
	 */
	public function testIsEmojiPost(bool $expected, string $body)
	{
		$this->assertEquals($expected, Smilies::isEmojiPost($body));
	}


	public function dataReplace(): array
	{
		$data = [
			'simple-1' => [
				'expected' => 'alt=":-p"',
				'body' => ':-p',
			],
			'simple-1' => [
				'expected' => 'alt=":-p"',
				'body' => ' :-p ',
			],
			'word-boundary-1' => [
				'expected' => ':-pppp',
				'body' => ':-pppp',
			],
			'word-boundary-2' => [
				'expected' => '~friendicaca',
				'body' => '~friendicaca',
			],
			'symbol-boundary-1' => [
				'expected' => 'alt=":-p"',
				'body' => '(:-p)',
			],
			'hearts-1' => [
				'expected' => '❤ (❤) ❤',
				'body' => '&lt;3 (&lt;3) &lt;3',
			],
			'hearts-8' => [
				'expected' => '(❤❤❤❤❤❤❤❤)',
				'body' => '(&lt;33333333)',
			],
			'no-hearts-1' => [
				'expected' => '(&lt;30)',
				'body' => '(&lt;30)',
			],
			'no-hearts-2' => [
				'expected' => '(3&lt;33)',
				'body' => '(3&lt;33)',
			],
			'space' => [
				'expected' => 'alt="smiley-heart"',
				'body' => ':smiley heart 333:',
			],
			'substitution-1' => [
				'expected' => '&#x1F525;',
				'body' => '⽕',
			],
			'substitution-2' => [
				'expected' => '&#x1F917;',
				'body' => ':hugging face:',
			],
			'substitution-3' => [
				'expected' => '&#x1F92D;',
				'body' => ':face with hand over mouth:',
			],
			'mixed' => [
				'expected' => '&#x1F525; &#x1F92D; invalid:hugging face: &#x1F917;',
				'body' => '⽕ :face with hand over mouth: invalid:hugging face: :hugging face:',
			],
		];
		foreach ([':-[', ':-D', 'o.O'] as $emoji) {
			foreach (['A', '_', ':', '-'] as $prefix) {
				foreach (['', ' ', 'A', ':', '-'] as $suffix) {
					$no_smile = ($prefix !== '' && ctype_alnum($prefix)) || ($suffix !== '' && ctype_alnum($suffix));
					$s = $prefix . $emoji . $suffix;
					$data[] = [
						'expected' => $no_smile ? $s : 'alt="' . $emoji . '"',
						'body' => $s,
					];
				}
			}
		}
		return $data;
	}

	/**
	 * @dataProvider dataReplace
	 *
	 * @param string $expected
	 * @param string $body
	 */
	public function testReplace(string $expected, string $body)
	{
		$result = Smilies::replace($body);
		$this->assertStringContainsString($expected, $result);
	}

	public function dataExtractUsedSmilies(): array
	{
		return [
			'symbols' => [
				'expected' => ['p', 'heart', 'embarrassed', 'kiss'],
				'body' => ':-p &lt;3 ":-[:-"',
				'normalized' => ':p: :heart: ":embarrassed::kiss:',
			],
			'single-smiley' => [
				'expected' => ['like'],
				'body' => ':like',
				'normalized' => ':like:',
			],
			'multiple-smilies' => [
				'expected' => ['like', 'dislike'],
				'body' => ':like :dislike',
				'normalized' => ':like: :dislike:',
			],
			'nosmile' => [
				'expected' => [],
				'body' => '[nosmile] :like :like',
				'normalized' => '[nosmile] :like :like'
			],
			'in-code' => [
				'expected' => [],
				'body' => '[code]:like :like :like[/code]',
				'normalized' => '[code]:like :like :like[/code]'
			],
			'~friendica' => [
				'expected' => ['friendica'],
				'body' => '~friendica',
				'normalized' => ':friendica:'
			],
			'space' => [
				'expected' => ['smileyheart333'],
				'body' => ':smiley heart 333:',
				'normalized' => ':smileyheart333:'
			],
			'substitution-1' => [
				'expected' => [],
				'body' => '⽕',
				'normalized' => '&#x1F525;',
			],
			'substitution-2' => [
				'expected' => [],
				'body' => ':hugging face:',
				'normalized' => '&#x1F917;',
			],
			'substitution-3' => [
				'expected' => [],
				'body' => ':face with hand over mouth:',
				'normalized' => '&#x1F92D;',
			],
			'mixed' => [
				'expected' => [],
				'body' => '⽕ :face with hand over mouth: invalid:hugging face: :hugging face:',
				'normalized' => '&#x1F525; &#x1F92D; invalid:hugging face: &#x1F917;',
			],
		];
	}

	/**
	 * @dataProvider dataExtractUsedSmilies
	 *
	 * @param array  $expected
	 * @param string $body
	 * @param stirng $normalized
	 */
	public function testExtractUsedSmilies(array $expected, string $body, string $normalized)
	{
		$extracted = Smilies::extractUsedSmilies($body, $converted);
		$expected = array_fill_keys($expected, true);
		$this->assertEquals($normalized, $converted);
		foreach (array_keys($extracted) as $shortcode) {
			$this->assertArrayHasKey($shortcode, $expected);
		}
		$this->assertEquals(count($expected), count($extracted));
	}
}
