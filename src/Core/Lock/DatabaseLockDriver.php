<?php

namespace Friendica\Core\Lock;

use dba;
use Friendica\Database\DBM;

/**
 * Locking driver that stores the locks in the database
 */
class DatabaseLockDriver implements ILockDriver
{
	/**
	 * @brief Sets a lock for a given name
	 *
	 * @param string  $key      The Name of the lock
	 * @param integer $timeout  Seconds until we give up
	 *
	 * @return boolean Was the lock successful?
	 */
	public function acquireLock($key, $timeout = 120)
	{
		$got_lock = false;
		$start = time();

		do {
			dba::lock('locks');
			$lock = dba::selectFirst('locks', ['locked', 'pid'], ['name' => $key]);

			if (DBM::is_result($lock)) {
				if ($lock['locked']) {
					// When the process id isn't used anymore, we can safely claim the lock for us.
					if (!posix_kill($lock['pid'], 0)) {
						$lock['locked'] = false;
					}
					// We want to lock something that was already locked by us? So we got the lock.
					if ($lock['pid'] == getmypid()) {
						$got_lock = true;
					}
				}
				if (!$lock['locked']) {
					dba::update('locks', ['locked' => true, 'pid' => getmypid()], ['name' => $key]);
					$got_lock = true;
				}
			} elseif (!DBM::is_result($lock)) {
				dba::insert('locks', ['name' => $key, 'locked' => true, 'pid' => getmypid()]);
				$got_lock = true;
			}

			dba::unlock();

			if (!$got_lock && ($timeout > 0)) {
				usleep(rand(100000, 2000000));
			}
		} while (!$got_lock && ((time() - $start) < $timeout));

		return $got_lock;
	}

	/**
	 * @brief Removes a lock if it was set by us
	 *
	 * @param string $key Name of the lock
	 *
	 * @return mixed
	 */
	public function releaseLock($key)
	{
		dba::update('locks', ['locked' => false, 'pid' => 0], ['name' => $key, 'pid' => getmypid()]);

		return;
	}

	/**
	 * @brief Removes all lock that were set by us
	 *
	 * @return void
	 */
	public function releaseAll()
	{
		dba::update('locks', ['locked' => false, 'pid' => 0], ['pid' => getmypid()]);
	}
}
