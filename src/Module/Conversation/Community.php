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
 * See update_profile.php for documentation
 */

namespace Friendica\Module\Conversation;

use Friendica\BaseModule;
use Friendica\Content\BoundariesPager;
use Friendica\Content\Feature;
use Friendica\Content\Nav;
use Friendica\Content\Widget\TrendingTags;
use Friendica\Core\ACL;
use Friendica\Core\Renderer;
use Friendica\Core\Session;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Item;
use Friendica\Model\User;
use Friendica\Network\HTTPException;

class Community extends BaseModule
{
	protected static $page_style;
	protected static $content;
	protected static $accounttype;
	protected static $itemsPerPage;
	protected static $since_id;
	protected static $max_id;

	public static function content(array $parameters = [])
	{
		self::parseRequest($parameters);

		$tabs = [];

		if ((Session::isAuthenticated() || in_array(self::$page_style, [CP_USERS_AND_GLOBAL, CP_USERS_ON_SERVER])) && empty(DI::config()->get('system', 'singleuser'))) {
			$tabs[] = [
				'label' => DI::l10n()->t('Local Community'),
				'url' => 'community/local',
				'sel' => self::$content == 'local' ? 'active' : '',
				'title' => DI::l10n()->t('Posts from local users on this server'),
				'id' => 'community-local-tab',
				'accesskey' => 'l'
			];
		}

		if (Session::isAuthenticated() || in_array(self::$page_style, [CP_USERS_AND_GLOBAL, CP_GLOBAL_COMMUNITY])) {
			$tabs[] = [
				'label' => DI::l10n()->t('Global Community'),
				'url' => 'community/global',
				'sel' => self::$content == 'global' ? 'active' : '',
				'title' => DI::l10n()->t('Posts from users of the whole federated network'),
				'id' => 'community-global-tab',
				'accesskey' => 'g'
			];
		}

		$tab_tpl = Renderer::getMarkupTemplate('common_tabs.tpl');
		$o = Renderer::replaceMacros($tab_tpl, ['$tabs' => $tabs]);

		Nav::setSelected('community');

		$items = self::getItems();

		if (!DBA::isResult($items)) {
			info(DI::l10n()->t('No results.'));
			return $o;
		}

		// We need the editor here to be able to reshare an item.
		if (Session::isAuthenticated()) {
			$x = [
				'is_owner' => true,
				'allow_location' => DI::app()->user['allow_location'],
				'default_location' => DI::app()->user['default-location'],
				'nickname' => DI::app()->user['nickname'],
				'lockstate' => (is_array(DI::app()->user) && (strlen(DI::app()->user['allow_cid']) || strlen(DI::app()->user['allow_gid']) || strlen(DI::app()->user['deny_cid']) || strlen(DI::app()->user['deny_gid'])) ? 'lock' : 'unlock'),
				'acl' => ACL::getFullSelectorHTML(DI::page(), DI::app()->user, true),
				'bang' => '',
				'visitor' => 'block',
				'profile_uid' => local_user(),
			];
			$o .= status_editor(DI::app(), $x, 0, true);
		}

		$o .= conversation(DI::app(), $items, 'community', false, false, 'commented', local_user());

		$pager = new BoundariesPager(
			DI::l10n(),
			DI::args()->getQueryString(),
			$items[0]['commented'],
			$items[count($items) - 1]['commented'],
			self::$itemsPerPage
		);

		$o .= $pager->renderMinimal(count($items));

		if (Feature::isEnabled(local_user(), 'trending_tags')) {
			DI::page()['aside'] .= TrendingTags::getHTML(self::$content);
		}

		$t = Renderer::getMarkupTemplate("community.tpl");
		return Renderer::replaceMacros($t, [
			'$content' => $o,
			'$header' => '',
			'$show_global_community_hint' => (self::$content == 'global') && DI::config()->get('system', 'show_global_community_hint'),
			'$global_community_hint' => DI::l10n()->t("This community stream shows all public posts received by this node. They may not reflect the opinions of this node’s users.")
		]);
	}

	/**
	 * Computes module parameters from the request and local configuration
	 *
	 * @param array $parameters
	 * @throws HTTPException\BadRequestException
	 * @throws HTTPException\ForbiddenException
	 */
	protected static function parseRequest(array $parameters)
	{
		if (DI::config()->get('system', 'block_public') && !Session::isAuthenticated()) {
			throw new HTTPException\ForbiddenException(DI::l10n()->t('Public access denied.'));
		}

		self::$page_style = DI::config()->get('system', 'community_page_style');

		if (self::$page_style == CP_NO_INTERNAL_COMMUNITY) {
			throw new HTTPException\ForbiddenException(DI::l10n()->t('Access denied.'));
		}

		switch ($parameters['accounttype'] ?? '') {
			case 'person':
				self::$accounttype = User::ACCOUNT_TYPE_PERSON;
				break;
			case 'organisation':
				self::$accounttype = User::ACCOUNT_TYPE_ORGANISATION;
				break;
			case 'news':
				self::$accounttype = User::ACCOUNT_TYPE_NEWS;
				break;
			case 'community':
				self::$accounttype = User::ACCOUNT_TYPE_COMMUNITY;
				break;
			default:
				self::$accounttype = null;
				break;
		}

		self::$content = $parameters['content'] ?? '';
		if (!self::$content) {
			if (!empty(DI::config()->get('system', 'singleuser'))) {
				// On single user systems only the global page does make sense
				self::$content = 'global';
			} else {
				// When only the global community is allowed, we use this as default
				self::$content = self::$page_style == CP_GLOBAL_COMMUNITY ? 'global' : 'local';
			}
		}

		if (!in_array(self::$content, ['local', 'global'])) {
			throw new HTTPException\BadRequestException(DI::l10n()->t('Community option not available.'));
		}

		// Check if we are allowed to display the content to visitors
		if (!Session::isAuthenticated()) {
			$available = self::$page_style == CP_USERS_AND_GLOBAL;

			if (!$available) {
				$available = (self::$page_style == CP_USERS_ON_SERVER) && (self::$content == 'local');
			}

			if (!$available) {
				$available = (self::$page_style == CP_GLOBAL_COMMUNITY) && (self::$content == 'global');
			}

			if (!$available) {
				throw new HTTPException\ForbiddenException(DI::l10n()->t('Not available.'));
			}
		}

		if (DI::mode()->isMobile()) {
			self::$itemsPerPage = DI::pConfig()->get(local_user(), 'system', 'itemspage_mobile_network',
				DI::config()->get('system', 'itemspage_network_mobile'));
		} else {
			self::$itemsPerPage = DI::pConfig()->get(local_user(), 'system', 'itemspage_network',
				DI::config()->get('system', 'itemspage_network'));
		}

		// now that we have the user settings, see if the theme forces
		// a maximum item number which is lower then the user choice
		if ((DI::app()->force_max_items > 0) && (DI::app()->force_max_items < self::$itemsPerPage)) {
			self::$itemsPerPage = DI::app()->force_max_items;
		}

		self::$since_id = $_GET['since_id'] ?? null;
		self::$max_id   = $_GET['max_id']   ?? null;
	}

	/**
	 * Computes the displayed items.
	 *
	 * Community pages have a restriction on how many successive posts by the same author can show on any given page,
	 * so we may have to retrieve more content beyond the first query
	 *
	 * @return array
	 * @throws \Exception
	 */
	protected static function getItems()
	{
		$items = self::selectItems(self::$since_id, self::$max_id, self::$itemsPerPage);

		$maxpostperauthor = (int) DI::config()->get('system', 'max_author_posts_community_page');
		if ($maxpostperauthor != 0 && self::$content == 'local') {
			$count = 1;
			$previousauthor = '';
			$numposts = 0;
			$selected_items = [];

			while (count($selected_items) < self::$itemsPerPage && ++$count < 50 && count($items) > 0) {
				foreach ($items as $item) {
					if ($previousauthor == $item["author-link"]) {
						++$numposts;
					} else {
						$numposts = 0;
					}
					$previousauthor = $item["author-link"];

					if (($numposts < $maxpostperauthor) && (count($selected_items) < self::$itemsPerPage)) {
						$selected_items[] = $item;
					}
				}

				// If we're looking at a "previous page", the lookup continues forward in time because the list is
				// sorted in chronologically decreasing order
				if (isset(self::$since_id)) {
					self::$since_id = $items[0]['commented'];
				} else {
					// In any other case, the lookup continues backwards in time
					self::$max_id = $items[count($items) - 1]['commented'];
				}

				$items = self::selectItems(self::$since_id, self::$max_id, self::$itemsPerPage);
			}
		} else {
			$selected_items = $items;
		}

		return $selected_items;
	}

	/**
	 * Database query for the comunity page
	 *
	 * @param $since_id
	 * @param $max_id
	 * @param $itemspage
	 * @return array
	 * @throws \Exception
	 * @TODO Move to repository/factory
	 */
	private static function selectItems($since_id, $max_id, $itemspage)
	{
		$r = false;

		if (self::$content == 'local') {
			if (!is_null(self::$accounttype)) {
				$condition = ["`wall` AND `origin` AND `private` = ? AND `owner`.`contact-type` = ?", Item::PUBLIC, self::$accounttype];
			} else {
				$condition = ["`wall` AND `origin` AND `private` = ?", Item::PUBLIC];
			}
		} elseif (self::$content == 'global') {
			if (!is_null(self::$accounttype)) {
				$condition = ["`uid` = ? AND `private` = ? AND `owner`.`contact-type` = ?", 0, Item::PUBLIC, self::$accounttype];
			} else {
				$condition = ["`uid` = ? AND `private` = ?", 0, Item::PUBLIC];
			}
		} else {
			return [];
		}

		if (isset($max_id)) {
			$condition[0] .= " AND `commented` < ?";
			$condition[] = $max_id;
		}

		if (isset($since_id)) {
			$condition[0] .= " AND `commented` > ?";
			$condition[] = $since_id;
		}

		$r = Item::selectThreadForUser(0, ['uri', 'commented', 'author-link'], $condition, ['order' => ['commented' => true], 'limit' => $itemspage]);

		return DBA::toArray($r);
	}
}
