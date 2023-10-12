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
use Friendica\Content\Conversation\Entity\Network as NetworkEntity;

final class Network extends Timeline
{
	/**
	 * List of available network timelines
	 *
	 * @param string $command
	 * @return Timelines
	 */
	public function getTimelines(string $command): Timelines
	{
		$tabs = [
			new NetworkEntity(NetworkEntity::COMMENTED, $this->l10n->t('Latest Activity'), $this->l10n->t('Sort by latest activity'), 'e', $command . '?' . http_build_query(['order' => 'commented'])),
			new NetworkEntity(NetworkEntity::RECEIVED, $this->l10n->t('Latest Posts'), $this->l10n->t('Sort by post received date'), 't', $command . '?' . http_build_query(['order' => 'received'])),
			new NetworkEntity(NetworkEntity::CREATED, $this->l10n->t('Latest Creation'), $this->l10n->t('Sort by post creation date'), 'q', $command . '?' . http_build_query(['order' => 'created'])),
			new NetworkEntity(NetworkEntity::MENTION, $this->l10n->t('Personal'), $this->l10n->t('Posts that mention or involve you'), 'r', $command . '?' . http_build_query(['mention' => true])),
			new NetworkEntity(NetworkEntity::STAR, $this->l10n->t('Starred'), $this->l10n->t('Favourite Posts'), 'm', $command . '?' . http_build_query(['star' => true])),
		];
		return new Timelines($tabs);
	}

	public function isTimeline(string $selectedTab): bool
	{
		return in_array($selectedTab, [NetworkEntity::COMMENTED, NetworkEntity::RECEIVED, NetworkEntity::CREATED, NetworkEntity::MENTION, NetworkEntity::STAR]);
	}
}
