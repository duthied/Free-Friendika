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
use Friendica\Core\Lock\DatabaseLock;

trait DbaLockMockTrait
{
	use DBAMockTrait;
	use DateTimeFormatMockTrait;

	/**
	 * Mocking acquireLock with DBA-backend
	 *
	 * @param mixed    $key       The key to lock
	 * @param int      $ttl       The TimeToLive
	 *
	 * @param bool     $locked    Was the lock already set?
	 * @param null     $pid       The PID which was set
	 * @param bool     $rowExists True, if a row already exists in the lock table
	 * @param null     $time      The current timestamp
	 * @param null|int $times     How often the method will get used
	 *
	 *@see DatabaseLock::acquire()
	 *
	 */
	public function mockAcquireLock($key, $ttl = Duration::FIVE_MINUTES, $locked = false, $pid = null, $rowExists = true, $time = null, $times = null)
	{
		if ($time === null) {
			$time = time();
		}

		if ($pid === null) {
			$pid = getmypid();
		}

		$this->mockDbaLock('locks', true, $times);

		$this->mockUtcNow($time, $times);
		$result = ['locked' => $locked, 'pid' => $pid];
		$this->mockSelectFirst('locks', ['locked', 'pid'], ['`name` = ? AND `expires` >= ?', $key, $time], $result, $times);
		$this->mockIsResult($result, $rowExists, $times);

		if ($rowExists) {
			if (!$locked ) {
				$this->mockUtc('now + ' . $ttl . 'seconds', $time, $times);
				$this->mockDBAUpdate('locks', ['locked' => true, 'pid' => $pid, 'expires' => $time], ['name' => $key], [], true, $times);
			}
		} else {
			$this->mockUtc('now + ' . $ttl . 'seconds', $time, $times);
			$this->mockDBAInsert('locks', ['name' => $key, 'locked' => true, 'pid' => $pid, 'expires' => $time], false, true, $times);
		}

		$this->mockDbaUnlock($times);
	}

	/**
	 * Mocking isLocked with DBA-backend
	 *
	 * @param mixed     $key    The key of the lock
	 * @param null|bool $return True, if the key is already locked
	 * @param null      $time   The current timestamp
	 * @param null|int  $times  How often the method will get used
	 *
	 *@see DatabaseLock::isLocked()
	 *
	 */
	public function mockIsLocked($key, $return = true, $time = null, $times = null)
	{
		if ($time === null) {
			$time = time();
		}

		$this->mockUtcNow($time, $times);
		$return = ((isset($return)) ? ['locked' => $return] : null);
		$this->mockSelectFirst('locks', ['locked'], ['`name` = ? AND `expires` >= ?', $key, $time], $return, $times);
		$this->mockIsResult($return, (isset($return) && $return), $times);
	}

	/**
	 * Mocking releaseAll with DBA-backend
	 *
	 * @param null     $pid   The PID which was set
	 * @param null|int $times How often the method will get used
	 *
	 *@see DatabaseLock::releaseAll()
	 *
	 */
	public function mockReleaseAll($pid = null, $times = null)
	{
		if ($pid === null) {
			$pid = getmypid();
		}

		$this->mockDBADelete('locks', ['pid' => $pid], true, $times);
	}

	/**
	 * Mocking ReleaseLock with DBA-backend
	 *
	 * @param mixed    $key    The key to release
	 * @param null|int $pid    The PID which was set
	 * @param null|int $times  How often the method will get used
	 *
	 *@see DatabaseLock::release()
	 *
	 */
	public function mockReleaseLock($key, $pid = null, $times = null)
	{
		if ($pid === null) {
			$pid = getmypid();
		}

		$this->mockDBADelete('locks', ['name' => $key, 'pid' => $pid], true, $times);
	}
}
