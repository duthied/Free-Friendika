<?php
/**
 * @file src/Util/Pidfile.php
 */
namespace Friendica\Util;

/**
 * Pidfile class
 */
class PidFile
{
	/**
	 * Read the pid from a given pid file
	 *
	 * @param string $file Filename of pid file
	 *
	 * @return boolean|string PID or "false" if not existent
	 */
	static private function pidFromFile($file) {
		if (!file_exists($file)) {
			return false;
		}

		return trim(@file_get_contents($file));
	}

	/**
	 * Is there a running process with the given pid file
	 *
	 * @param string $file Filename of pid file
	 *
	 * @return boolean Is it running?
	 */
	static public function isRunningProcess($file) {
		$pid = self::pidFromFile($file);

		if (!$pid) {
			return false;
		}

		// Is the process running?
		$running = posix_kill($pid, 0);

		// If not, then we will kill the stale file
		if (!$running) {
			self::delete($file);
		}
		return $running;
	}

	/**
	 * Kills a process from a given pid file
	 *
	 * @param string $file Filename of pid file
	 *
	 * @return boolean Was it killed successfully?
	 */
	static public function killProcess($file) {
		$pid = self::pidFromFile($file);

		// We don't have a process id? then we quit
		if (!$pid) {
			return false;
		}

		// We now kill the process
		$killed = posix_kill($pid, SIGTERM);

		// If we killed the process successfully, we can remove the pidfile
		if ($killed) {
			self::delete($file);
		}
		return $killed;
	}

	/**
	 * Creates a pid file
	 *
	 * @param string $file Filename of pid file
	 *
	 * @return boolean|string PID or "false" if not created
	 */
	static public function create($file) {
		$pid = self::pidFromFile($file);

		// We have a process id? then we quit
		if ($pid) {
			return false;
		}

		$pid = getmypid();
		file_put_contents($file, $pid);

		// Now we check if everything is okay
		return self::pidFromFile($file);
	}

	/**
	 * Deletes a given pid file
	 *
	 * @param string $file Filename of pid file
	 *
	 * @return boolean Is it running?
	 */
	static public function delete($file) {
		return @unlink($file);
	}
}
