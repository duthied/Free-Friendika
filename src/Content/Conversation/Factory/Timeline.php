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

use Friendica\Capabilities\ICanCreateFromTableRow;
use Friendica\Content\Conversation\Entity\Timeline as TimelineEntity;
use Friendica\Content\Conversation\Repository\Channel;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\L10n;
use Psr\Log\LoggerInterface;

class Timeline extends \Friendica\BaseFactory implements ICanCreateFromTableRow
{
	/** @var L10n */
	protected $l10n;
	/** @var IManageConfigValues The config */
	protected $config;
	/** @var Channel */
	protected $channelRepository;

	public function __construct(Channel $channel, L10n $l10n, LoggerInterface $logger, IManageConfigValues $config)
	{
		parent::__construct($logger);

		$this->channelRepository = $channel;
		$this->l10n              = $l10n;
		$this->config            = $config;
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
}
