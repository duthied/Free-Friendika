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

namespace Friendica\Test\Util;

use Friendica\Core\Cache\Duration;

trait DbaCacheMockTrait
{
	/**
	 * @var
	 */
	protected $dba;

	public function __construct()
	{
	}

	protected function mockDelete($key, $return = true, $times = null)
	{
		$this->mockDBADelete('cache', ['k' => $key], $return, $times);
	}

	protected function mockGet($key, $return = null, $time = null, $times = null)
	{
		if ($time === null) {
			$time = time();
		}

		$value = @serialize($return);

		$this->mockSelectFirst('cache', ['v'], ['`k` = ? AND (`expires` >= ? OR `expires` = -1)', $key, $time], ['v' => $value], $times);
		$this->mockIsResult(['v' => $value], isset($return), $times);
	}

	protected function mockSet($key, $value, $ttl = Duration::FIVE_MINUTES, $time = null, $return = true, $times = null)
	{
		if ($time === null) {
			$time = time();
		}

		if ($ttl > 0) {
			$this->mockUtc('now + ' . $ttl . 'seconds', $time + $ttl, $times);
			$fields = [
				'v' => serialize($value),
				'expires' => $time + $ttl,
				'updated' => $time
			];
		} else {
			$fields = [
				'v' => serialize($value),
				'expires' => -1,
				'updated' => $time
			];
		}

		$this->mockDBAUpdate('cache', $fields, ['k' => $key], true, $return, $times);
	}

	protected function mockClear($outdated = true, $return = true, $times = null)
	{
		if ($outdated) {
			$this->mockDBADelete('cache', ['`expires` < NOW()'], $return, $times);
		} else {
			$this->mockDBADelete('cache', ['`k` IS NOT NULL '], $return, $times);
		}
	}

	protected function mockGetAllKeys($prefix = null, $return = [], $time = null, $times = null)
	{
		if ($time === null) {
			$time = time();
		}

		if (empty($prefix)) {
			$where = ['`expires` >= ?', $time];
		} else {
			$where = ['`expires` >= ? AND `k` LIKE CONCAT(?, \'%\')', $time, $prefix];
		}

		$this->mockSelect('cache', ['k'], $where, $return, $times);
		$this->mockFetchLoop($return, $times);
		$this->mockDbaClose(true, $times);
	}
}
