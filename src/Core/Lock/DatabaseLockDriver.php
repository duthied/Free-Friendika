<?php

namespace Friendica\Core\Lock;

use Friendica\Core\Cache;
use Friendica\Database\DBA;
use Friendica\Util\DateTimeFormat;

/**
 * Locking driver that stores the locks in the database
 */
class DatabaseLockDriver extends AbstractLockDriver
{
	/**
	 * The current ID of the process
	 *
	 * @var int
	 */
	private $pid;

	/**
	 * @param null|int $pid The Id of the current process (null means determine automatically)
	 */
	public function __construct($pid = null)
	{
		$this->pid = isset($pid) ? $pid : getmypid();
	}

	/**
	 * (@inheritdoc)
	 */
	public function acquireLock($key, $timeout = 120, $ttl = Cache::FIVE_MINUTES)
	{
		$got_lock = false;
		$start = time();

		do {
			DBA::lock('locks');
			$lock = DBA::selectFirst('locks', ['locked', 'pid'], ['`name` = ? AND `expires` >= ?', $key, DateTimeFormat::utcNow()]);

			if (DBA::isResult($lock)) {
				if ($lock['locked']) {
					// We want to lock something that was already locked by us? So we got the lock.
					if ($lock['pid'] == $this->pid) {
						$got_lock = true;
					}
				}
				if (!$lock['locked']) {
					DBA::update('locks', ['locked' => true, 'pid' => $this->pid, 'expires' => DateTimeFormat::utc('now + ' . $ttl . 'seconds')], ['name' => $key]);
					$got_lock = true;
				}
			} else {
				DBA::insert('locks', ['name' => $key, 'locked' => true, 'pid' => $this->pid, 'expires' => DateTimeFormat::utc('now + ' . $ttl . 'seconds')]);
				$got_lock = true;
				$this->markAcquire($key);
			}

			DBA::unlock();

			if (!$got_lock && ($timeout > 0)) {
				usleep(rand(100000, 2000000));
			}
		} while (!$got_lock && ((time() - $start) < $timeout));

		return $got_lock;
	}

	/**
	 * (@inheritdoc)
	 */
	public function releaseLock($key, $force = false)
	{
		if ($force) {
			$where = ['name' => $key];
		} else {
			$where = ['name' => $key, 'pid' => $this->pid];
		}

		DBA::delete('locks', $where);

		$this->markRelease($key);

		return;
	}

	/**
	 * (@inheritdoc)
	 */
	public function releaseAll()
	{
		DBA::delete('locks', ['pid' => $this->pid]);

		$this->acquiredLocks = [];
	}

	/**
	 * (@inheritdoc)
	 */
	public function isLocked($key)
	{
		$lock = DBA::selectFirst('locks', ['locked'], ['`name` = ? AND `expires` >= ?', $key, DateTimeFormat::utcNow()]);

		if (DBA::isResult($lock)) {
			return $lock['locked'] !== false;
		} else {
			return false;
		}
	}
}
