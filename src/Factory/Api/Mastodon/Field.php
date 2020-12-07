<?php
/**
 * @copyright Copyright (C) 2020, Friendica
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

namespace Friendica\Factory\Api\Mastodon;

use Friendica\BaseFactory;
use Friendica\Collection\Api\Mastodon\Fields;
use Friendica\Collection\ProfileFields;
use Friendica\Content\Text\BBCode;
use Friendica\Model\ProfileField;
use Friendica\Network\HTTPException;

class Field extends BaseFactory
{
	/**
	 * @param ProfileField $profileField
	 * @return \Friendica\Api\Entity\Mastodon\Field
	 * @throws HTTPException\InternalServerErrorException
	 */
	public function createFromProfileField(ProfileField $profileField)
	{
		return new \Friendica\Api\Entity\Mastodon\Field($profileField->label, BBCode::convert($profileField->value, false, BBCode::ACTIVITYPUB));
	}

	/**
	 * @param ProfileFields $profileFields
	 * @return Fields
	 * @throws HTTPException\InternalServerErrorException
	 */
	public function createFromProfileFields(ProfileFields $profileFields)
	{
		$fields = [];

		foreach ($profileFields as $profileField) {
			$fields[] = $this->createFromProfileField($profileField);
		}

		return new Fields($fields);
	}
}
