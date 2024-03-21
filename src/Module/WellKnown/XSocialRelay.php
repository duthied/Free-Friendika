<?php
/**
 * @copyright Copyright (C) 2010-2024, the Friendica project
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
use Friendica\Protocol\Relay;
use Friendica\Util\Strings;

/**
 * Node subscription preferences for social relay systems
 * @see https://git.feneas.org/jaywink/social-relay/blob/master/docs/relays.md
 */
class XSocialRelay extends BaseModule
{
	protected function rawContent(array $request = [])
	{
		$config = DI::config();

		$scope = $config->get('system', 'relay_scope');

		$systemTags = [];
		$userTags = [];

		if ($scope == Relay::SCOPE_TAGS) {
			$systemTags = Strings::getTagArrayByString($config->get('system', 'relay_server_tags'));

			if ($config->get('system', 'relay_user_tags')) {
				$userTags = Search::getUserTags();
			}
		}

		$tagList = array_unique(array_merge($systemTags, $userTags));

		$relay = [
			'subscribe' => ($scope != Relay::SCOPE_NONE),
			'scope'     => $scope,
			'tags'      => $tagList,
			'protocols' => [
				'activitypub' => [
					'actor' => DI::baseUrl() . '/friendica',
					'receive' => DI::baseUrl() . '/inbox'
				],
				'dfrn'     => [
					'receive' => DI::baseUrl() . '/dfrn_notify'
				]
			]
		];

		if (DI::config()->get("system", "diaspora_enabled")) {
			$relay['protocols']['diaspora'] = ['receive' => DI::baseUrl() . '/receive/public'];
		}

		$this->jsonExit($relay);
	}
}
