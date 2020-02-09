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

namespace Friendica\Core;

use Friendica\App;
use Friendica\Database\DBA;
use Friendica\Database\DBStructure;
use Friendica\DI;
use Friendica\Util\Strings;

class Update
{
	const SUCCESS = 0;
	const FAILED  = 1;

	/**
	 * Function to check if the Database structure needs an update.
	 *
	 * @param string   $basePath   The base path of this application
	 * @param boolean  $via_worker Is the check run via the worker?
	 * @param App\Mode $mode       The current app mode
	 *
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function check($basePath, $via_worker, App\Mode $mode)
	{
		if (!DBA::connected()) {
			return;
		}

		// Don't check the status if the last update was failed
		if (DI::config()->get('system', 'update', Update::SUCCESS, true) == Update::FAILED) {
			return;
		}

		$build = DI::config()->get('system', 'build');

		if (empty($build)) {
			DI::config()->set('system', 'build', DB_UPDATE_VERSION - 1);
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
			DI::lock()->release('dbupdate', true);
		}

		$build = DI::config()->get('system', 'build', null, true);

		if (empty($build) || ($build > DB_UPDATE_VERSION)) {
			$build = DB_UPDATE_VERSION - 1;
			DI::config()->set('system', 'build', $build);
		}

		if ($build != DB_UPDATE_VERSION || $force) {
			require_once 'update.php';

			$stored = intval($build);
			$current = intval(DB_UPDATE_VERSION);
			if ($stored < $current || $force) {
				DI::config()->load('database');

				Logger::info('Update starting.', ['from' => $stored, 'to' => $current]);

				// Compare the current structure with the defined structure
				// If the Lock is acquired, never release it automatically to avoid double updates
				if (DI::lock()->acquire('dbupdate', 120, Cache\Duration::INFINITE)) {

					// Checks if the build changed during Lock acquiring (so no double update occurs)
					$retryBuild = DI::config()->get('system', 'build', null, true);
					if ($retryBuild !== $build) {
						Logger::info('Update already done.', ['from' => $stored, 'to' => $current]);
						DI::lock()->release('dbupdate');
						return '';
					}

					// run the pre_update_nnnn functions in update.php
					for ($x = $stored + 1; $x <= $current; $x++) {
						$r = self::runUpdateFunction($x, 'pre_update');
						if (!$r) {
							DI::config()->set('system', 'update', Update::FAILED);
							DI::lock()->release('dbupdate');
							return $r;
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
						Logger::error('Update ERROR.', ['from' => $stored, 'to' => $current, 'retval' => $retval]);
						DI::config()->set('system', 'update', Update::FAILED);
						DI::lock()->release('dbupdate');
						return $retval;
					} else {
						DI::config()->set('database', 'last_successful_update', $current);
						DI::config()->set('database', 'last_successful_update_time', time());
						Logger::info('Update finished.', ['from' => $stored, 'to' => $current]);
					}

					// run the update_nnnn functions in update.php
					for ($x = $stored + 1; $x <= $current; $x++) {
						$r = self::runUpdateFunction($x, 'update');
						if (!$r) {
							DI::config()->set('system', 'update', Update::FAILED);
							DI::lock()->release('dbupdate');
							return $r;
						}
					}

					Logger::notice('Update success.', ['from' => $stored, 'to' => $current]);
					if ($sendMail) {
						self::updateSuccessfull($stored, $current);
					}

					DI::config()->set('system', 'update', Update::SUCCESS);
					DI::lock()->release('dbupdate');
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

		Logger::info('Update function start.', ['function' => $funcname]);

		if (function_exists($funcname)) {
			// There could be a lot of processes running or about to run.
			// We want exactly one process to run the update command.
			// So store the fact that we're taking responsibility
			// after first checking to see if somebody else already has.
			// If the update fails or times-out completely you may need to
			// delete the config entry to try again.

			if (DI::lock()->acquire('dbupdate_function', 120, Cache\Duration::INFINITE)) {

				// call the specific update
				$retval = $funcname();

				if ($retval) {
					//send the administrator an e-mail
					self::updateFailed(
						$x,
						DI::l10n()->t('Update %s failed. See error logs.', $x)
					);
					Logger::error('Update function ERROR.', ['function' => $funcname, 'retval' => $retval]);
					DI::lock()->release('dbupdate_function');
					return false;
				} else {
					DI::config()->set('database', 'last_successful_update_function', $funcname);
					DI::config()->set('database', 'last_successful_update_function_time', time());

					if ($prefix == 'update') {
						DI::config()->set('system', 'build', $x);
					}

					DI::lock()->release('dbupdate_function');
					Logger::info('Update function finished.', ['function' => $funcname]);
					return true;
				}
			}
		} else {
			Logger::info('Update function skipped.', ['function' => $funcname]);

			DI::config()->set('database', 'last_successful_update_function', $funcname);
			DI::config()->set('database', 'last_successful_update_function_time', time());

			if ($prefix == 'update') {
				DI::config()->set('system', 'build', $x);
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
		$condition = ['email' => explode(",", str_replace(" ", "", DI::config()->get('config', 'admin_email'))), 'parent-uid' => 0];
		$adminlist = DBA::select('user', ['uid', 'language', 'email'], $condition, ['order' => ['uid']]);

		// No valid result?
		if (!DBA::isResult($adminlist)) {
			Logger::warning('Cannot notify administrators .', ['update' => $update_id, 'message' => $error_message]);

			// Don't continue
			return;
		}

		$sent = [];

		// every admin could had different language
		while ($admin = DBA::fetch($adminlist)) {
			if (in_array($admin['email'], $sent)) {
				continue;
			}
			$sent[] = $admin['email'];

			$lang = $admin['language'] ?? 'en';
			$l10n = DI::l10n()->withLang($lang);

			$preamble = Strings::deindent($l10n->t("
				The friendica developers released update %s recently,
				but when I tried to install it, something went terribly wrong.
				This needs to be fixed soon and I can't do it alone. Please contact a
				friendica developer if you can not help me on your own. My database might be invalid.",
				$update_id));
			$body     = $l10n->t("The error message is\n[pre]%s[/pre]", $error_message);

			$email = DI::emailer()
				->newSystemMail()
				->withMessage($l10n->t('[Friendica Notify] Database update'), $preamble, $body)
				->forUser($admin)
				->withRecipient($admin['email'])
				->build();
			DI::emailer()->send($email);
		}

		//try the logger
		Logger::alert('Database structure update FAILED.', ['error' => $error_message]);
	}

	private static function updateSuccessfull($from_build, $to_build)
	{
		//send the administrators an e-mail
		$condition = ['email' => explode(",", str_replace(" ", "", DI::config()->get('config', 'admin_email'))), 'parent-uid' => 0];
		$adminlist = DBA::select('user', ['uid', 'language', 'email'], $condition, ['order' => ['uid']]);

		if (DBA::isResult($adminlist)) {
			$sent = [];

			// every admin could had different language
			while ($admin = DBA::fetch($adminlist)) {
				if (in_array($admin['email'], $sent)) {
					continue;
				}
				$sent[] = $admin['email'];

				$lang = (($admin['language']) ? $admin['language'] : 'en');
				$l10n = DI::l10n()->withLang($lang);

				$preamble = Strings::deindent($l10n->t("
					The friendica database was successfully updated from %s to %s.",
					$from_build, $to_build));

				$email = DI::emailer()
					->newSystemMail()
					->withMessage($l10n->t('[Friendica Notify] Database update'), $preamble)
					->forUser($admin)
					->withRecipient($admin['email'])
					->build();
				DI::emailer()->send($email);
			}
		}

		//try the logger
		Logger::debug('Database structure update successful.');
	}
}
