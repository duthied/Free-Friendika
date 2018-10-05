<?php

namespace Friendica\Core;

use Friendica\Database\DBStructure;

class Update
{
	/**
	 * Automatic database updates
	 */
	public static function run()
	{
		$build = Config::get('system', 'build');

		if (empty($build) || ($build > DB_UPDATE_VERSION)) {
			$build = DB_UPDATE_VERSION - 1;
			Config::set('system', 'build', $build);
		}

		if ($build != DB_UPDATE_VERSION) {
			require_once 'update.php';

			$stored = intval($build);
			$current = intval(DB_UPDATE_VERSION);
			if ($stored < $current) {
				Config::load('database');

				// Compare the current structure with the defined structure
				$t = Config::get('database', 'dbupdate_' . DB_UPDATE_VERSION);
				if (!is_null($t)) {
					return;
				}

				// run the pre_update_nnnn functions in update.php
				for ($x = $stored + 1; $x <= $current; $x++) {
					$r = self::runUpdateFunction($x, 'pre_update');
					if (!$r) {
						break;
					}
				}

				Config::set('database', 'dbupdate_' . DB_UPDATE_VERSION, time());

				// update the structure in one call
				$retval = DBStructure::update(false, true);
				if ($retval) {
					DBStructure::updateFail(
						DB_UPDATE_VERSION,
						$retval
					);
					return;
				} else {
					Config::set('database', 'dbupdate_' . DB_UPDATE_VERSION, 'success');
				}

				// run the update_nnnn functions in update.php
				for ($x = $stored + 1; $x <= $current; $x++) {
					$r = self::runUpdateFunction($x, 'update');
					if (!$r) {
						break;
					}
				}
			}
		}
	}

	/**
	 * Executes a specific update function
	 *
	 * @param int $x the DB version number of the function
	 * @param string $prefix the prefix of the function (update, pre_update)
	 *
	 * @return bool true, if the update function worked
	 */
	public static function runUpdateFunction($x, $prefix)
	{
		$funcname = $prefix . '_' . $x;

		if (function_exists($funcname)) {
			// There could be a lot of processes running or about to run.
			// We want exactly one process to run the update command.
			// So store the fact that we're taking responsibility
			// after first checking to see if somebody else already has.
			// If the update fails or times-out completely you may need to
			// delete the config entry to try again.

			$t = Config::get('database', $funcname);
			if (!is_null($t)) {
				return false;
			}
			Config::set('database', $funcname, time());

			// call the specific update
			$retval = $funcname();

			if ($retval) {
				//send the administrator an e-mail
				DBStructure::updateFail(
					$x,
					L10n::t('Update %s failed. See error logs.', $x)
				);
				return false;
			} else {
				Config::set('database', $funcname, 'success');

				if ($prefix == 'update') {
					Config::set('system', 'build', $x);
				}

				return true;
			}
		} else {
			Config::set('database', $funcname, 'success');

			if ($prefix == 'update') {
				Config::set('system', 'build', $x);
			}

			return true;
		}
	}
}