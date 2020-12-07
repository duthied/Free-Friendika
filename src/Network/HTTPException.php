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

namespace Friendica\Network;

use Exception;

/**
 * Throwable exceptions to return HTTP status code
 *
 * This list of Exception has been extracted from
 * here http://racksburg.com/choosing-an-http-status-code/
 */
abstract class HTTPException extends Exception
{
	public $httpdesc = '';

	public function __construct($message = '', Exception $previous = null)
	{
		parent::__construct($message, $this->code, $previous);

		if (empty($this->httpdesc)) {
			$classname = str_replace('Exception', '', str_replace('Friendica\Network\HTTPException\\', '', get_class($this)));
			$this->httpdesc = preg_replace("|([a-z])([A-Z])|",'$1 $2', $classname);
		}
	}
}
