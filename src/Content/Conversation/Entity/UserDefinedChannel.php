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

namespace Friendica\Content\Conversation\Entity;

class UserDefinedChannel extends Channel
{
	public function __construct(string $code = null, string $label = null, string $description = null, string $accessKey = null, string $path = null, int $uid = null, string $includeTags = null, string $excludeTags = null, string $fullTextSearch = null, int $mediaType = null, int $circle = null)
	{
		parent::__construct($code, $label, $description, $accessKey, $path, $uid, $includeTags, $excludeTags, $fullTextSearch, $mediaType, $circle);
	}
}
