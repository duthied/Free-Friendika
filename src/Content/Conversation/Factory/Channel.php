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

use Friendica\Model\User;
use Friendica\Content\Conversation\Entity\Channel as ChannelEntity;
use Friendica\Core\L10n;
use Psr\Log\LoggerInterface;

final class Channel extends \Friendica\BaseFactory
{
	/** @var L10n */
	protected $l10n;

	public function __construct(L10n $l10n, LoggerInterface $logger)
	{
		parent::__construct($logger);

		$this->l10n = $l10n;
	}

	/**
	 * List of available channels
	 *
	 * @param integer $uid
	 * @return array
	 */
	public function getForUser(int $uid): array
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
		return $tabs;
	}
}
