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
use Friendica\Content\Conversation\Entity\Community as CommunityEntity;
use Friendica\Module\Conversation\Community as CommunityModule;

final class Community extends Timeline
{
	/**
	 * List of available communities
	 *
	 * @param boolean $authenticated
	 * @return Timelines
	 */
	public function getTimelines(bool $authenticated): Timelines
	{
		$page_style = $this->config->get('system', 'community_page_style');

		$tabs = [];

		if (($authenticated || in_array($page_style, [CommunityModule::LOCAL_AND_GLOBAL, CommunityModule::LOCAL])) && empty($this->config->get('system', 'singleuser'))) {
			$tabs[] = new CommunityEntity(CommunityEntity::LOCAL, $this->l10n->t('Local Community'), $this->l10n->t('Posts from local users on this server'), 'l');
		}

		if ($authenticated || in_array($page_style, [CommunityModule::LOCAL_AND_GLOBAL, CommunityModule::GLOBAL])) {
			$tabs[] = new CommunityEntity(CommunityEntity::GLOBAL, $this->l10n->t('Global Community'), $this->l10n->t('Posts from users of the whole federated network'), 'g');
		}
		return new Timelines($tabs);
	}

	public function isTimeline(string $selectedTab): bool
	{
		return in_array($selectedTab, [CommunityEntity::LOCAL, CommunityEntity::GLOBAL]);
	}
}
