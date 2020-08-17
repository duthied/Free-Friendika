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

namespace Friendica\Module\Settings;

use Friendica\App;
use Friendica\Core\Hook;
use Friendica\Core\Renderer;
use Friendica\Database\DBA;
use Friendica\Database\DBStructure;
use Friendica\DI;
use Friendica\Module\BaseSettings;

/**
 * Module to export user data
 **/
class UserExport extends BaseSettings
{
	/**
	 * Handle the request to export data.
	 * At the moment one can export three different data set
	 * 1. The profile data that can be used by uimport to resettle
	 *    to a different Friendica instance
	 * 2. The entire data-set, profile plus postings
	 * 3. A list of contacts as CSV file similar to the export of Mastodon
	 *
	 * If there is an action required through the URL / path, react
	 * accordingly and export the requested data.
	 **/
	public static function content(array $parameters = [])
	{
		parent::content($parameters);

		/**
		 * options shown on "Export personal data" page
		 * list of array( 'link url', 'link text', 'help text' )
		 */
		$options = [
			['settings/userexport/account', DI::l10n()->t('Export account'), DI::l10n()->t('Export your account info and contacts. Use this to make a backup of your account and/or to move it to another server.')],
			['settings/userexport/backup', DI::l10n()->t('Export all'), DI::l10n()->t("Export your account info, contacts and all your items as json. Could be a very big file, and could take a lot of time. Use this to make a full backup of your account \x28photos are not exported\x29")],
			['settings/userexport/contact', DI::l10n()->t('Export Contacts to CSV'), DI::l10n()->t("Export the list of the accounts you are following as CSV file. Compatible to e.g. Mastodon.")],
		];
		Hook::callAll('uexport_options', $options);

		$tpl = Renderer::getMarkupTemplate("settings/userexport.tpl");
		return Renderer::replaceMacros($tpl, [
			'$title' => DI::l10n()->t('Export personal data'),
			'$options' => $options
		]);
	}
	/**
	 * raw content generated for the different choices made
	 * by the user. At the moment this returns a JSON file
	 * to the browser which then offers a save / open dialog
	 * to the user.
	 **/
	public static function rawContent(array $parameters = [])
	{
		$args = DI::args();
		if ($args->getArgc() == 3) {
			// @TODO Replace with router-provided arguments
			$action = $args->get(2);
			$user = DI::app()->user;
			switch ($action) {
				case "backup":
					header("Content-type: application/json");
					header('Content-Disposition: attachment; filename="' . $user['nickname'] . '.' . $action . '"');
					self::exportAll(DI::app());
					exit();
					break;
				case "account":
					header("Content-type: application/json");
					header('Content-Disposition: attachment; filename="' . $user['nickname'] . '.' . $action . '"');
					self::exportAccount(DI::app());
					exit();
					break;
				case "contact":
					header("Content-type: application/csv");
					header('Content-Disposition: attachment; filename="' . $user['nickname'] . '-contacts.csv'. '"');
					self::exportContactsAsCSV();
					exit();
					break;
				default:
					exit();
			}
		}
	}
	private static function exportMultiRow(string $query)
	{
		$dbStructure = DBStructure::definition(DI::app()->getBasePath(), false);

		preg_match("/\s+from\s+`?([a-z\d_]+)`?/i", $query, $match);
		$table = $match[1];

		$result = [];
		$rows = DBA::p($query);
		while ($row = DBA::fetch($rows)) {
			$p = [];
			foreach ($row as $k => $v) {
				switch ($dbStructure[$table]['fields'][$k]['type']) {
					case 'datetime':
						$p[$k] = $v ?? DBA::NULL_DATETIME;
						break;
					default:
						$p[$k] = $v;
						break;
				}
			}
			$result[] = $p;
		}
		DBA::close($rows);
		return $result;
	}

	private static function exportRow(string $query)
	{
		$dbStructure = DBStructure::definition(DI::app()->getBasePath(), false);

		preg_match("/\s+from\s+`?([a-z\d_]+)`?/i", $query, $match);
		$table = $match[1];

		$result = [];
		$r = q($query);
		if (DBA::isResult($r)) {

			foreach ($r as $rr) {
				foreach ($rr as $k => $v) {
					switch ($dbStructure[$table]['fields'][$k]['type']) {
						case 'datetime':
							$result[$k] = $v ?? DBA::NULL_DATETIME;
							break;
						default:
							$result[$k] = $v;
							break;
					}
				}
			}
		}
		return $result;
	}

	/**
	 * Export a list of the contacts as CSV file as e.g. Mastodon and Pleroma are doing.
	 **/
	private static function exportContactsAsCSV()
	{
		// write the table header (like Mastodon)
		echo "Account address, Show boosts\n";
		// get all the contacts
		$contacts = DBA::select('contact', ['addr'], ['uid' => $_SESSION['uid'], 'self' => false, 'rel' => [1,3], 'deleted' => false]);
		while ($contact = DBA::fetch($contacts)) {
			echo $contact['addr'] . ", true\n";
		}
		DBA::close($contacts);
	}
	private static function exportAccount(App $a)
	{
		$user = self::exportRow(
			sprintf("SELECT * FROM `user` WHERE `uid` = %d LIMIT 1", intval(local_user()))
		);

		$contact = self::exportMultiRow(
			sprintf("SELECT * FROM `contact` WHERE `uid` = %d ", intval(local_user()))
		);


		$profile = self::exportMultiRow(
			sprintf("SELECT *, 'default' AS `profile_name`, 1 AS `is-default` FROM `profile` WHERE `uid` = %d ", intval(local_user()))
		);

		$profile_fields = self::exportMultiRow(
			sprintf("SELECT * FROM `profile_field` WHERE `uid` = %d ", intval(local_user()))
		);

		$photo = self::exportMultiRow(
			sprintf("SELECT * FROM `photo` WHERE uid = %d AND profile = 1", intval(local_user()))
		);
		foreach ($photo as &$p) {
			$p['data'] = bin2hex($p['data']);
		}

		$pconfig = self::exportMultiRow(
			sprintf("SELECT * FROM `pconfig` WHERE uid = %d", intval(local_user()))
		);

		$group = self::exportMultiRow(
			sprintf("SELECT * FROM `group` WHERE uid = %d", intval(local_user()))
		);

		$group_member = self::exportMultiRow(
			sprintf("SELECT `group_member`.`gid`, `group_member`.`contact-id` FROM `group_member` INNER JOIN `group` ON `group`.`id` = `group_member`.`gid` WHERE `group`.`uid` = %d", intval(local_user()))
		);

		$output = [
			'version' => FRIENDICA_VERSION,
			'schema' => DB_UPDATE_VERSION,
			'baseurl' => DI::baseUrl(),
			'user' => $user,
			'contact' => $contact,
			'profile' => $profile,
			'profile_fields' => $profile_fields,
			'photo' => $photo,
			'pconfig' => $pconfig,
			'group' => $group,
			'group_member' => $group_member,
		];

		echo json_encode($output, JSON_PARTIAL_OUTPUT_ON_ERROR);
	}

	/**
	 * echoes account data and items as separated json, one per line
	 *
	 * @param App $a
	 * @throws \Exception
	 */
	private static function exportAll(App $a)
	{
		self::exportAccount($a);
		echo "\n";

		$total = DBA::count('item', ['uid' => local_user()]);
		// chunk the output to avoid exhausting memory

		for ($x = 0; $x < $total; $x += 500) {
			$r = q("SELECT * FROM `item` WHERE `uid` = %d LIMIT %d, %d",
				intval(local_user()),
				intval($x),
				intval(500)
			);

			$output = ['item' => $r];
			echo json_encode($output, JSON_PARTIAL_OUTPUT_ON_ERROR). "\n";
		}
	}
}
