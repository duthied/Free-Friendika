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

namespace Friendica\Test\src\Protocol\ActivityPub;

use Friendica\DI;
use Friendica\Model\Post;
use Friendica\Protocol\ActivityPub\Transmitter;
use Friendica\Test\FixtureTest;

class TransmitterTest extends FixtureTest
{
	protected function setUp(): void
	{
		parent::setUp();

		DI::config()->set('system', 'no_smilies', false);
	}

	public function testEmojiPost()
	{
		$post = Post::selectFirst([], ['id' => 14]);
		$this->assertNotNull($post);
		$note = Transmitter::createNote($post);
		$this->assertNotNull($note);

		$this->assertEquals(':like: :friendica: no <code>:dislike</code> :p: :embarrassed:', $note['content']);
		$emojis = array_fill_keys(['like', 'friendica', 'p', 'embarrassed'], true);
		$this->assertEquals(count($emojis), count($note['tag']));
		foreach ($note['tag'] as $emoji) {
			$this->assertTrue(array_key_exists($emoji['name'], $emojis));
			$this->assertEquals('Emoji', $emoji['type']);
		}
	}
}
