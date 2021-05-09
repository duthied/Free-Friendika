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

namespace Friendica\Module;

use Friendica\BaseModule;
use Friendica\Core\Session;
use Friendica\DI;
use Friendica\Model\Profile;
use Friendica\Model\User;
use Friendica\Network\HTTPException;

/**
 * Loads a profile for the HoverCard view
 */
class HoverCard extends BaseModule
{
	public static function rawContent(array $parameters = [])
	{
		$a = DI::app();

		if ((local_user()) && ($parameters['action'] ?? '') === 'view') {
			// A logged in user views a profile of a user
			$nickname = $a->user['nickname'];
		} elseif (empty($parameters['action'])) {
			// Show the profile hovercard
			$nickname = $parameters['profile'];
		} else {
			throw new HTTPException\NotFoundException(DI::l10n()->t('No profile'));
		}

		Profile::load($a, $nickname);

		if (empty($a->profile)) {
			throw new HTTPException\NotFoundException(DI::l10n()->t('User not found.'));
		}

		$page = DI::page();

		if (!empty($a->profile['page-flags']) && ($a->profile['page-flags'] == User::PAGE_FLAGS_COMMUNITY)) {
			$page['htmlhead'] .= '<meta name="friendica.community" content="true" />';
		}
		if (!empty($a->profile['openidserver'])) {
			$page['htmlhead'] .= '<link rel="openid.server" href="' . $a->profile['openidserver'] . '" />' . "\r\n";
		}
		if (!empty($a->profile['openid'])) {
			$delegate         = ((strstr($a->profile['openid'], '://')) ? $a->profile['openid'] : 'http://' . $a->profile['openid']);
			$page['htmlhead'] .= '<link rel="openid.delegate" href="' . $delegate . '" />' . "\r\n";
		}

		// check if blocked
		if (DI::config()->get('system', 'block_public') && !Session::isAuthenticated()) {
			$keywords = $a->profile['pub_keywords'] ?? '';
			$keywords = str_replace([',', ' ', ',,'], [' ', ',', ','], $keywords);
			if (strlen($keywords)) {
				$page['htmlhead'] .= '<meta name="keywords" content="' . $keywords . '" />' . "\r\n";
			}
		}

		$baseUrl = DI::baseUrl();

		$uri = urlencode('acct:' . $a->profile['nickname'] . '@' . $baseUrl->getHostname() . ($baseUrl->getUrlPath() ? '/' . $baseUrl->getUrlPath() : ''));

		$page['htmlhead'] .= '<meta name="dfrn-global-visibility" content="' . ($a->profile['net-publish'] ? 'true' : 'false') . '" />' . "\r\n";
		$page['htmlhead'] .= '<link rel="alternate" type="application/atom+xml" href="' . $baseUrl->get() . '/dfrn_poll/' . $nickname . '" />' . "\r\n";
		$page['htmlhead'] .= '<link rel="lrdd" type="application/xrd+xml" href="' . $baseUrl->get() . '/xrd/?uri=' . $uri . '" />' . "\r\n";
		header('Link: <' . $baseUrl->get() . '/xrd/?uri=' . $uri . '>; rel="lrdd"; type="application/xrd+xml"', false);

		foreach (['request', 'confirm', 'notify', 'poll'] as $dfrn) {
			$page['htmlhead'] .= "<link rel=\"dfrn-{$dfrn}\" href=\"" . $baseUrl->get() . "/dfrn_{$dfrn}/{$nickname}\" />\r\n";
		}
	}
}
