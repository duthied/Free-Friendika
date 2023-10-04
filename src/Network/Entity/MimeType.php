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

namespace Friendica\Network\Entity;

use Friendica\BaseEntity;

/**
 * Implementation of the Content-Type header value from the MIME type RFC
 *
 * @see https://www.rfc-editor.org/rfc/rfc2045#section-5
 *
 * @property-read string $type
 * @property-read string $subtype
 * @property-read array $parameters
 */
class MimeType extends BaseEntity
{
	/** @var string */
	protected $type;
	/** @var string */
	protected $subtype;
	/** @var array */
	protected $parameters;

	public function __construct(string $type, string $subtype, array $parameters = [])
	{
		$this->type = $type;
		$this->subtype = $subtype;
		$this->parameters = $parameters;
	}

	public function __toString(): string
	{
		$parameters = array_map(function (string $attribute, string $value) {
			if (
				strpos($value, '"') !== false ||
				strpos($value, '\\') !== false ||
				strpos($value, "\r") !== false
			) {
				$value = '"' . str_replace(['\\', '"', "\r"], ['\\\\', '\\"', "\\\r"], $value) . '"';
			}

			return '; ' . $attribute . '=' . $value;
		}, array_keys($this->parameters), array_values($this->parameters));

		return $this->type . '/' .
			$this->subtype .
			implode('', $parameters);
	}
}
