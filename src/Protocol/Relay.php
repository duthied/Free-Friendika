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

namespace Friendica\Protocol;

use Friendica\Content\Text\BBCode;
use Friendica\Core\Logger;
use Friendica\DI;
use Friendica\Model\Search;

/**
 * Base class for relay handling
 */
class Relay
{
	/**
	 * Check if a post is wanted
	 *
	 * @param array $tags
	 * @param string $body
	 * @param string $url
	 * @return boolean "true" is the post is wanted by the system
	 */
	public static function isSolicitedPost(array $tags, string $body, string $url, string $network = '')
	{
		$config = DI::config();

		$subscribe = $config->get('system', 'relay_subscribe', false);
		if ($subscribe) {
			$scope = $config->get('system', 'relay_scope', SR_SCOPE_ALL);
		} else {
			$scope = SR_SCOPE_NONE;
		}

		if ($scope == SR_SCOPE_NONE) {
			Logger::info('Server does not accept relay posts - rejected', ['network' => $network, 'url' => $url]);
			return false;
		}

		$systemTags = [];
		$userTags = [];
		$denyTags = [];

		if ($scope == SR_SCOPE_TAGS) {
			$server_tags = $config->get('system', 'relay_server_tags');
			$tagitems = explode(',', mb_strtolower($server_tags));
			foreach ($tagitems AS $tag) {
				$systemTags[] = trim($tag, '# ');
			}

			if ($config->get('system', 'relay_user_tags')) {
				$userTags = Search::getUserTags();
			}
		}

		$tagList = array_unique(array_merge($systemTags, $userTags));

		$deny_tags = $config->get('system', 'relay_deny_tags');
		$tagitems = explode(',', mb_strtolower($deny_tags));
		foreach ($tagitems AS $tag) {
			$tag = trim($tag, '# ');
			$denyTags[] = $tag;
		}

		if (!empty($tagList) || !empty($denyTags)) {
			$content = mb_strtolower(BBCode::toPlaintext($body, false));

			foreach ($tags as $tag) {
				$tag = mb_strtolower($tag);
				if (in_array($tag, $denyTags)) {
					Logger::info('Unwanted hashtag found - rejected', ['hashtag' => $tag, 'network' => $network, 'url' => $url]);
					return false;
				}

				if (in_array($tag, $tagList)) {
					Logger::info('Subscribed hashtag found - accepted', ['hashtag' => $tag, 'network' => $network, 'url' => $url]);
					return true;
				}

				// We check with "strpos" for performance issues. Only when this is true, the regular expression check is used
				// RegExp is taken from here: https://medium.com/@shiba1014/regex-word-boundaries-with-unicode-207794f6e7ed
				if ((strpos($content, $tag) !== false) && preg_match('/(?<=[\s,.:;"\']|^)' . preg_quote($tag, '/') . '(?=[\s,.:;"\']|$)/', $content)) {
					Logger::info('Subscribed hashtag found in content - accepted', ['hashtag' => $tag, 'network' => $network, 'url' => $url]);
					return true;
				}
			}
		}

		if ($scope == SR_SCOPE_ALL) {
			Logger::info('Server accept all posts - accepted', ['network' => $network, 'url' => $url]);
			return true;
		}

		Logger::info('No matching hashtags found - rejected', ['network' => $network, 'url' => $url]);
		return false;
	}
}
