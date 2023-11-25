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

namespace Friendica\Test\src\Factory\Api\Mastodon;

use Friendica\Core\Hook;
use Friendica\DI;
use Friendica\Model\Post;
use Friendica\Test\FixtureTest;

class StatusTest extends FixtureTest
{
	protected $status;

	protected function setUp(): void
	{
		parent::setUp();

		DI::config()->set('system', 'no_smilies', false);
		$this->status = DI::mstdnStatus();

		Hook::register('smilie', 'tests/Util/SmileyWhitespaceAddon.php', 'add_test_unicode_smilies');
		Hook::loadHooks();
	}

	public function testSimpleStatus()
	{
		$post = Post::selectFirst([], ['id' => 13]);
		$this->assertNotNull($post);
		$result = $this->status->createFromUriId($post['uri-id']);
		$this->assertNotNull($result);
	}

	public function testSimpleEmojiStatus()
	{
		$post = Post::selectFirst([], ['id' => 14]);
		$this->assertNotNull($post);
		$result = $this->status->createFromUriId($post['uri-id'])->toArray();
		$this->assertEquals(':like: :friendica: no <code>:dislike</code> :p: :embarrassed: 🤗 ❤ :smileyheart333: 🔥', $result['content']);
		$emojis = array_fill_keys(['like', 'friendica', 'p', 'embarrassed', 'smileyheart333'], true);
		$this->assertEquals(count($emojis), count($result['emojis']));
		foreach ($result['emojis'] as $emoji) {
			$this->assertTrue(array_key_exists($emoji['shortcode'], $emojis));
			$this->assertEquals(0, strpos($emoji['url'], 'http'));
		}
	}
}
