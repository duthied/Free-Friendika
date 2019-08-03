<?php

namespace Friendica\Core\Lock;

use Friendica\Core\Cache;
use Friendica\Database\Database;
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
	 * @var Database The database connection of Friendica
	 */
	private $dba;

	/**
	 * @param null|int $pid The Id of the current process (null means determine automatically)
	 */
	public function __construct(Database $dba, $pid = null)
	{
		$this->dba = $dba;
		$this->pid = isset($pid) ? $pid : getmypid();
	}

	/**
	 * (@inheritdoc)
	 */
	public function acquireLock($key, $timeout = 120, $ttl = Cache::FIVE_MINUTES)
	{
		$got_lock = false;
		$start    = time();

		do {
			$this->dba->lock('locks');
			$lock = $this->dba->selectFirst('locks', ['locked', 'pid'], ['`name` = ? AND `expires` >= ?', $key, DateTimeFormat::utcNow()]);

			if ($this->dba->isResult($lock)) {
				if ($lock['locked']) {
					// We want to lock something that was already locked by us? So we got the lock.
					if ($lock['pid'] == $this->pid) {
						$got_lock = true;
					}
				}
				if (!$lock['locked']) {
					$this->dba->update('locks', ['locked' => true, 'pid' => $this->pid, 'expires' => DateTimeFormat::utc('now + ' . $ttl . 'seconds')], ['name' => $key]);
					$got_lock = true;
				}
			} else {
				$this->dba->insert('locks', ['name' => $key, 'locked' => true, 'pid' => $this->pid, 'expires' => DateTimeFormat::utc('now + ' . $ttl . 'seconds')]);
				$got_lock = true;
				$this->markAcquire($key);
			}

			$this->dba->unlock();

			if (!$got_lock && ($timeout > 0)) {
				usleep(rand(100000, 2000000));
			}
		} while (!$got_lock && ((time() - $start) < $timeout));

		return $got_lock;
	}

	/**
	 * (@inheritdoc)
	 */
	public function releaseLock($key, $override = false)
	{
		if ($override) {
			$where = ['name' => $key];
		} else {
			$where = ['name' => $key, 'pid' => $this->pid];
		}

		$return = $this->dba->delete('locks', $where);

		$this->markRelease($key);

		return $return;
	}

	/**
	 * (@inheritdoc)
	 */
	public function releaseAll()
	{
		$return = $this->dba->delete('locks', ['pid' => $this->pid]);

		$this->acquiredLocks = [];

		return $return;
	}

	/**
	 * (@inheritdoc)
	 */
	public function isLocked($key)
	{
		$lock = $this->dba->selectFirst('locks', ['locked'], ['`name` = ? AND `expires` >= ?', $key, DateTimeFormat::utcNow()]);

		if ($this->dba->isResult($lock)) {
			return $lock['locked'] !== false;
		} else {
			return false;
		}
	}
}
