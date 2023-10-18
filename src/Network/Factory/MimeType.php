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

namespace Friendica\Network\Factory;

use Friendica\BaseFactory;
use Friendica\Network\Entity;

/**
 * Implementation of the Content-Type header value from the MIME type RFC
 *
 * @see https://www.rfc-editor.org/rfc/rfc2045#section-5
 */
class MimeType extends BaseFactory
{
	public function createFromContentType(?string $contentType): Entity\MimeType
	{
		if ($contentType) {
			$parameterStrings = explode(';', $contentType);
			$mimetype = array_shift($parameterStrings);

			$types = explode('/', $mimetype);
			if (count($types) >= 2) {
				$filetype = strtolower($types[0]);
				$subtype = strtolower($types[1]);
			} else {
				$this->logger->notice('Unknown MimeType', ['type' => $contentType]);
			}

			$parameters = [];
			foreach ($parameterStrings as $parameterString) {
				$parameterString = trim($parameterString);
				$parameterParts = explode('=', $parameterString, 2);
				if (count($parameterParts) < 2) {
					continue;
				}

				$attribute = trim($parameterParts[0]);
				$valueString = trim($parameterParts[1]);

				if ($valueString[0] == '"' && $valueString[strlen($valueString) - 1] == '"') {
					$valueString = substr(str_replace(['\\"', '\\\\', "\\\r"], ['"', '\\', "\r"], $valueString), 1, -1);
				}

				$value = preg_replace('#\s*\([^()]*?\)#', '', $valueString);

				$parameters[$attribute] = $value;
			}
		}

		return new Entity\MimeType(
			$filetype ?? 'unkn',
			$subtype ?? 'unkn',
			$parameters ?? [],
		);
	}
}
