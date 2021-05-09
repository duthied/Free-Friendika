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

namespace Friendica\Module\Api\Mastodon;

use Friendica\Core\System;
use Friendica\DI;
use Friendica\Model\Tag;
use Friendica\Module\BaseApi;

/**
 * @see https://docs.joinmastodon.org/methods/instance/trends/
 */
class Trends extends BaseApi
{
	/**
	 * @param array $parameters
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function rawContent(array $parameters = [])
	{
		// Maximum number of results to return. Defaults to 10.
		$limit = (int)!isset($_REQUEST['limit']) ? 10 : $_REQUEST['limit'];

		$trending = [];
		$tags = Tag::getGlobalTrendingHashtags(24, 20);
		foreach ($tags as $tag) {
			$tag['name'] = $tag['term'];
			$hashtag = new \Friendica\Object\Api\Mastodon\Tag(DI::baseUrl(), $tag);
			$trending[] = $hashtag->toArray();
		}

		System::jsonExit(array_slice($trending, 0, $limit));
	}
}
