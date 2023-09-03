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
use Friendica\Content\BoundariesPager;
use Friendica\Content\Conversation;
use Friendica\Content\Feature;
use Friendica\Content\Nav;
use Friendica\Content\Text\HTML;
use Friendica\Content\Widget;
use Friendica\Content\Widget\TrendingTags;
use Friendica\Core\Cache\Capability\ICanCache;
use Friendica\Core\Cache\Enum\Duration;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\L10n;
use Friendica\Core\PConfig\Capability\IManagePersonalConfigValues;
use Friendica\Core\Renderer;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Model\Contact;
use Friendica\Model\Post;
use Friendica\Model\User;
use Friendica\Module\Security\Login;
use Friendica\Network\HTTPException;
use Friendica\Core\Session\Model\UserSession;
use Friendica\Database\Database;
use Friendica\Model\Item;
use Friendica\Module\Response;
use Friendica\Navigation\SystemMessages;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

class Channel extends BaseModule
{
	const WHATSHOT  = 'whatshot';
	const FORYOU    = 'foryou';
	const FOLLOWERS = 'followers';
	const IMAGE     = 'image';
	const VIDEO     = 'video';
	const AUDIO     = 'audio';
	const LANGUAGE  = 'language';

	protected static $content;
	protected static $accountTypeString;
	protected static $accountType;
	protected static $itemsPerPage;
	protected static $min_id;
	protected static $max_id;
	protected static $item_id;

	/** @var UserSession */
	protected $session;
	/** @var ICanCache */
	protected $cache;
	/** @var IManageConfigValues The config */
	protected $config;
	/** @var SystemMessages */
	protected $systemMessages;
	/** @var App\Page */
	protected $page;
	/** @var Conversation */
	protected $conversation;
	/** @var App\Mode $mode */
	protected $mode;
	/** @var IManagePersonalConfigValues */
	protected $pConfig;
	/** @var Database */
	protected $database;


	public function __construct(SystemMessages $systemMessages, Database $database, IManagePersonalConfigValues $pConfig, Mode $mode, Conversation $conversation, App\Page $page, IManageConfigValues $config, ICanCache $cache, IHandleUserSessions $session, L10n $l10n, App\BaseURL $baseUrl, App\Arguments $args, LoggerInterface $logger, Profiler $profiler, Response $response, array $server, array $parameters = [])
	{
		parent::__construct($l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		$this->systemMessages = $systemMessages;
		$this->database       = $database;
		$this->pConfig        = $pConfig;
		$this->mode           = $mode;
		$this->conversation   = $conversation;
		$this->page           = $page;
		$this->config         = $config;
		$this->cache          = $cache;
		$this->session        = $session;
	}

	protected function content(array $request = []): string
	{
		if (!$this->session->getLocalUserId()) {
			return Login::form();
		}

		$this->parseRequest($request);

		$t = Renderer::getMarkupTemplate("community.tpl");
		$o = Renderer::replaceMacros($t, [
			'$content' => '',
			'$header'  => '',
		]);

		if ($this->pConfig->get($this->session->getLocalUserId(), 'system', 'infinite_scroll')) {
			$tpl = Renderer::getMarkupTemplate('infinite_scroll_head.tpl');
			$o .= Renderer::replaceMacros($tpl, ['$reload_uri' => $this->args->getQueryString()]);
		}

		if (empty($request['mode']) || ($request['mode'] != 'raw')) {
			$tabs = [];

			$tabs[] = [
				'label'     => $this->l10n->t('For you'),
				'url'       => 'channel/' . self::FORYOU,
				'sel'       => self::$content == self::FORYOU ? 'active' : '',
				'title'     => $this->l10n->t('Posts from contacts you interact with and who interact with you'),
				'id'        => 'channel-foryou-tab',
				'accesskey' => 'y'
			];

			$tabs[] = [
				'label'     => $this->l10n->t('Followers'),
				'url'       => 'channel/' . self::FOLLOWERS,
				'sel'       => self::$content == self::FOLLOWERS ? 'active' : '',
				'title'     => $this->l10n->t('Posts from your followers that you don\'t follow'),
				'id'        => 'channel-followers-tab',
				'accesskey' => 'f'
			];

			$tabs[] = [
				'label'     => $this->l10n->t('What\'s Hot'),
				'url'       => 'channel/' . self::WHATSHOT,
				'sel'       => self::$content == self::WHATSHOT ? 'active' : '',
				'title'     => $this->l10n->t('Posts with a lot of interactions'),
				'id'        => 'channel-whatshot-tab',
				'accesskey' => 'h'
			];

			$tabs[] = [
				'label'     => $this->l10n->t('Images'),
				'url'       => 'channel/' . self::IMAGE,
				'sel'       => self::$content == self::IMAGE ? 'active' : '',
				'title'     => $this->l10n->t('Posts with images'),
				'id'        => 'channel-image-tab',
				'accesskey' => 'i'
			];

			$tabs[] = [
				'label'     => $this->l10n->t('Videos'),
				'url'       => 'channel/' . self::VIDEO,
				'sel'       => self::$content == self::VIDEO ? 'active' : '',
				'title'     => $this->l10n->t('Posts with videos'),
				'id'        => 'channel-video-tab',
				'accesskey' => 'v'
			];

			$tabs[] = [
				'label'     => $this->l10n->t('Audio'),
				'url'       => 'channel/' . self::AUDIO,
				'sel'       => self::$content == self::AUDIO ? 'active' : '',
				'title'     => $this->l10n->t('Posts with audio'),
				'id'        => 'channel-audio-tab',
				'accesskey' => 'd'
			];

			$language  = User::getLanguageCode($this->session->getLocalUserId(), false);
			$languages = $this->l10n->getAvailableLanguages();

			$tabs[] = [
				'label'     => $languages[$language],
				'url'       => 'channel/' . self::LANGUAGE,
				'sel'       => self::$content == self::LANGUAGE ? 'active' : '',
				'title'     => $this->l10n->t('Posts in %s', $languages[$language]),
				'id'        => 'channel-language-tab',
				'accesskey' => 'g'
			];

			$tab_tpl = Renderer::getMarkupTemplate('common_tabs.tpl');
			$o .= Renderer::replaceMacros($tab_tpl, ['$tabs' => $tabs]);

			Nav::setSelected('channel');

			$this->page['aside'] .= Widget::accountTypes('channel/' . self::$content, self::$accountTypeString);

			if (!in_array(self::$content, [self::FOLLOWERS, self::FORYOU]) && $this->config->get('system', 'community_no_sharer')) {
				$path = self::$content;
				if (!empty($this->parameters['accounttype'])) {
					$path .= '/' . $this->parameters['accounttype'];
				}
				$query_parameters = [];

				if (!empty($request['min_id'])) {
					$query_parameters['min_id'] = $request['min_id'];
				}
				if (!empty($request['max_id'])) {
					$query_parameters['max_id'] = $request['max_id'];
				}
				if (!empty($request['last_created'])) {
					$query_parameters['max_id'] = $request['last_created'];
				}

				$path_all       = $path . (!empty($query_parameters) ? '?' . http_build_query($query_parameters) : '');
				$path_no_sharer = $path . '?' . http_build_query(array_merge($query_parameters, ['no_sharer' => true]));
				$this->page['aside'] .= Renderer::replaceMacros(Renderer::getMarkupTemplate('widget/community_sharer.tpl'), [
					'$title'           => $this->l10n->t('Own Contacts'),
					'$path_all'        => $path_all,
					'$path_no_sharer'  => $path_no_sharer,
					'$no_sharer'       => !empty($request['no_sharer']),
					'$all'             => $this->l10n->t('Include'),
					'$no_sharer_label' => $this->l10n->t('Hide'),
					'$base'            => 'channel',
				]);
			}

			if (Feature::isEnabled($this->session->getLocalUserId(), 'trending_tags')) {
				$this->page['aside'] .= TrendingTags::getHTML(self::$content);
			}

			// We need the editor here to be able to reshare an item.
			$o .= $this->conversation->statusEditor([], 0, true);
		}

		$items = $this->getItems($request);

		if (!$this->database->isResult($items)) {
			$this->systemMessages->addNotice($this->l10n->t('No results.'));
			return $o;
		}

		$o .= $this->conversation->render($items, Conversation::MODE_CHANNEL, false, false, 'created', $this->session->getLocalUserId());

		$pager = new BoundariesPager(
			$this->l10n,
			$this->args->getQueryString(),
			$items[0]['created'],
			$items[count($items) - 1]['created'],
			self::$itemsPerPage
		);

		if ($this->pConfig->get($this->session->getLocalUserId(), 'system', 'infinite_scroll')) {
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
	protected function parseRequest(array $request)
	{
		self::$accountTypeString = $request['accounttype'] ?? $this->parameters['accounttype'] ?? '';
		self::$accountType       = User::getAccountTypeByString(self::$accountTypeString);

		self::$content = $this->parameters['content'] ?? '';
		if (!self::$content) {
			self::$content = self::FORYOU;
		}

		if (!in_array(self::$content, [self::WHATSHOT, self::FORYOU, self::FOLLOWERS, self::IMAGE, self::VIDEO, self::AUDIO, self::LANGUAGE])) {
			throw new HTTPException\BadRequestException($this->l10n->t('Channel not available.'));
		}

		if ($this->mode->isMobile()) {
			self::$itemsPerPage = $this->pConfig->get(
				$this->session->getLocalUserId(),
				'system',
				'itemspage_mobile_network',
				$this->config->get('system', 'itemspage_network_mobile')
			);
		} else {
			self::$itemsPerPage = $this->pConfig->get(
				$this->session->getLocalUserId(),
				'system',
				'itemspage_network',
				$this->config->get('system', 'itemspage_network')
			);
		}

		if (!empty($request['item'])) {
			$item          = Post::selectFirst(['parent-uri-id'], ['id' => $request['item']]);
			self::$item_id = $item['parent-uri-id'] ?? 0;
		} else {
			self::$item_id = 0;
		}

		self::$min_id = $request['min_id']       ?? null;
		self::$max_id = $request['last_created'] ?? $request['max_id'] ?? null;
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
	protected function getItems(array $request)
	{
		if (self::$content == self::WHATSHOT) {
			if (!is_null(self::$accountType)) {
				$condition = ["(`comments` >= ? OR `activities` >= ?) AND `contact-type` = ?", $this->getMedianComments(4), $this->getMedianActivities(4), self::$accountType];
			} else {
				$condition = ["(`comments` >= ? OR `activities` >= ?) AND `contact-type` != ?", $this->getMedianComments(4), $this->getMedianActivities(4), Contact::TYPE_COMMUNITY];
			}

			$condition = $this->addLanguageCondition($condition);
		} elseif (self::$content == self::FORYOU) {
			$cid = Contact::getPublicIdByUserId($this->session->getLocalUserId());

			$condition = ["(`owner-id` IN (SELECT `relation-cid` FROM `contact-relation` WHERE `cid` = ? AND `thread-score` > ?) OR
				((`comments` >= ? OR `activities` >= ?) AND `owner-id` IN (SELECT `pid` FROM `account-user-view` WHERE `uid` = ? AND `rel` IN (?, ?))) OR
				( `owner-id` IN (SELECT `pid` FROM `account-user-view` WHERE `uid` = ? AND `rel` IN (?, ?) AND `notify_new_posts`)))",
				$cid, $this->getMedianThreadScore($cid, 4), $this->getMedianComments(4), $this->getMedianActivities(4), $this->session->getLocalUserId(), Contact::FRIEND, Contact::SHARING,
				$this->session->getLocalUserId(), Contact::FRIEND, Contact::SHARING];
		} elseif (self::$content == self::FOLLOWERS) {
			$condition = ["`owner-id` IN (SELECT `pid` FROM `account-user-view` WHERE `uid` = ? AND `rel` = ?)", $this->session->getLocalUserId(), Contact::FOLLOWER];
		} elseif (self::$content == self::IMAGE) {
			$condition = ["`media-type` & ?", 1];
		} elseif (self::$content == self::VIDEO) {
			$condition = ["`media-type` & ?", 2];
		} elseif (self::$content == self::AUDIO) {
			$condition = ["`media-type` & ?", 4];
		} elseif (self::$content == self::LANGUAGE) {
			$condition = ["JSON_EXTRACT(JSON_KEYS(language), '$[0]') = ?", User::getLanguageCode($this->session->getLocalUserId(), true)];
		}

		$condition[0] .= " AND NOT EXISTS(SELECT `cid` FROM `user-contact` WHERE `uid` = ? AND `cid` = `post-engagement`.`owner-id` AND (`ignored` OR `blocked` OR `collapsed`))";
		$condition[] = $this->session->getLocalUserId();

		if ((self::$content != self::WHATSHOT) && !is_null(self::$accountType)) {
			$condition[0] .= " AND `contact-type` = ?";
			$condition[] = self::$accountType;
		}

		$params = ['order' => ['created' => true], 'limit' => self::$itemsPerPage];

		if (!empty(self::$item_id)) {
			$condition[0] .= " AND `uri-id` = ?";
			$condition[] = self::$item_id;
		} else {
			if (!empty($request['no_sharer'])) {
				$condition[0] .= " AND NOT `uri-id` IN (SELECT `uri-id` FROM `post-user` WHERE `post-user`.`uid` = ? AND `post-user`.`uri-id` = `post-engagement`.`uri-id`)";
				$condition[] = $this->session->getLocalUserId();
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

		$items = $this->database->selectToArray('post-engagement', ['uri-id', 'created'], $condition, $params);
		if (empty($items)) {
			return [];
		}

		// Previous page case: once we get the relevant items closest to min_id, we need to restore the expected display order
		if (empty(self::$item_id) && isset(self::$min_id) && !isset(self::$max_id)) {
			$items = array_reverse($items);
		}

		Item::update(['unseen' => false], ['unseen' => true, 'uid' => $this->session->getLocalUserId(), 'uri-id' => array_column($items, 'uri-id')]);

		return $items;
	}

	private function addLanguageCondition(array $condition): array
	{
		$conditions = [];
		$languages  = $this->pConfig->get($this->session->getLocalUserId(), 'channel', 'languages', [User::getLanguageCode($this->session->getLocalUserId(), false)]);
		foreach ($languages as $language) {
			$conditions[] = "JSON_EXTRACT(JSON_KEYS(language), '$[0]') = ?";
			$condition[]  = substr($language, 0, 2);
		}
		if (!empty($conditions)) {
			$condition[0] .= " AND (`language` IS NULL OR " . implode(' OR ', $conditions) . ")";
		}
		return $condition;
	}

	private function getMedianComments(int $divider): int
	{
		$cache_key = 'Channel:getMedianComments:' . $divider;
		$comments  = $this->cache->get($cache_key);
		if (!empty($comments)) {
			return $comments;
		}

		$limit    = $this->database->count('post-engagement', ["`contact-type` != ? AND `comments` > ?", Contact::TYPE_COMMUNITY, 0]) / $divider;
		$post     = $this->database->selectToArray('post-engagement', ['comments'], ["`contact-type` != ?", Contact::TYPE_COMMUNITY], ['order' => ['comments' => true], 'limit' => [$limit, 1]]);
		$comments = $post[0]['comments'] ?? 0;
		if (empty($comments)) {
			return 0;
		}

		$this->cache->set($cache_key, $comments, Duration::HOUR);
		return $comments;
	}

	private function getMedianActivities(int $divider): int
	{
		$cache_key  = 'Channel:getMedianActivities:' . $divider;
		$activities = $this->cache->get($cache_key);
		if (!empty($activities)) {
			return $activities;
		}

		$limit      = $this->database->count('post-engagement', ["`contact-type` != ? AND `activities` > ?", Contact::TYPE_COMMUNITY, 0]) / $divider;
		$post       = $this->database->selectToArray('post-engagement', ['activities'], ["`contact-type` != ?", Contact::TYPE_COMMUNITY], ['order' => ['activities' => true], 'limit' => [$limit, 1]]);
		$activities = $post[0]['activities'] ?? 0;
		if (empty($activities)) {
			return 0;
		}

		$this->cache->set($cache_key, $activities, Duration::HOUR);
		return $activities;
	}

	private function getMedianThreadScore(int $cid, int $divider): int
	{
		$cache_key = 'Channel:getThreadScore:' . $cid . ':' . $divider;
		$score     = $this->cache->get($cache_key);
		if (!empty($score)) {
			return $score;
		}

		$limit    = $this->database->count('contact-relation', ["`cid` = ? AND `thread-score` > ?", $cid, 0]) / $divider;
		$relation = $this->database->selectToArray('contact-relation', ['thread-score'], ['cid' => $cid], ['order' => ['thread-score' => true], 'limit' => [$limit, 1]]);
		$score    = $relation[0]['thread-score'] ?? 0;
		if (empty($score)) {
			return 0;
		}

		$this->cache->set($cache_key, $score, Duration::HOUR);
		return $score;
	}
}
