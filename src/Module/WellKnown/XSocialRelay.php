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

namespace Friendica\Module\WellKnown;

use Friendica\BaseModule;
use Friendica\DI;
use Friendica\Model\Search;

/**
 * Node subscription preferences for social realy systems
 * @see https://git.feneas.org/jaywink/social-relay/blob/master/docs/relays.md
 */
class XSocialRelay extends BaseModule
{
	public static function rawContent(array $parameters = [])
	{
		$config = DI::config();

		$subscribe = $config->get('system', 'relay_subscribe', false);

		if ($subscribe) {
			$scope = $config->get('system', 'relay_scope', SR_SCOPE_ALL);
		} else {
			$scope = SR_SCOPE_NONE;
		}

		$systemTags = [];
		$userTags = [];

		if ($scope == SR_SCOPE_TAGS) {
			$server_tags = $config->get('system', 'relay_server_tags');
			$tagitems = explode(',', $server_tags);

			/// @todo Check if it was better to use "strtolower" on the tags
			foreach ($tagitems AS $tag) {
				$systemTags[] = trim($tag, '# ');
			}

			if ($config->get('system', 'relay_user_tags')) {
				$userTags = Search::getUserTags();
			}
		}

		$tagList = array_unique(array_merge($systemTags, $userTags));

		$relay = [
			'subscribe' => $subscribe,
			'scope'     => $scope,
			'tags'      => $tagList,
			'protocols' => [
				'diaspora' => [
					'receive' => DI::baseUrl()->get() . '/receive/public'
				],
				'dfrn'     => [
					'receive' => DI::baseUrl()->get() . '/dfrn_notify'
				]
			]
		];

		header('Content-type: application/json; charset=utf-8');
		echo json_encode($relay, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
		exit;
	}
}
