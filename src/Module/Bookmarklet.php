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

namespace Friendica\Module;

use Friendica\BaseModule;
use Friendica\Core\ACL;
use Friendica\DI;
use Friendica\Module\Security\Login;
use Friendica\Network\HTTPException;
use Friendica\Util\Strings;

/**
 * Creates a bookmarklet
 * Shows either a editor browser or adds the given bookmarklet to the current user
 */
class Bookmarklet extends BaseModule
{
	public static function content(array $parameters = [])
	{
		$_GET['mode'] = 'minimal';

		$app = DI::app();
		$config = DI::config();

		if (!local_user()) {
			$output = '<h2>' . DI::l10n()->t('Login') . '</h2>';
			$output .= Login::form(DI::args()->getQueryString(), intval($config->get('config', 'register_policy')) === Register::CLOSED ? false : true);
			return $output;
		}

		$referer = Strings::normaliseLink($_SERVER['HTTP_REFERER'] ?? '');
		$page = Strings::normaliseLink(DI::baseUrl()->get() . "/bookmarklet");

		if (!strstr($referer, $page)) {
			if (empty($_REQUEST["url"])) {
				throw new HTTPException\BadRequestException(DI::l10n()->t('This page is missing a url parameter.'));
			}

			$content = add_page_info($_REQUEST["url"]);

			$x = [
				'is_owner'         => true,
				'allow_location'   => $app->user['allow_location'],
				'default_location' => $app->user['default-location'],
				'nickname'         => $app->user['nickname'],
				'lockstate'        => ((is_array($app->user) && ((strlen($app->user['allow_cid'])) || (strlen($app->user['allow_gid'])) || (strlen($app->user['deny_cid'])) || (strlen($app->user['deny_gid'])))) ? 'lock' : 'unlock'),
				'default_perms'    => ACL::getDefaultUserPermissions($app->user),
				'acl'              => ACL::getFullSelectorHTML(DI::page(), $app->user, true),
				'bang'             => '',
				'visitor'          => 'block',
				'profile_uid'      => local_user(),
				'title'            => trim($_REQUEST['title'] ?? '', '*'),
				'content'          => $content
			];
			$output = status_editor($app, $x, 0, false);
			$output .= "<script>window.resizeTo(800,550);</script>";
		} else {
			$output = '<h2>' . DI::l10n()->t('The post was created') . '</h2>';
			$output .= "<script>window.close()</script>";
		}

		return $output;
	}
}
