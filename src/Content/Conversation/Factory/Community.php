<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

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
