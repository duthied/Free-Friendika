<?php
/**
 * @copyright Copyright (C) 2021, Friendica
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
namespace Friendica\Model\Log;

use \Friendica\Util\ReversedFileReader;
use \Friendica\Object\Log\ParsedLog;


/**
 * An iterator which returns `\Friendica\Objec\Log\ParsedLog` instances
 *
 * Uses `\Friendica\Util\ReversedFileReader` to fetch log lines
 * from newest to oldest
 */
class ParsedLogIterator implements \Iterator
{
	public function __construct(string $filename, int $limit=0)
	{
		$this->reader = new ReversedFileReader($filename);
		$this->_value = null;
		$this->_limit = $limit;
	}

	public function next()
	{
		$this->reader->next();
		if ($this->_limit > 0 && $this->reader->key() > $this->_limit) {
			$this->_value = null;
			return;
		}
		if ($this->reader->valid()) {
			$line = $this->reader->current();
			$this->_value = new ParsedLog($this->reader->key(), $line);
		} else {
			$this->_value = null;
		}
	}


	public function rewind()
	{
		$this->_value = null;
		$this->reader->rewind();
		$this->next();
	}

	public function key()
	{
		return $this->reader->key();
	}

	public function current()
	{
		return $this->_value;
	}

	public function valid()
	{
		return ! is_null($this->_value);
	}

}

