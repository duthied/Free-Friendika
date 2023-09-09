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

namespace Friendica\Content\Conversation\Factory;

use Friendica\Content\Conversation\Collection\Timelines;
use Friendica\Model\User;
use Friendica\Content\Conversation\Entity\Timeline as TimelineEntity;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\L10n;
use Friendica\Module\Conversation\Community;
use Psr\Log\LoggerInterface;

final class Timeline extends \Friendica\BaseFactory
{
	/** @var L10n */
	protected $l10n;
	/** @var IManageConfigValues The config */
	protected $config;

	public function __construct(L10n $l10n, LoggerInterface $logger, IManageConfigValues $config)
	{
		parent::__construct($logger);

		$this->l10n   = $l10n;
		$this->config = $config;
	}

	/**
	 * List of available channels
	 *
	 * @param integer $uid
	 * @return Timelines
	 */
	public function getChannelsForUser(int $uid): Timelines
	{
		$language  = User::getLanguageCode($uid);
		$languages = $this->l10n->getAvailableLanguages(true);

		$tabs = [
			new TimelineEntity(TimelineEntity::FORYOU, $this->l10n->t('For you'), $this->l10n->t('Posts from contacts you interact with and who interact with you'), 'y'),
			new TimelineEntity(TimelineEntity::WHATSHOT, $this->l10n->t('What\'s Hot'), $this->l10n->t('Posts with a lot of interactions'), 'h'),
			new TimelineEntity(TimelineEntity::LANGUAGE, $languages[$language], $this->l10n->t('Posts in %s', $languages[$language]), 'g'),
			new TimelineEntity(TimelineEntity::FOLLOWERS, $this->l10n->t('Followers'), $this->l10n->t('Posts from your followers that you don\'t follow'), 'f'),
			new TimelineEntity(TimelineEntity::SHARERSOFSHARERS, $this->l10n->t('Sharers of sharers'), $this->l10n->t('Posts from accounts that are followed by accounts that you follow'), 'r'),
			new TimelineEntity(TimelineEntity::IMAGE, $this->l10n->t('Images'), $this->l10n->t('Posts with images'), 'i'),
			new TimelineEntity(TimelineEntity::AUDIO, $this->l10n->t('Audio'), $this->l10n->t('Posts with audio'), 'd'),
			new TimelineEntity(TimelineEntity::VIDEO, $this->l10n->t('Videos'), $this->l10n->t('Posts with videos'), 'v'),
		];
		return new Timelines($tabs);
	}

	/**
	 * List of available communities
	 *
	 * @param boolean $authenticated
	 * @return Timelines
	 */
	public function getCommunities(bool $authenticated): Timelines
	{
		$page_style = $this->config->get('system', 'community_page_style');

		$tabs = [];

		if (($authenticated || in_array($page_style, [Community::LOCAL_AND_GLOBAL, Community::LOCAL])) && empty($this->config->get('system', 'singleuser'))) {
			$tabs[] = new TimelineEntity(TimelineEntity::LOCAL, $this->l10n->t('Local Community'), $this->l10n->t('Posts from local users on this server'), 'l');
		}

		if ($authenticated || in_array($page_style, [Community::LOCAL_AND_GLOBAL, Community::GLOBAL])) {
			$tabs[] = new TimelineEntity(TimelineEntity::GLOBAL, $this->l10n->t('Global Community'), $this->l10n->t('Posts from users of the whole federated network'), 'g');
		}
		return new Timelines($tabs);
	}

	/**
	 * List of available network feeds
	 *
	 * @param string $command
	 * @return Timelines
	 */
	public function getNetworkFeeds(string $command): Timelines
	{
		$tabs = [
			new TimelineEntity(TimelineEntity::COMMENTED, $this->l10n->t('Latest Activity'), $this->l10n->t('Sort by latest activity'), 'e', $command . '?' . http_build_query(['order' => 'commented'])),
			new TimelineEntity(TimelineEntity::RECEIVED, $this->l10n->t('Latest Posts'), $this->l10n->t('Sort by post received date'), 't', $command . '?' . http_build_query(['order' => 'received'])),
			new TimelineEntity(TimelineEntity::CREATED, $this->l10n->t('Latest Creation'), $this->l10n->t('Sort by post creation date'), 'q', $command . '?' . http_build_query(['order' => 'created'])),
			new TimelineEntity(TimelineEntity::MENTION, $this->l10n->t('Personal'), $this->l10n->t('Posts that mention or involve you'), 'r', $command . '?' . http_build_query(['mention' => true])),
			new TimelineEntity(TimelineEntity::STAR, $this->l10n->t('Starred'), $this->l10n->t('Favourite Posts'), 'm', $command . '?' . http_build_query(['star' => true])),
		];
		return new Timelines($tabs);
	}

	public function isCommunity(string $selectedTab): bool
	{
		return in_array($selectedTab, [TimelineEntity::LOCAL, TimelineEntity::GLOBAL]);
	}

	public function isChannel(string $selectedTab): bool
	{
		return in_array($selectedTab, [TimelineEntity::WHATSHOT, TimelineEntity::FORYOU, TimelineEntity::FOLLOWERS, TimelineEntity::SHARERSOFSHARERS, TimelineEntity::IMAGE, TimelineEntity::VIDEO, TimelineEntity::AUDIO, TimelineEntity::LANGUAGE]);
	}
}
