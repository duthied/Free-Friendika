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
 * See update_profile.php for documentation
 */

namespace Friendica\Module\Conversation;

use Friendica\BaseModule;
use Friendica\Content\BoundariesPager;
use Friendica\Content\Conversation;
use Friendica\Content\Feature;
use Friendica\Content\Nav;
use Friendica\Content\Text\HTML;
use Friendica\Content\Widget;
use Friendica\Content\Widget\TrendingTags;
use Friendica\Core\Cache\Enum\Duration;
use Friendica\Core\Renderer;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Model\Post;
use Friendica\Model\User;
use Friendica\Module\Security\Login;
use Friendica\Network\HTTPException;

class Channel extends BaseModule
{
	const WHATSHOT  = 'whatshot';
	const FORYOU    = 'foryou';
	const FOLLOWERS = 'followers';
	/**
	 * @}
	 */

	protected static $content;
	protected static $accountTypeString;
	protected static $accountType;
	protected static $itemsPerPage;
	protected static $min_id;
	protected static $max_id;
	protected static $item_id;

	protected function content(array $request = []): string
	{
		if (!DI::userSession()->getLocalUserId()) {
			return Login::form();
		}

		$this->parseRequest();

		$t = Renderer::getMarkupTemplate("community.tpl");
		$o = Renderer::replaceMacros($t, [
			'$content' => '',
			'$header'  => '',
		]);

		if (DI::pConfig()->get(DI::userSession()->getLocalUserId(), 'system', 'infinite_scroll')) {
			$tpl = Renderer::getMarkupTemplate('infinite_scroll_head.tpl');
			$o .= Renderer::replaceMacros($tpl, ['$reload_uri' => DI::args()->getQueryString()]);
		}

		if (empty($_GET['mode']) || ($_GET['mode'] != 'raw')) {
			$tabs = [];

			$tabs[] = [
				'label'     => DI::l10n()->t('For you'),
				'url'       => 'channel/' . self::FORYOU,
				'sel'       => self::$content == self::FORYOU ? 'active' : '',
				'title'     => DI::l10n()->t('Posts from contacts you interact with and who interact with you'),
				'id'        => 'channel-foryou-tab',
				'accesskey' => 'y'
			];

			$tabs[] = [
				'label'     => DI::l10n()->t('Followers'),
				'url'       => 'channel/' . self::FOLLOWERS,
				'sel'       => self::$content == self::FOLLOWERS ? 'active' : '',
				'title'     => DI::l10n()->t('Posts from your followers that you don\'t follow'),
				'id'        => 'channel-followers-tab',
				'accesskey' => 'f'
			];

			$tabs[] = [
				'label'     => DI::l10n()->t('Whats Hot'),
				'url'       => 'channel/' . self::WHATSHOT,
				'sel'       => self::$content == self::WHATSHOT ? 'active' : '',
				'title'     => DI::l10n()->t('Posts with a lot of interactions'),
				'id'        => 'channel-whatshot-tab',
				'accesskey' => 'h'
			];

			$tab_tpl = Renderer::getMarkupTemplate('common_tabs.tpl');
			$o .= Renderer::replaceMacros($tab_tpl, ['$tabs' => $tabs]);

			Nav::setSelected('channel');

			DI::page()['aside'] .= Widget::accountTypes('channel/' . self::$content, self::$accountTypeString);

			if ((self::$content != self::FOLLOWERS) && DI::config()->get('system', 'community_no_sharer')) {
				$path = self::$content;
				if (!empty($this->parameters['accounttype'])) {
					$path .= '/' . $this->parameters['accounttype'];
				}
				$query_parameters = [];

				if (!empty($_GET['min_id'])) {
					$query_parameters['min_id'] = $_GET['min_id'];
				}
				if (!empty($_GET['max_id'])) {
					$query_parameters['max_id'] = $_GET['max_id'];
				}
				if (!empty($_GET['last_created'])) {
					$query_parameters['max_id'] = $_GET['last_created'];
				}

				$path_all       = $path . (!empty($query_parameters) ? '?' . http_build_query($query_parameters) : '');
				$path_no_sharer = $path . '?' . http_build_query(array_merge($query_parameters, ['no_sharer' => true]));
				DI::page()['aside'] .= Renderer::replaceMacros(Renderer::getMarkupTemplate('widget/community_sharer.tpl'), [
					'$title'           => DI::l10n()->t('Own Contacts'),
					'$path_all'        => $path_all,
					'$path_no_sharer'  => $path_no_sharer,
					'$no_sharer'       => !empty($_REQUEST['no_sharer']),
					'$all'             => DI::l10n()->t('Include'),
					'$no_sharer_label' => DI::l10n()->t('Hide'),
					'$base'            => 'channel',
				]);
			}

			if (Feature::isEnabled(DI::userSession()->getLocalUserId(), 'trending_tags')) {
				DI::page()['aside'] .= TrendingTags::getHTML(self::$content);
			}

			// We need the editor here to be able to reshare an item.
			$o .= DI::conversation()->statusEditor([], 0, true);
		}

		$items = self::getItems();

		if (!DBA::isResult($items)) {
			DI::sysmsg()->addNotice(DI::l10n()->t('No results.'));
			return $o;
		}

		$o .= DI::conversation()->render($items, Conversation::MODE_CHANNEL, false, false, 'created', DI::userSession()->getLocalUserId());

		$pager = new BoundariesPager(
			DI::l10n(),
			DI::args()->getQueryString(),
			$items[0]['created'],
			$items[count($items) - 1]['created'],
			self::$itemsPerPage
		);

		if (DI::pConfig()->get(DI::userSession()->getLocalUserId(), 'system', 'infinite_scroll')) {
			$o .= HTML::scrollLoader();
		} else {
			$o .= $pager->renderMinimal(count($items));
		}

		return $o;
	}

	/**
	 * Computes module parameters from the request and local configuration
	 *
	 * @throws HTTPException\BadRequestException
	 * @throws HTTPException\ForbiddenException
	 */
	protected function parseRequest()
	{
		self::$accountTypeString = $_GET['accounttype'] ?? $this->parameters['accounttype'] ?? '';
		self::$accountType       = User::getAccountTypeByString(self::$accountTypeString);

		self::$content = $this->parameters['content'] ?? '';
		if (!self::$content) {
			self::$content = self::FORYOU;
		}

		if (!in_array(self::$content, [self::WHATSHOT, self::FORYOU, self::FOLLOWERS])) {
			throw new HTTPException\BadRequestException(DI::l10n()->t('Channel not available.'));
		}

		if (DI::mode()->isMobile()) {
			self::$itemsPerPage = DI::pConfig()->get(
				DI::userSession()->getLocalUserId(),
				'system',
				'itemspage_mobile_network',
				DI::config()->get('system', 'itemspage_network_mobile')
			);
		} else {
			self::$itemsPerPage = DI::pConfig()->get(
				DI::userSession()->getLocalUserId(),
				'system',
				'itemspage_network',
				DI::config()->get('system', 'itemspage_network')
			);
		}

		if (!empty($_GET['item'])) {
			$item          = Post::selectFirst(['parent-uri-id'], ['id' => $_GET['item']]);
			self::$item_id = $item['parent-uri-id'] ?? 0;
		} else {
			self::$item_id = 0;
		}

		self::$min_id = $_GET['min_id']       ?? null;
		self::$max_id = $_GET['max_id']       ?? null;
		self::$max_id = $_GET['last_created'] ?? self::$max_id;
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
		if (self::$content == self::WHATSHOT) {
			if (!is_null(self::$accountType)) {
				$condition = ["(`comments` >= ? OR `activities` >= ?) AND `contact-type` = ?", self::getMedianComments(4), self::getMedianActivities(4), self::$accountType];
			} else {
				$condition = ["(`comments` >= ? OR `activities` >= ?) AND `contact-type` != ?", self::getMedianComments(4), self::getMedianActivities(4), Contact::TYPE_COMMUNITY];
			}
		} elseif (self::$content == self::FORYOU) {
			$cid = Contact::getPublicIdByUserId(DI::userSession()->getLocalUserId());

			$condition = ["(`owner-id` IN (SELECT `relation-cid` FROM `contact-relation` WHERE `cid` = ? AND `thread-score` > ?) OR
				((`comments` >= ? OR `activities` >= ?) AND `owner-id` IN (SELECT `pid` FROM `account-user-view` WHERE `uid` = ? AND `rel` IN (?, ?))) OR
				( `owner-id` IN (SELECT `pid` FROM `account-user-view` WHERE `uid` = ? AND `rel` IN (?, ?) AND `notify_new_posts`)))",
				$cid, self::getMedianThreadScore($cid, 4), self::getMedianComments(4), self::getMedianActivities(4), DI::userSession()->getLocalUserId(), Contact::FRIEND, Contact::SHARING,
				DI::userSession()->getLocalUserId(), Contact::FRIEND, Contact::SHARING];
		} elseif (self::$content == self::FOLLOWERS) {
			$condition = ["`owner-id` IN (SELECT `pid` FROM `account-user-view` WHERE `uid` = ? AND `rel` = ?)", DI::userSession()->getLocalUserId(), Contact::FOLLOWER];
		}

		if ((self::$content != self::WHATSHOT) && !is_null(self::$accountType)) {
			$condition[0] .= " AND `contact-type` = ?";
			$condition[] = self::$accountType;
		}

		$params = ['order' => ['created' => true], 'limit' => self::$itemsPerPage];

		if (!empty(self::$item_id)) {
			$condition[0] .= " AND `uri-id` = ?";
			$condition[] = self::$item_id;
		} else {
			if (!empty($_REQUEST['no_sharer'])) {
				$condition[0] .= " AND NOT `uri-id` IN (SELECT `uri-id` FROM `post-user` WHERE `post-user`.`uid` = ? AND `post-user`.`uri-id` = `post-engagement`.`uri-id`)";
				$condition[] = DI::userSession()->getLocalUserId();
			}

			if (isset(self::$max_id)) {
				$condition[0] .= " AND `created` < ?";
				$condition[] = self::$max_id;
			}

			if (isset(self::$min_id)) {
				$condition[0] .= " AND `created` > ?";
				$condition[] = self::$min_id;

				// Previous page case: we want the items closest to min_id but for that we need to reverse the query order
				if (!isset(self::$max_id)) {
					$params['order']['created'] = false;
				}
			}
		}

		$items = DBA::selectToArray('post-engagement', ['uri-id', 'created'], $condition, $params);

		if (empty($items)) {
			return [];
		}

		// Previous page case: once we get the relevant items closest to min_id, we need to restore the expected display order
		if (empty(self::$item_id) && isset(self::$min_id) && !isset(self::$max_id)) {
			$items = array_reverse($items);
		}

		return $items;
	}

	private static function getMedianComments(int $divider): int
	{
		$cache_key = 'Channel:getMedianComments:' . $divider;
		$comments  = DI::cache()->get($cache_key);
		if (!empty($comments)) {
			return $comments;
		}

		$limit    = DBA::count('post-engagement', ["`contact-type` != ? AND `comments` > ?", Contact::TYPE_COMMUNITY, 0]) / $divider;
		$post     = DBA::selectToArray('post-engagement', ['comments'], ["`contact-type` != ?", Contact::TYPE_COMMUNITY, 0], ['order' => ['comments' => true], 'limit' => [$limit, 1]]);
		$comments = $post[0]['comments'] ?? 0;
		if (empty($comments)) {
			return 0;
		}

		DI::cache()->set($cache_key, $comments, Duration::HOUR);
		return $comments;
	}

	private static function getMedianActivities(int $divider): int
	{
		$cache_key  = 'Channel:getMedianActivities:' . $divider;
		$activities = DI::cache()->get($cache_key);
		if (!empty($activities)) {
			return $activities;
		}

		$limit      = DBA::count('post-engagement', ["`contact-type` != ? AND `activities` > ?", Contact::TYPE_COMMUNITY, 0]) / $divider;
		$post       = DBA::selectToArray('post-engagement', ['activities'], ["`contact-type` != ?", Contact::TYPE_COMMUNITY, 0], ['order' => ['activities' => true], 'limit' => [$limit, 1]]);
		$activities = $post[0]['activities'] ?? 0;
		if (empty($activities)) {
			return 0;
		}

		DI::cache()->set($cache_key, $activities, Duration::HOUR);
		return $activities;
	}

	private static function getMedianThreadScore(int $cid, int $divider): int
	{
		$cache_key = 'Channel:getThreadScore:' . $cid . ':' . $divider;
		$score     = DI::cache()->get($cache_key);
		if (!empty($score)) {
			return $score;
		}

		$limit    = DBA::count('contact-relation', ["`cid` = ? AND `thread-score` > ?", $cid, 0]) / $divider;
		$relation = DBA::selectToArray('contact-relation', ['thread-score'], ['cid' => $cid], ['order' => ['thread-score' => true], 'limit' => [$limit, 1]]);
		$score    = $relation[0]['thread-score'] ?? 0;
		if (empty($score)) {
			return 0;
		}

		DI::cache()->set($cache_key, $score, Duration::HOUR);
		return $score;
	}
}
