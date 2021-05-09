<?php
/**
 * @copyright Copyright (C) 2010-2021, the Friendica project
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

namespace Friendica\Object\Api\Mastodon;

use Friendica\BaseDataTransferObject;

/**
 * Class Field
 *
 * @see https://docs.joinmastodon.org/entities/field/
 */
class Field extends BaseDataTransferObject
{
	/** @var string */
	protected $name;
	/** @var string (HTML) */
	protected $value;
	/** @var string (Datetime)*/
	protected $verified_at;

	public function __construct(string $name, string $value)
	{
		$this->name = $name;
		$this->value = $value;
		// Link verification unsupported
		$this->verified_at = null;
	}
}
