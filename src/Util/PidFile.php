<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

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
	 * @return int|false PID or "false" if nonexistent or invalid
	 */
	private static function pidFromFile(string $file): int|false
	{
		if (!file_exists($file)) {
			return false;
		}

		$pid = trim((string) @file_get_contents($file));

		if (!ctype_digit($pid)) {
			return false;
		}

		return (int) $pid;
	}

	/**
	 * Is there a running process with the given pid file
	 *
	 * @param string $file Filename of pid file
	 *
	 * @return boolean Is it running?
	 */
	public static function isRunningProcess(string $file): bool
	{
		$pid = self::pidFromFile($file);

		if ($pid === false || $pid === 0) {
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
	public static function killProcess(string $file): bool
	{
		$pid = self::pidFromFile($file);

		// We don't have a process id? then we quit
		if ($pid === false || $pid === 0) {
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
	 * @return int|false PID or "false" if not created
	 */
	public static function create(string $file)
	{
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
	public static function delete(string $file): bool
	{
		return @unlink($file);
	}
}
