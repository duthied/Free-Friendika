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
use Friendica\Content\Conversation\Repository\Channel as ChannelRepository;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\L10n;
use Psr\Log\LoggerInterface;

final class UserDefinedChannel extends Timeline
{
	public function __construct(ChannelRepository $channel, L10n $l10n, LoggerInterface $logger, IManageConfigValues $config)
	{
		parent::__construct($channel, $l10n, $logger, $config);
	}

	/**
	 * List of available user defined channels
	 *
	 * @param integer $uid
	 * @return Timelines
	 */
	public function getForUser(int $uid): Timelines
	{
		foreach ($this->channel->selectByUid($uid) as $channel) {
			$tabs[] = $channel;
		}

		return new Timelines($tabs);
	}

	public function isTimeline(string $selectedTab, int $uid): bool
	{
		return is_numeric($selectedTab) && $uid && $this->channel->existsById($selectedTab, $uid);
	}
}
