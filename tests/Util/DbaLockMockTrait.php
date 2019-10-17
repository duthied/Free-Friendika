<?php

namespace Friendica\Test\Util;

use Friendica\Core\Cache;
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
	 *@see DatabaseLock::acquireLock()
	 *
	 */
	public function mockAcquireLock($key, $ttl = Cache::FIVE_MINUTES, $locked = false, $pid = null, $rowExists = true, $time = null, $times = null)
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
	 *@see DatabaseLock::releaseLock()
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
