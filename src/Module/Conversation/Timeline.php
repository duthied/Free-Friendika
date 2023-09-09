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

namespace Friendica\Module\Conversation;

use Friendica\App;
use Friendica\App\Mode;
use Friendica\BaseModule;
use Friendica\Content\Conversation\Collection\Timelines;
use Friendica\Content\Conversation\Entity\Timeline as TimelineEntity;
use Friendica\Core\Cache\Capability\ICanCache;
use Friendica\Core\Cache\Enum\Duration;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\L10n;
use Friendica\Core\PConfig\Capability\IManagePersonalConfigValues;
use Friendica\Core\Renderer;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Model\Contact;
use Friendica\Model\User;
use Friendica\Database\Database;
use Friendica\Database\DBA;
use Friendica\Model\Item;
use Friendica\Model\Post;
use Friendica\Module\Response;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

class Timeline extends BaseModule
{
	/** @var string */
	protected $selectedTab;
	/** @var mixed */
	protected $min_id;
	/** @var mixed */
	protected $max_id;
	/** @var string */
	protected $accountTypeString;
	/** @var int */
	protected $accountType;
	/** @var int */
	protected $itemUriId;
	/** @var int */
	protected $itemsPerPage;
	/** @var bool */
	protected $noSharer;

	/** @var App\Mode $mode */
	protected $mode;
	/** @var UserSession */
	protected $session;
	/** @var Database */
	protected $database;
	/** @var IManagePersonalConfigValues */
	protected $pConfig;
	/** @var IManageConfigValues The config */
	protected $config;
	/** @var ICanCache */
	protected $cache;

	public function __construct(Mode $mode, IHandleUserSessions $session, Database $database, IManagePersonalConfigValues $pConfig, IManageConfigValues $config, ICanCache $cache, L10n $l10n, App\BaseURL $baseUrl, App\Arguments $args, LoggerInterface $logger, Profiler $profiler, Response $response, array $server, array $parameters = [])
	{
		parent::__construct($l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		$this->mode     = $mode;
		$this->session  = $session;
		$this->database = $database;
		$this->pConfig  = $pConfig;
		$this->config   = $config;
		$this->cache    = $cache;
	}

	/**
	 * Computes module parameters from the request and local configuration
	 *
	 * @throws HTTPException\BadRequestException
	 * @throws HTTPException\ForbiddenException
	 */
	protected function parseRequest(array $request)
	{
		$this->logger->debug('Got request', $request);
		$this->selectedTab = $this->parameters['content'] ?? '';

		$this->accountTypeString = $request['accounttype'] ?? $this->parameters['accounttype'] ?? '';
		$this->accountType       = User::getAccountTypeByString($this->accountTypeString);

		if ($this->mode->isMobile()) {
			$this->itemsPerPage = $this->pConfig->get(
				$this->session->getLocalUserId(),
				'system',
				'itemspage_mobile_network',
				$this->config->get('system', 'itemspage_network_mobile')
			);
		} else {
			$this->itemsPerPage = $this->pConfig->get(
				$this->session->getLocalUserId(),
				'system',
				'itemspage_network',
				$this->config->get('system', 'itemspage_network')
			);
		}

		if (!empty($request['item'])) {
			$item = Post::selectFirst(['parent', 'parent-uri-id'], ['id' => $request['item']]);
			$this->itemUriId = $item['parent-uri-id'] ?? 0;
		} else {
			$this->itemUriId = 0;
		}

		$this->min_id = $request['min_id'] ?? null;
		$this->max_id = $request['max_id'] ?? null;

		$this->noSharer = !empty($request['no_sharer']);
	}

	protected function getNoSharerWidget(string $base): string
	{
		$path = $this->selectedTab;
		if (!empty($this->accountTypeString)) {
			$path .= '/' . $this->accountTypeString;
		}
		$query_parameters = [];

		if (!empty($this->min_id)) {
			$query_parameters['min_id'] = $this->min_id;
		}
		if (!empty($this->max_id)) {
			$query_parameters['max_id'] = $this->max_id;
		}

		$path_all       = $path . (!empty($query_parameters) ? '?' . http_build_query($query_parameters) : '');
		$path_no_sharer = $path . '?' . http_build_query(array_merge($query_parameters, ['no_sharer' => true]));
		return Renderer::replaceMacros(Renderer::getMarkupTemplate('widget/community_sharer.tpl'), [
			'$title'           => $this->l10n->t('Own Contacts'),
			'$path_all'        => $path_all,
			'$path_no_sharer'  => $path_no_sharer,
			'$no_sharer'       => $this->noSharer,
			'$all'             => $this->l10n->t('Include'),
			'$no_sharer_label' => $this->l10n->t('Hide'),
			'$base'            => $base,
		]);
	}

	protected function getTabArray(Timelines $timelines, string $prefix): array
	{
		$tabs = [];

		foreach ($timelines as $tab) {
			$tabs[] = [
				'label'     => $tab->label,
				'url'       => $tab->path ?? $prefix . '/' . $tab->code,
				'sel'       => $this->selectedTab == $tab->code ? 'active' : '',
				'title'     => $tab->description,
				'id'        => $prefix . '-' . $tab->code . '-tab',
				'accesskey' => $tab->accessKey,
			];
		}
		return $tabs;
	}

	/**
	 * Database query for the channel page
	 *
	 * @return array
	 * @throws \Exception
	 */
	protected function getChannelItems()
	{
		$uid = $this->session->getLocalUserId();

		if ($this->selectedTab == TimelineEntity::WHATSHOT) {
			if (!is_null($this->accountType)) {
				$condition = ["(`comments` >= ? OR `activities` >= ?) AND `contact-type` = ?", $this->getMedianComments($uid, 4), $this->getMedianActivities($uid, 4), $this->accountType];
			} else {
				$condition = ["(`comments` >= ? OR `activities` >= ?) AND `contact-type` != ?", $this->getMedianComments($uid, 4), $this->getMedianActivities($uid, 4), Contact::TYPE_COMMUNITY];
			}
		} elseif ($this->selectedTab == TimelineEntity::FORYOU) {
			$cid = Contact::getPublicIdByUserId($uid);

			$condition = [
				"(`owner-id` IN (SELECT `cid` FROM `contact-relation` WHERE `relation-cid` = ? AND `relation-thread-score` > ?) OR
				((`comments` >= ? OR `activities` >= ?) AND `owner-id` IN (SELECT `cid` FROM `contact-relation` WHERE `follows` AND `relation-cid` = ?)) OR
				(`owner-id` IN (SELECT `pid` FROM `account-user-view` WHERE `uid` = ? AND `rel` IN (?, ?) AND `notify_new_posts`)))",
				$cid, $this->getMedianRelationThreadScore($cid, 4), $this->getMedianComments($uid, 4), $this->getMedianActivities($uid, 4), $cid,
				$uid, Contact::FRIEND, Contact::SHARING
			];
		} elseif ($this->selectedTab == TimelineEntity::FOLLOWERS) {
			$condition = ["`owner-id` IN (SELECT `pid` FROM `account-user-view` WHERE `uid` = ? AND `rel` = ?)", $uid, Contact::FOLLOWER];
		} elseif ($this->selectedTab == TimelineEntity::SHARERSOFSHARERS) {
			$cid = Contact::getPublicIdByUserId($uid);

			// @todo Suggest posts from contacts that are followed most by our followers
			$condition = [
				"`owner-id` IN (SELECT `cid` FROM `contact-relation` WHERE `follows` AND `last-interaction` > ?
				AND `relation-cid` IN (SELECT `cid` FROM `contact-relation` WHERE `follows` AND `relation-cid` = ? AND `relation-thread-score` >= ?)
				AND NOT `cid` IN (SELECT `cid` FROM `contact-relation` WHERE `follows` AND `relation-cid` = ?))",
				DateTimeFormat::utc('now - ' . $this->config->get('channel', 'sharer_interaction_days') . ' day'), $cid, $this->getMedianRelationThreadScore($cid, 4), $cid
			];
		} elseif ($this->selectedTab == TimelineEntity::IMAGE) {
			$condition = ["`media-type` & ?", 1];
		} elseif ($this->selectedTab == TimelineEntity::VIDEO) {
			$condition = ["`media-type` & ?", 2];
		} elseif ($this->selectedTab == TimelineEntity::AUDIO) {
			$condition = ["`media-type` & ?", 4];
		} elseif ($this->selectedTab == TimelineEntity::LANGUAGE) {
			$condition = ["JSON_EXTRACT(JSON_KEYS(language), '$[0]') = ?", $this->l10n->convertCodeForLanguageDetection(User::getLanguageCode($uid))];
		}

		if ($this->selectedTab != TimelineEntity::LANGUAGE) {
			$condition = $this->addLanguageCondition($uid, $condition);
		}

		$condition = DBA::mergeConditions($condition, ["NOT EXISTS(SELECT `cid` FROM `user-contact` WHERE `uid` = ? AND `cid` = `post-engagement`.`owner-id` AND (`ignored` OR `blocked` OR `collapsed`))", $uid]);

		if (($this->selectedTab != TimelineEntity::WHATSHOT) && !is_null($this->accountType)) {
			$condition = DBA::mergeConditions($condition, ['contact-type' => $this->accountType]);
		}

		$params = ['order' => ['created' => true], 'limit' => $this->itemsPerPage];

		if (!empty($this->itemUriId)) {
			$condition = DBA::mergeConditions($condition, ['uri-id' => $this->itemUriId]);
		} else {
			if ($this->noSharer) {
				$condition = DBA::mergeConditions($condition, ["NOT `uri-id` IN (SELECT `uri-id` FROM `post-user` WHERE `post-user`.`uid` = ? AND `post-user`.`uri-id` = `post-engagement`.`uri-id`)", $this->session->getLocalUserId()]);
			}

			if (isset($this->max_id)) {
				$condition = DBA::mergeConditions($condition, ["`created` < ?", $this->max_id]);
			}

			if (isset($this->min_id)) {
				$condition = DBA::mergeConditions($condition, ["`created` > ?", $this->min_id]);

				// Previous page case: we want the items closest to min_id but for that we need to reverse the query order
				if (!isset($this->max_id)) {
					$params['order']['created'] = false;
				}
			}
		}

		$items = $this->database->selectToArray('post-engagement', ['uri-id', 'created'], $condition, $params);
		if (empty($items)) {
			return [];
		}

		// Previous page case: once we get the relevant items closest to min_id, we need to restore the expected display order
		if (empty($this->itemUriId) && isset($this->min_id) && !isset($this->max_id)) {
			$items = array_reverse($items);
		}

		Item::update(['unseen' => false], ['unseen' => true, 'uid' => $uid, 'uri-id' => array_column($items, 'uri-id')]);

		return $items;
	}

	private function addLanguageCondition(int $uid, array $condition): array
	{
		$conditions = [];
		$languages  = $this->pConfig->get($uid, 'channel', 'languages', [User::getLanguageCode($uid)]);
		$languages  = $this->l10n->convertForLanguageDetection($languages);
		foreach ($languages as $language) {
			$conditions[] = "JSON_EXTRACT(JSON_KEYS(language), '$[0]') = ?";
			$condition[]  = $language;
		}
		if (!empty($conditions)) {
			$condition[0] .= " AND (`language` IS NULL OR " . implode(' OR ', $conditions) . ")";
		}
		return $condition;
	}

	private function getMedianComments(int $uid, int $divider): int
	{
		$languages = $this->pConfig->get($uid, 'channel', 'languages', [User::getLanguageCode($uid)]);
		$cache_key = 'Channel:getMedianComments:' . $divider . ':' . implode(':', $languages);
		$comments  = $this->cache->get($cache_key);
		if (!empty($comments)) {
			return $comments;
		}

		$condition = ["`contact-type` != ? AND `comments` > ?", Contact::TYPE_COMMUNITY, 0];
		$condition = $this->addLanguageCondition($uid, $condition);

		$limit    = $this->database->count('post-engagement', $condition) / $divider;
		$post     = $this->database->selectToArray('post-engagement', ['comments'], $condition, ['order' => ['comments' => true], 'limit' => [$limit, 1]]);
		$comments = $post[0]['comments'] ?? 0;
		if (empty($comments)) {
			return 0;
		}

		$this->cache->set($cache_key, $comments, Duration::HALF_HOUR);
		$this->logger->debug('Calculated median comments', ['divider' => $divider, 'languages' => $languages, 'median' => $comments]);
		return $comments;
	}

	private function getMedianActivities(int $uid, int $divider): int
	{
		$languages  = $this->pConfig->get($uid, 'channel', 'languages', [User::getLanguageCode($uid)]);
		$cache_key  = 'Channel:getMedianActivities:' . $divider . ':' . implode(':', $languages);
		$activities = $this->cache->get($cache_key);
		if (!empty($activities)) {
			return $activities;
		}

		$condition = ["`contact-type` != ? AND `activities` > ?", Contact::TYPE_COMMUNITY, 0];
		$condition = $this->addLanguageCondition($uid, $condition);

		$limit      = $this->database->count('post-engagement', $condition) / $divider;
		$post       = $this->database->selectToArray('post-engagement', ['activities'], $condition, ['order' => ['activities' => true], 'limit' => [$limit, 1]]);
		$activities = $post[0]['activities'] ?? 0;
		if (empty($activities)) {
			return 0;
		}

		$this->cache->set($cache_key, $activities, Duration::HALF_HOUR);
		$this->logger->debug('Calculated median activities', ['divider' => $divider, 'languages' => $languages, 'median' => $activities]);
		return $activities;
	}

	private function getMedianRelationThreadScore(int $cid, int $divider): int
	{
		$cache_key = 'Channel:getThreadScore:' . $cid . ':' . $divider;
		$score     = $this->cache->get($cache_key);
		if (!empty($score)) {
			return $score;
		}

		$condition = ["`relation-cid` = ? AND `relation-thread-score` > ?", $cid, 0];

		$limit    = $this->database->count('contact-relation', $condition) / $divider;
		$relation = $this->database->selectToArray('contact-relation', ['relation-thread-score'], $condition, ['order' => ['relation-thread-score' => true], 'limit' => [$limit, 1]]);
		$score    = $relation[0]['relation-thread-score'] ?? 0;
		if (empty($score)) {
			return 0;
		}

		$this->cache->set($cache_key, $score, Duration::HALF_HOUR);
		$this->logger->debug('Calculated median score', ['cid' => $cid, 'divider' => $divider, 'median' => $score]);
		return $score;
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
	protected function getCommunityItems()
	{
		$items = $this->selectItems();

		$maxpostperauthor = (int) $this->config->get('system', 'max_author_posts_community_page');
		if ($maxpostperauthor != 0 && $this->selectedTab == 'local') {
			$count          = 1;
			$previousauthor = '';
			$numposts       = 0;
			$selected_items = [];

			while (count($selected_items) < $this->itemsPerPage && ++$count < 50 && count($items) > 0) {
				foreach ($items as $item) {
					if ($previousauthor == $item["author-link"]) {
						++$numposts;
					} else {
						$numposts = 0;
					}
					$previousauthor = $item["author-link"];

					if (($numposts < $maxpostperauthor) && (count($selected_items) < $this->itemsPerPage)) {
						$selected_items[] = $item;
					}
				}

				// If we're looking at a "previous page", the lookup continues forward in time because the list is
				// sorted in chronologically decreasing order
				if (isset($this->min_id)) {
					$this->min_id = $items[0]['commented'];
				} else {
					// In any other case, the lookup continues backwards in time
					$this->max_id = $items[count($items) - 1]['commented'];
				}

				$items = $this->selectItems();
			}
		} else {
			$selected_items = $items;
		}

		return $selected_items;
	}

	/**
	 * Database query for the community page
	 *
	 * @return array
	 * @throws \Exception
	 * @TODO Move to repository/factory
	 */
	private function selectItems()
	{
		if ($this->selectedTab == 'local') {
			if (!is_null($this->accountType)) {
				$condition = ["`wall` AND `origin` AND `private` = ? AND `owner-contact-type` = ?", Item::PUBLIC, $this->accountType];
			} else {
				$condition = ["`wall` AND `origin` AND `private` = ?", Item::PUBLIC];
			}
		} elseif ($this->selectedTab == 'global') {
			if (!is_null($this->accountType)) {
				$condition = ["`uid` = ? AND `private` = ? AND `owner-contact-type` = ?", 0, Item::PUBLIC, $this->accountType];
			} else {
				$condition = ["`uid` = ? AND `private` = ?", 0, Item::PUBLIC];
			}
		} else {
			return [];
		}

		$params = ['order' => ['commented' => true], 'limit' => $this->itemsPerPage];

		if (!empty($this->itemUriId)) {
			$condition = DBA::mergeConditions($condition, ['uri-id' => $this->itemUriId]);
		} else {
			if ($this->session->getLocalUserId() && $this->noSharer) {
				$condition = DBA::mergeConditions($condition, ["NOT `uri-id` IN (SELECT `uri-id` FROM `post-user` WHERE `post-user`.`uid` = ? AND `post-user`.`uri-id` = `post-thread-user-view`.`uri-id`)", $this->session->getLocalUserId()]);
			}

			if (isset($this->max_id)) {
				$condition = DBA::mergeConditions($condition, ["`commented` < ?", $this->max_id]);
			}

			if (isset($this->min_id)) {
				$condition = DBA::mergeConditions($condition, ["`commented` > ?", $this->min_id]);

				// Previous page case: we want the items closest to min_id but for that we need to reverse the query order
				if (!isset($this->max_id)) {
					$params['order']['commented'] = false;
				}
			}
		}

		$r = Post::selectThreadForUser($this->session->getLocalUserId() ?: 0, ['uri-id', 'commented', 'author-link'], $condition, $params);

		$items = Post::toArray($r);
		if (empty($items)) {
			return [];
		}

		// Previous page case: once we get the relevant items closest to min_id, we need to restore the expected display order
		if (empty($this->itemUriId) && isset($this->min_id) && !isset($this->max_id)) {
			$items = array_reverse($items);
		}

		return $items;
	}
}
