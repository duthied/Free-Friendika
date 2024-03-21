<?php
/**
 * @copyright Copyright (C) 2010-2024, the Friendica project
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

namespace Friendica\Object\Api\Mastodon\InstanceV2;

use Friendica\BaseDataTransferObject;

/**
 * Class StatusConfig
 *
 * @see https://docs.joinmastodon.org/entities/Instance/
 */
class StatusesConfig extends BaseDataTransferObject
{
	/** @var int */
	protected $max_characters = 0;
	/** @var int */
	protected $max_media_attachments = 0;
	/** @var int */
	protected $characters_reserved_per_url = 0;

	/**
	 * @param int $max_characters
	 * @param int $max_media_attachments
	 * @param int $characters_reserved_per_url
	 */
	public function __construct(int $max_characters, int $max_media_attachments, int $characters_reserved_per_url)
	{
		$this->max_characters              = $max_characters;
		$this->max_media_attachments       = $max_media_attachments;
		$this->characters_reserved_per_url = $characters_reserved_per_url;
	}
}
