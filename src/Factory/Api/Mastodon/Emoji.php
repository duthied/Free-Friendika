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

namespace Friendica\Factory\Api\Mastodon;

use Friendica\BaseFactory;
use Friendica\Collection\Api\Mastodon\Emojis;

class Emoji extends BaseFactory
{
	public function create(string $shortcode, string $url): \Friendica\Object\Api\Mastodon\Emoji
	{
		return new \Friendica\Object\Api\Mastodon\Emoji($shortcode, $url);
	}

	/**
	 * Creates an emoji collection from shortcode => image mappings.
	 *
	 * @param array $smilies
	 *
	 * @return Emojis
	 */
	public function createCollectionFromArray(array $smilies): Emojis
	{
		$prototype = null;

		$emojis = [];

		foreach ($smilies as $shortcode => $url) {
			if ($shortcode !== '' && $url !== '') {
				$shortcode = trim($shortcode, ':');

				if ($prototype === null) {
					$prototype = $this->create($shortcode, $url);
					$emojis[]  = $prototype;
				} else {
					$emojis[] = \Friendica\Object\Api\Mastodon\Emoji::createFromPrototype($prototype, $shortcode, $url);
				}
			}
		}

		return new Emojis($emojis);
	}

	/**
	 * @param array $smilies as is returned by Smilies::getList()
	 *
	 * @return Emojis
	 */
	public function createCollectionFromSmilies(array $smilies): Emojis
	{
		$emojis = [];
		$icons = $smilies['icons'];
		foreach ($smilies['texts'] as $i => $name) {
			$url = $icons[$i];
			if (preg_match('/src="(.+?)"/', $url, $matches)) {
				$emojis[$name] = $matches[1];
			}
		}
		return self::createCollectionFromArray($emojis);
	}
}
