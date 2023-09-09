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
use Friendica\Content\BoundariesPager;
use Friendica\Content\Conversation;
use Friendica\Content\Conversation\Entity\Timeline as TimelineEntity;
use Friendica\Content\Conversation\Factory\Timeline as TimelineFactory;
use Friendica\Content\Feature;
use Friendica\Content\GroupManager;
use Friendica\Content\Nav;
use Friendica\Content\Widget;
use Friendica\Content\Text\HTML;
use Friendica\Content\Widget\TrendingTags;
use Friendica\Core\ACL;
use Friendica\Core\Cache\Capability\ICanCache;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\Hook;
use Friendica\Core\L10n;
use Friendica\Core\PConfig\Capability\IManagePersonalConfigValues;
use Friendica\Core\Renderer;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Database\DBA;
use Friendica\Database\Database;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Model\Circle;
use Friendica\Model\Item;
use Friendica\Model\Post;
use Friendica\Model\Profile;
use Friendica\Model\Verb;
use Friendica\Module\Contact as ModuleContact;
use Friendica\Module\Response;
use Friendica\Module\Security\Login;
use Friendica\Network\HTTPException;
use Friendica\Navigation\SystemMessages;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Profiler;
use Friendica\Protocol\Activity;
use Psr\Log\LoggerInterface;

class Network extends Timeline
{
	/** @var int */
	private static $circleId;
	/** @var int */
	private static $groupContactId;
	/** @var string */
	private static $network;
	/** @var string */
	private static $dateFrom;
	/** @var string */
	private static $dateTo;
	/** @var int */
	private static $star;
	/** @var int */
	private static $mention;
	/** @var string */
	protected static $order;

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
	/** @var IManagePersonalConfigValues */
	protected $pConfig;
	/** @var Database */
	protected $database;
	/** @var TimelineFactory */
	protected $timeline;

	public function __construct(TimelineFactory $timeline, SystemMessages $systemMessages, Mode $mode, Conversation $conversation, App\Page $page, IHandleUserSessions $session, Database $database, IManagePersonalConfigValues $pConfig, IManageConfigValues $config, ICanCache $cache, L10n $l10n, App\BaseURL $baseUrl, App\Arguments $args, LoggerInterface $logger, Profiler $profiler, Response $response, array $server, array $parameters = [])
	{
		parent::__construct($mode, $session, $database, $pConfig, $config, $cache, $l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		$this->timeline       = $timeline;
		$this->systemMessages = $systemMessages;
		$this->conversation   = $conversation;
		$this->page           = $page;
	}

	protected function content(array $request = []): string
	{
		if (!$this->session->getLocalUserId()) {
			return Login::form();
		}

		$this->parseRequest($request);

		$module = 'network';

		$this->page['aside'] .= Widget::accountTypes($module, self::$accountTypeString);

		$arr = ['query' => $this->args->getQueryString()];
		Hook::callAll('network_content_init', $arr);

		$o = '';

		if ($this->timeline->isChannel(self::$selectedTab)) {
			if (!in_array(self::$selectedTab, [TimelineEntity::FOLLOWERS, TimelineEntity::FORYOU]) && $this->config->get('system', 'community_no_sharer')) {
				$this->page['aside'] .= $this->getNoSharerWidget($module);
			}

			if (Feature::isEnabled($this->session->getLocalUserId(), 'trending_tags')) {
				$this->page['aside'] .= TrendingTags::getHTML(self::$selectedTab);
			}

			$items = $this->getChannelItems();
		} elseif ($this->timeline->isCommunity(self::$selectedTab)) {
			if ($this->session->getLocalUserId() && $this->config->get('system', 'community_no_sharer')) {
				$this->page['aside'] .= $this->getNoSharerWidget($module);
			}

			if (Feature::isEnabled($this->session->getLocalUserId(), 'trending_tags')) {
				$this->page['aside'] .= TrendingTags::getHTML(self::$selectedTab);
			}

			$items = $this->getCommunityItems();
		} else {
			$this->page['aside'] .= Circle::sidebarWidget($module, $module . '/circle', 'standard', self::$circleId);
			$this->page['aside'] .= GroupManager::widget($module . '/group', $this->session->getLocalUserId(), self::$groupContactId);
			$this->page['aside'] .= Widget::postedByYear($module . '/archive', $this->session->getLocalUserId(), false);
			$this->page['aside'] .= Widget::networks($module, !self::$groupContactId ? self::$network : '');
			$this->page['aside'] .= Widget\SavedSearches::getHTML($this->args->getQueryString());
			$this->page['aside'] .= Widget::fileAs('filed', '');

			// Fetch a page full of parent items for this page
			$params = ['limit' => self::$itemsPerPage];
			$table = 'network-thread-view';

			$items = $this->getItems($table, $params);
		}

		if ($this->pConfig->get($this->session->getLocalUserId(), 'system', 'infinite_scroll') && ($_GET['mode'] ?? '') != 'minimal') {
			$tpl = Renderer::getMarkupTemplate('infinite_scroll_head.tpl');
			$o .= Renderer::replaceMacros($tpl, ['$reload_uri' => $this->args->getQueryString()]);
		}

		if (!(isset($_GET['mode']) and ($_GET['mode'] == 'raw'))) {
			$o .= $this->getTabsHTML();

			Nav::setSelected($this->args->get(0));

			$content = '';

			if (self::$groupContactId) {
				// If self::$groupContactId belongs to a community group or a private group, add a mention to the status editor
				$condition = ["`id` = ? AND `contact-type` = ?", self::$groupContactId, Contact::TYPE_COMMUNITY];
				$contact = DBA::selectFirst('contact', ['addr'], $condition);
				if (!empty($contact['addr'])) {
					$content = '!' . $contact['addr'];
				}
			}

			$default_permissions = [];
			if (self::$circleId) {
				$default_permissions['allow_gid'] = [self::$circleId];
			}

			$allowedCids = [];
			if (self::$groupContactId) {
				$allowedCids[] = (int) self::$groupContactId;
			} elseif (self::$network) {
				$condition = [
					'uid'     => $this->session->getLocalUserId(),
					'network' => self::$network,
					'self'    => false,
					'blocked' => false,
					'pending' => false,
					'archive' => false,
					'rel'     => [Contact::SHARING, Contact::FRIEND],
				];
				$contactStmt = DBA::select('contact', ['id'], $condition);
				while ($contact = DBA::fetch($contactStmt)) {
					$allowedCids[] = (int) $contact['id'];
				}
				DBA::close($contactStmt);
			}

			if (count($allowedCids)) {
				$default_permissions['allow_cid'] = $allowedCids;
			}

			$x = [
				'lockstate' => self::$circleId || self::$groupContactId || self::$network || ACL::getLockstateForUserId($this->session->getLocalUserId()) ? 'lock' : 'unlock',
				'acl' => ACL::getFullSelectorHTML($this->page, $this->session->getLocalUserId(), true, $default_permissions),
				'bang' => ((self::$circleId || self::$groupContactId || self::$network) ? '!' : ''),
				'content' => $content,
			];

			$o .= $this->conversation->statusEditor($x);
		}

		if (self::$circleId) {
			$circle = DBA::selectFirst('group', ['name'], ['id' => self::$circleId, 'uid' => $this->session->getLocalUserId()]);
			if (!DBA::isResult($circle)) {
				$this->systemMessages->addNotice($this->l10n->t('No such circle'));
			}

			$o = Renderer::replaceMacros(Renderer::getMarkupTemplate('section_title.tpl'), [
				'$title' => $this->l10n->t('Circle: %s', $circle['name'])
			]) . $o;
		} elseif (self::$groupContactId) {
			$contact = Contact::getById(self::$groupContactId);
			if (DBA::isResult($contact)) {
				$o = Renderer::replaceMacros(Renderer::getMarkupTemplate('contact/list.tpl'), [
					'contacts' => [ModuleContact::getContactTemplateVars($contact)],
					'id' => $this->args->get(0),
				]) . $o;
			} else {
				$this->systemMessages->addNotice($this->l10n->t('Invalid contact.'));
			}
		} elseif (!$this->config->get('theme', 'hide_eventlist')) {
			$o .= Profile::getBirthdays();
			$o .= Profile::getEventsReminderHTML();
		}

		if (self::$order === 'received') {
			$ordering = '`received`';
		} elseif (self::$order === 'created') {
			$ordering = '`created`';
		} else {
			$ordering = '`commented`';
		}

		$o .= $this->conversation->render($items, Conversation::MODE_NETWORK, false, false, $ordering, $this->session->getLocalUserId());

		if ($this->pConfig->get($this->session->getLocalUserId(), 'system', 'infinite_scroll')) {
			$o .= HTML::scrollLoader();
		} else {
			$pager = new BoundariesPager(
				$this->l10n,
				$this->args->getQueryString(),
				$items[0][self::$order] ?? null,
				$items[count($items) - 1][self::$order] ?? null,
				self::$itemsPerPage
			);

			$o .= $pager->renderMinimal(count($items));
		}

		return $o;
	}

	/**
	 * Sets items as seen
	 *
	 * @param array $condition The array with the SQL condition
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	private static function setItemsSeenByCondition(array $condition)
	{
		if (empty($condition)) {
			return;
		}

		$unseen = Post::exists($condition);

		if ($unseen) {
			/// @todo handle huge "unseen" updates in the background to avoid timeout errors
			Item::update(['unseen' => false], $condition);
		}
	}

	/**
	 * Get the network tabs menu
	 *
	 * @return string Html of the network tabs
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	private function getTabsHTML()
	{
		// @todo user confgurable selection of tabs
		$tabs = $this->getTabArray($this->timeline->getNetworkFeeds($this->args->getCommand()), 'network');

		$arr = ['tabs' => $tabs];
		Hook::callAll('network_tabs', $arr);

		$tpl = Renderer::getMarkupTemplate('common_tabs.tpl');

		return Renderer::replaceMacros($tpl, ['$tabs' => $arr['tabs']]);
	}

	protected function parseRequest(array $request)
	{
		parent::parseRequest($request);

		self::$circleId = (int)($this->parameters['circle_id'] ?? 0);

		self::$groupContactId = (int)($this->parameters['contact_id'] ?? 0);

		if (!self::$selectedTab) {
			self::$selectedTab = self::getTimelineOrderBySession(DI::userSession(), $this->pConfig);
		} elseif (!$this->timeline->isChannel(self::$selectedTab) && !$this->timeline->isCommunity(self::$selectedTab)) {
			throw new HTTPException\BadRequestException($this->l10n->t('Network feed not available.'));
		}


		if (!empty($request['star'])) {
			self::$selectedTab = TimelineEntity::STAR;
			self::$star = true;
		} else {
			self::$star = self::$selectedTab == TimelineEntity::STAR;
		}

		if (!empty($request['mention'])) {
			self::$selectedTab = TimelineEntity::MENTION;
			self::$mention = true;
		} else {
			self::$mention = self::$selectedTab == TimelineEntity::MENTION;
		}

		if (!empty($request['order'])) {
			self::$selectedTab = $request['order'];
			self::$order = $request['order'];
			self::$star = false;
			self::$mention = false;
		} elseif (in_array(self::$selectedTab, [TimelineEntity::RECEIVED, TimelineEntity::STAR])) {
			self::$order = 'received';
		} elseif ((self::$selectedTab == TimelineEntity::CREATED) || $this->timeline->isChannel(self::$selectedTab)) {
			self::$order = 'created';
		} else {
			self::$order = 'commented';
		}

		self::$selectedTab = self::$selectedTab ?? self::$order;

		// Prohibit combined usage of "star" and "mention"
		if (self::$selectedTab == TimelineEntity::STAR) {
			self::$mention = false;
		} elseif (self::$selectedTab == TimelineEntity::MENTION) {
			self::$star = false;
		}

		$this->session->set('network-tab', self::$selectedTab);
		$this->pConfig->set($this->session->getLocalUserId(), 'network.view', 'selected_tab', self::$selectedTab);

		self::$network = $request['nets'] ?? '';

		self::$dateFrom = $this->parameters['from'] ?? '';
		self::$dateTo = $this->parameters['to'] ?? '';

		switch (self::$order) {
			case 'received':
				self::$max_id = $request['last_received'] ?? self::$max_id;
				break;
			case 'created':
				self::$max_id = $request['last_created'] ?? self::$max_id;
				break;
			case 'uriid':
				self::$max_id = $request['last_uriid'] ?? self::$max_id;
				break;
			default:
				self::$order = 'commented';
				self::$max_id = $request['last_commented'] ?? self::$max_id;
		}
	}

	protected function getItems(string $table, array $params, array $conditionFields = [])
	{
		$conditionFields['uid'] = $this->session->getLocalUserId();
		$conditionStrings = [];

		if (!is_null(self::$accountType)) {
			$conditionFields['contact-type'] = self::$accountType;
		}

		if (self::$star) {
			$conditionFields['starred'] = true;
		}
		if (self::$mention) {
			$conditionFields['mention'] = true;
		}
		if (self::$network) {
			$conditionFields['network'] = self::$network;
		}

		if (self::$dateFrom) {
			$conditionStrings = DBA::mergeConditions($conditionStrings, ["`received` <= ? ", DateTimeFormat::convert(self::$dateFrom, 'UTC', DI::app()->getTimeZone())]);
		}
		if (self::$dateTo) {
			$conditionStrings = DBA::mergeConditions($conditionStrings, ["`received` >= ? ", DateTimeFormat::convert(self::$dateTo, 'UTC', DI::app()->getTimeZone())]);
		}

		if (self::$circleId) {
			$conditionStrings = DBA::mergeConditions($conditionStrings, ["`contact-id` IN (SELECT `contact-id` FROM `group_member` WHERE `gid` = ?)", self::$circleId]);
		} elseif (self::$groupContactId) {
			$conditionStrings = DBA::mergeConditions($conditionStrings,
				["((`contact-id` = ?) OR `uri-id` IN (SELECT `parent-uri-id` FROM `post-user-view` WHERE (`contact-id` = ? AND `gravity` = ? AND `vid` = ? AND `uid` = ?)))",
				self::$groupContactId, self::$groupContactId, Item::GRAVITY_ACTIVITY, Verb::getID(Activity::ANNOUNCE), $this->session->getLocalUserId()]);
		}

		// Currently only the order modes "received" and "commented" are in use
		if (!empty(self::$item_uri_id)) {
			$conditionStrings = DBA::mergeConditions($conditionStrings, ['uri-id' => self::$item_uri_id]);
		} else {
			if (isset(self::$max_id)) {
				switch (self::$order) {
					case 'received':
						$conditionStrings = DBA::mergeConditions($conditionStrings, ["`received` < ?", self::$max_id]);
						break;
					case 'commented':
						$conditionStrings = DBA::mergeConditions($conditionStrings, ["`commented` < ?", self::$max_id]);
						break;
					case 'created':
						$conditionStrings = DBA::mergeConditions($conditionStrings, ["`created` < ?", self::$max_id]);
						break;
					case 'uriid':
						$conditionStrings = DBA::mergeConditions($conditionStrings, ["`uri-id` < ?", self::$max_id]);
						break;
				}
			}

			if (isset(self::$min_id)) {
				switch (self::$order) {
					case 'received':
						$conditionStrings = DBA::mergeConditions($conditionStrings, ["`received` > ?", self::$min_id]);
						break;
					case 'commented':
						$conditionStrings = DBA::mergeConditions($conditionStrings, ["`commented` > ?", self::$min_id]);
						break;
					case 'created':
						$conditionStrings = DBA::mergeConditions($conditionStrings, ["`created` > ?", self::$min_id]);
						break;
					case 'uriid':
						$conditionStrings = DBA::mergeConditions($conditionStrings, ["`uri-id` > ?", self::$min_id]);
						break;
				}
			}
		}

		if (isset(self::$min_id) && !isset(self::$max_id)) {
			// min_id quirk: querying in reverse order with min_id gets the most recent rows, regardless of how close
			// they are to min_id. We change the query ordering to get the expected data, and we need to reverse the
			// order of the results.
			$params['order'] = [self::$order => false];
		} else {
			$params['order'] = [self::$order => true];
		}

		$items = DBA::selectToArray($table, [], DBA::mergeConditions($conditionFields, $conditionStrings), $params);

		// min_id quirk, continued
		if (isset(self::$min_id) && !isset(self::$max_id)) {
			$items = array_reverse($items);
		}

		if (DBA::isResult($items)) {
			$parents = array_column($items, 'uri-id');
		} else {
			$parents = [];
		}

		// We aren't going to try and figure out at the item, circle, and page
		// level which items you've seen and which you haven't. If you're looking
		// at the top level network page just mark everything seen.
		if (!self::$circleId && !self::$groupContactId && !self::$star && !self::$mention) {
			$condition = ['unseen' => true, 'uid' => $this->session->getLocalUserId()];
			self::setItemsSeenByCondition($condition);
		} elseif (!empty($parents)) {
			$condition = ['unseen' => true, 'uid' => $this->session->getLocalUserId(), 'parent-uri-id' => $parents];
			self::setItemsSeenByCondition($condition);
		}

		return $items;
	}

	/**
	 * Returns the selected network tab of the currently logged-in user
	 *
	 * @param IHandleUserSessions         $session
	 * @param IManagePersonalConfigValues $pconfig
	 * @return string
	 */
	public static function getTimelineOrderBySession(IHandleUserSessions $session, IManagePersonalConfigValues $pconfig): string
	{
		return $session->get('network-tab')
			?? $pconfig->get($session->getLocalUserId(), 'network.view', 'selected_tab')
			?? '';
	}
}
