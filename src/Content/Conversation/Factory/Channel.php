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
use Friendica\Content\Conversation\Entity\Channel as ChannelEntity;
use Friendica\Model\User;
use Friendica\Content\Conversation\Entity\Timeline as TimelineEntity;
use Friendica\Content\Conversation\Repository\Channel as ChannelRepository;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\L10n;
use Psr\Log\LoggerInterface;

class Channel extends Timeline
{
	public function __construct(ChannelRepository $channel, L10n $l10n, LoggerInterface $logger, IManageConfigValues $config)
	{
		parent::__construct($channel, $l10n, $logger, $config);
	}

	public function createFromTableRow(array $row): TimelineEntity
	{
		return new TimelineEntity(
			$row['id'] ?? null,
			$row['label'],
			$row['description'] ?? null,
			$row['access-key'] ?? null,
			null,
			$row['uid'],
			$row['include-tags'] ?? null,
			$row['exclude-tags'] ?? null,
			$row['full-text-search'] ?? null,
			$row['media-type'] ?? null,
			$row['circle'] ?? null,
		);
	}

	/**
	 * List of available channels
	 *
	 * @param integer $uid
	 * @return Timelines
	 */
	public function getForUser(int $uid): Timelines
	{
		$language  = User::getLanguageCode($uid);
		$languages = $this->l10n->getAvailableLanguages(true);

		$tabs = [
			new ChannelEntity(ChannelEntity::FORYOU, $this->l10n->t('For you'), $this->l10n->t('Posts from contacts you interact with and who interact with you'), 'y'),
			new ChannelEntity(ChannelEntity::WHATSHOT, $this->l10n->t('What\'s Hot'), $this->l10n->t('Posts with a lot of interactions'), 'h'),
			new ChannelEntity(ChannelEntity::LANGUAGE, $languages[$language], $this->l10n->t('Posts in %s', $languages[$language]), 'g'),
			new ChannelEntity(ChannelEntity::FOLLOWERS, $this->l10n->t('Followers'), $this->l10n->t('Posts from your followers that you don\'t follow'), 'f'),
			new ChannelEntity(ChannelEntity::SHARERSOFSHARERS, $this->l10n->t('Sharers of sharers'), $this->l10n->t('Posts from accounts that are followed by accounts that you follow'), 'r'),
			new ChannelEntity(ChannelEntity::IMAGE, $this->l10n->t('Images'), $this->l10n->t('Posts with images'), 'i'),
			new ChannelEntity(ChannelEntity::AUDIO, $this->l10n->t('Audio'), $this->l10n->t('Posts with audio'), 'd'),
			new ChannelEntity(ChannelEntity::VIDEO, $this->l10n->t('Videos'), $this->l10n->t('Posts with videos'), 'v'),
		];

		foreach ($this->channel->selectByUid($uid) as $channel) {
			$tabs[] = $channel;
		}

		return new Timelines($tabs);
	}

	public function isTimeline(string $selectedTab, int $uid): bool
	{
		if (is_numeric($selectedTab) && $uid && $this->channel->existsById($selectedTab, $uid)) {
			return true;
		}
		return in_array($selectedTab, [ChannelEntity::WHATSHOT, ChannelEntity::FORYOU, ChannelEntity::FOLLOWERS, ChannelEntity::SHARERSOFSHARERS, ChannelEntity::IMAGE, ChannelEntity::VIDEO, ChannelEntity::AUDIO, ChannelEntity::LANGUAGE]);
	}
}
