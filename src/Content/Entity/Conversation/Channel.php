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

namespace Friendica\Content\Entity\Conversation;

/**
 * @property-read string $code        Channel code
 * @property-read string $label       Channel label
 * @property-read string $description Channel description
 * @property-read string $accessKey   Access key
 */
final class Channel extends \Friendica\BaseEntity
{
	/** @var string */
	protected $code;
	/** @var string */
	protected $label;
	/** @var string */
	protected $description;
	/** @var string */
	protected $accessKey;

	public function __construct(string $code, string $label, string $description, string $accessKey)
	{
		$this->code        = $code;
		$this->label       = $label;
		$this->description = $description;
		$this->accessKey   = $accessKey;
	}
}
