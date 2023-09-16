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
use Friendica\Content\Nav;
use Friendica\Content\Text\HTML;
use Friendica\Content\Widget;
use Friendica\Content\Widget\TrendingTags;
use Friendica\Core\Cache\Capability\ICanCache;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\L10n;
use Friendica\Core\PConfig\Capability\IManagePersonalConfigValues;
use Friendica\Core\Renderer;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Module\Security\Login;
use Friendica\Network\HTTPException;
use Friendica\Database\Database;
use Friendica\Module\Response;
use Friendica\Navigation\SystemMessages;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

class Channel extends Timeline
{
	/** @var TimelineFactory */
	protected $timeline;
	/** @var Conversation */
	protected $conversation;
	/** @var App\Page */
	protected $page;
	/** @var SystemMessages */
	protected $systemMessages;

	public function __construct(TimelineFactory $timeline, Conversation $conversation, App\Page $page, SystemMessages $systemMessages, Mode $mode, IHandleUserSessions $session, Database $database, IManagePersonalConfigValues $pConfig, IManageConfigValues $config, ICanCache $cache, L10n $l10n, App\BaseURL $baseUrl, App\Arguments $args, LoggerInterface $logger, Profiler $profiler, Response $response, array $server, array $parameters = [])
	{
		parent::__construct($mode, $session, $database, $pConfig, $config, $cache, $l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		$this->timeline       = $timeline;
		$this->conversation   = $conversation;
		$this->page           = $page;
		$this->systemMessages = $systemMessages;
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
			$tabs = $this->getTabArray($this->timeline->getChannelsForUser($this->session->getLocalUserId()), 'channel');
			$tabs = array_merge($tabs, $this->getTabArray($this->timeline->getCommunities(true), 'channel'));

			$tab_tpl = Renderer::getMarkupTemplate('common_tabs.tpl');
			$o .= Renderer::replaceMacros($tab_tpl, ['$tabs' => $tabs]);

			Nav::setSelected('channel');

			$this->page['aside'] .= Widget::accountTypes('channel/' . $this->selectedTab, $this->accountTypeString);

			if (!in_array($this->selectedTab, [TimelineEntity::FOLLOWERS, TimelineEntity::FORYOU]) && $this->config->get('system', 'community_no_sharer')) {
				$this->page['aside'] .= $this->getNoSharerWidget('channel');
			}

			if (Feature::isEnabled($this->session->getLocalUserId(), 'trending_tags')) {
				$this->page['aside'] .= TrendingTags::getHTML($this->selectedTab);
			}

			// We need the editor here to be able to reshare an item.
			$o .= $this->conversation->statusEditor([], 0, true);
		}

		if ($this->timeline->isChannel($this->selectedTab)) {
			$items = $this->getChannelItems();
			$order = 'created';
		} else {
			$items = $this->getCommunityItems();
			$order = 'commented';
		}

		if (!$this->database->isResult($items)) {
			$this->systemMessages->addNotice($this->l10n->t('No results.'));
			return $o;
		}

		$o .= $this->conversation->render($items, Conversation::MODE_CHANNEL, false, false, $order, $this->session->getLocalUserId());

		$pager = new BoundariesPager(
			$this->l10n,
			$this->args->getQueryString(),
			$items[0][$order],
			$items[count($items) - 1][$order],
			$this->itemsPerPage
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
		parent::parseRequest($request);

		if (!$this->selectedTab) {
			$this->selectedTab = TimelineEntity::FORYOU;
		}

		if (!$this->timeline->isChannel($this->selectedTab) && !$this->timeline->isCommunity($this->selectedTab)) {
			throw new HTTPException\BadRequestException($this->l10n->t('Channel not available.'));
		}

		$this->maxId = $request['last_created'] ?? $this->maxId;
		$this->minId = $request['first_created'] ?? $this->minId;
	}
}
