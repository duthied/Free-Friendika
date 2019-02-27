<?php

namespace Friendica\Core;

use Friendica\Database\DBA;
use Friendica\Database\DBStructure;
use Friendica\Util\Strings;

class Update
{
	const SUCCESS = 0;
	const FAILED  = 1;

	/**
	 * @brief Function to check if the Database structure needs an update.
	 *
	 * @param string $basePath The base path of this application
	 * @param boolean $via_worker boolean Is the check run via the worker?
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function check($basePath, $via_worker)
	{
		if (!DBA::connected()) {
			return;
		}

		$build = Config::get('system', 'build');

		if (empty($build)) {
			Config::set('system', 'build', DB_UPDATE_VERSION - 1);
			$build = DB_UPDATE_VERSION - 1;
		}

		// We don't support upgrading from very old versions anymore
		if ($build < NEW_UPDATE_ROUTINE_VERSION) {
			die('You try to update from a version prior to database version 1170. The direct upgrade path is not supported. Please update to version 3.5.4 before updating to this version.');
		}

		if ($build < DB_UPDATE_VERSION) {
			if ($via_worker) {
				// Calling the database update directly via the worker enables us to perform database changes to the workerqueue table itself.
				// This is a fallback, since normally the database update will be performed by a worker job.
				// This worker job doesn't work for changes to the "workerqueue" table itself.
				self::run($basePath);
			} else {
				Worker::add(PRIORITY_CRITICAL, 'DBUpdate');
			}
		}
	}

	/**
	 * Automatic database updates
	 *
	 * @param string $basePath The base path of this application
	 * @param bool $force      Force the Update-Check even if the database version doesn't match
	 * @param bool $override   Overrides any running/stuck updates
	 * @param bool $verbose    Run the Update-Check verbose
	 * @param bool $sendMail   Sends a Mail to the administrator in case of success/failure
	 *
	 * @return string Empty string if the update is successful, error messages otherwise
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function run($basePath, $force = false, $override = false, $verbose = false, $sendMail = true)
	{
		// In force mode, we release the dbupdate lock first
		// Necessary in case of an stuck update
		if ($override) {
			Lock::release('dbupdate', true);
		}

		$build = Config::get('system', 'build');

		if (empty($build) || ($build > DB_UPDATE_VERSION)) {
			$build = DB_UPDATE_VERSION - 1;
			Config::set('system', 'build', $build);
		}

		if ($build != DB_UPDATE_VERSION || $force) {
			require_once 'update.php';

			$stored = intval($build);
			$current = intval(DB_UPDATE_VERSION);
			if ($stored < $current || $force) {
				Config::load('database');

				Logger::log('Update from \'' . $stored . '\'  to \'' . $current . '\' - starting', Logger::DEBUG);

				// Compare the current structure with the defined structure
				// If the Lock is acquired, never release it automatically to avoid double updates
				if (Lock::acquire('dbupdate', 120, Cache::INFINITE)) {

					// run the pre_update_nnnn functions in update.php
					for ($x = $stored + 1; $x <= $current; $x++) {
						$r = self::runUpdateFunction($x, 'pre_update');
						if (!$r) {
							break;
						}
					}

					// update the structure in one call
					$retval = DBStructure::update($basePath, $verbose, true);
					if (!empty($retval)) {
						if ($sendMail) {
							self::updateFailed(
								DB_UPDATE_VERSION,
								$retval
							);
						}
						Logger::log('ERROR: Update from \'' . $stored . '\'  to \'' . $current . '\' - failed:  ' - $retval, Logger::ALL);
						Lock::release('dbupdate');
						return $retval;
					} else {
						Config::set('database', 'last_successful_update', $current);
						Config::set('database', 'last_successful_update_time', time());
						Logger::log('Update from \'' . $stored . '\'  to \'' . $current . '\' - finished', Logger::DEBUG);
					}

					// run the update_nnnn functions in update.php
					for ($x = $stored + 1; $x <= $current; $x++) {
						$r = self::runUpdateFunction($x, 'update');
						if (!$r) {
							break;
						}
					}

					Logger::log('Update from \'' . $stored . '\'  to \'' . $current . '\' - successful', Logger::DEBUG);
					if ($sendMail) {
						self::updateSuccessfull($stored, $current);
					}

					Lock::release('dbupdate');
				}
			}
		}

		return '';
	}

	/**
	 * Executes a specific update function
	 *
	 * @param int    $x      the DB version number of the function
	 * @param string $prefix the prefix of the function (update, pre_update)
	 *
	 * @return bool true, if the update function worked
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function runUpdateFunction($x, $prefix)
	{
		$funcname = $prefix . '_' . $x;

		Logger::log('Update function \'' . $funcname . '\' - start', Logger::DEBUG);

		if (function_exists($funcname)) {
			// There could be a lot of processes running or about to run.
			// We want exactly one process to run the update command.
			// So store the fact that we're taking responsibility
			// after first checking to see if somebody else already has.
			// If the update fails or times-out completely you may need to
			// delete the config entry to try again.

			if (Lock::acquire('dbupdate_function', 120,Cache::INFINITE)) {

				// call the specific update
				$retval = $funcname();

				if ($retval) {
					//send the administrator an e-mail
					self::updateFailed(
						$x,
						L10n::t('Update %s failed. See error logs.', $x)
					);
					Logger::log('ERROR: Update function \'' . $funcname . '\' - failed: ' . $retval, Logger::ALL);
					Lock::release('dbupdate_function');
					return false;
				} else {
					Config::set('database', 'last_successful_update_function', $funcname);
					Config::set('database', 'last_successful_update_function_time', time());

					if ($prefix == 'update') {
						Config::set('system', 'build', $x);
					}

					Lock::release('dbupdate_function');
					Logger::log('Update function \'' . $funcname . '\' - finished', Logger::DEBUG);
					return true;
				}
			}
		} else {
			 Logger::log('Skipping \'' . $funcname . '\' without executing', Logger::DEBUG);

			Config::set('database', 'last_successful_update_function', $funcname);
			Config::set('database', 'last_successful_update_function_time', time());

			if ($prefix == 'update') {
				Config::set('system', 'build', $x);
			}

			return true;
		}
	}

	/**
	 * send the email and do what is needed to do on update fails
	 *
	 * @param int    $update_id     number of failed update
	 * @param string $error_message error message
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	private static function updateFailed($update_id, $error_message) {
		//send the administrators an e-mail
		$adminlist = DBA::select('user', ['uid', 'language', 'email'], ['email' => explode(",", str_replace(" ", "", Config::get('config', 'admin_email')))]);

		// No valid result?
		if (!DBA::isResult($adminlist)) {
			Logger::log(sprintf('Cannot notify administrators about update_id=%d, error_message=%s', $update_id, $error_message), Logger::INFO);

			// Don't continue
			return;
		}

		// every admin could had different language
		foreach ($adminlist as $admin) {
			$lang = (($admin['language'])?$admin['language']:'en');
			L10n::pushLang($lang);

			$preamble = Strings::deindent(L10n::t("
				The friendica developers released update %s recently,
				but when I tried to install it, something went terribly wrong.
				This needs to be fixed soon and I can't do it alone. Please contact a
				friendica developer if you can not help me on your own. My database might be invalid.",
				$update_id));
			$body = L10n::t("The error message is\n[pre]%s[/pre]", $error_message);

			notification([
					'uid'      => $admin['uid'],
					'type'     => SYSTEM_EMAIL,
					'to_email' => $admin['email'],
					'preamble' => $preamble,
					'body'     => $body,
					'language' => $lang]
			);
			L10n::popLang();
		}

		//try the logger
		Logger::log("CRITICAL: Database structure update failed: " . $error_message);
	}

	private static function updateSuccessfull($from_build, $to_build)
	{
		//send the administrators an e-mail
		$adminlist = DBA::select('user', ['uid', 'language', 'email'], ['email' => explode(",", str_replace(" ", "", Config::get('config', 'admin_email')))]);

		if (DBA::isResult($adminlist)) {
			// every admin could had different language
			foreach ($adminlist as $admin) {
				$lang = (($admin['language']) ? $admin['language'] : 'en');
				L10n::pushLang($lang);

				$preamble = Strings::deindent(L10n::t("
					The friendica database was successfully updated from %s to %s.",
					$from_build, $to_build));

				notification([
						'uid' => $admin['uid'],
						'type' => SYSTEM_EMAIL,
						'to_email' => $admin['email'],
						'preamble' => $preamble,
						'body' => $preamble,
						'language' => $lang]
				);
				L10n::popLang();
			}
		}

		//try the logger
		Logger::log("Database structure update successful.", Logger::TRACE);
	}
}
