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

namespace Friendica\Module;

use Friendica\BaseModule;
use Friendica\Core\Protocol;
use Friendica\Core\System;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\GContact;
use Friendica\Model\Profile;
use Friendica\Model\User;

/**
 * Endpoint for getting current user infos
 *
 * @see GContact::updateFromNoScrape() for usage
 */
class NoScrape extends BaseModule
{
	public static function rawContent(array $parameters = [])
	{
		$a = DI::app();

		if (isset($parameters['nick'])) {
			// Get infos about a specific nick (public)
			$which = $parameters['nick'];
		} elseif (local_user() && isset($parameters['profile']) && DI::args()->get(2) == 'view') {
			// view infos about a known profile (needs a login)
			$which   = $a->user['nickname'];
		} else {
			System::jsonError(403, 'Authentication required');
			exit();
		}

		Profile::load($a, $which);

		$json_info = [
			'addr'         => $a->profile['addr'],
			'nick'         => $which,
			'guid'         => $a->profile['guid'],
			'key'          => $a->profile['pubkey'],
			'homepage'     => DI::baseUrl() . "/profile/{$which}",
			'comm'         => ($a->profile['account-type'] == User::ACCOUNT_TYPE_COMMUNITY),
			'account-type' => $a->profile['account-type'],
		];

		$dfrn_pages = ['request', 'confirm', 'notify', 'poll'];
		foreach ($dfrn_pages as $dfrn) {
			$json_info["dfrn-{$dfrn}"] = DI::baseUrl() . "/dfrn_{$dfrn}/{$which}";
		}

		if (!$a->profile['net-publish']) {
			$json_info['hide'] = true;
			System::jsonExit($json_info);
		}

		$keywords = $a->profile['pub_keywords'] ?? '';
		$keywords = str_replace(['#', ',', ' ', ',,'], ['', ' ', ',', ','], $keywords);
		$keywords = explode(',', $keywords);

		$contactPhoto = DBA::selectFirst('contact', ['photo'], ['self' => true, 'uid' => $a->profile['uid']]);

		$json_info['fn']       = $a->profile['name'];
		$json_info['photo']    = $contactPhoto["photo"];
		$json_info['tags']     = $keywords;
		$json_info['language'] = $a->profile['language'];

		if (!($a->profile['hide-friends'] ?? false)) {
			$stmt = DBA::p(
				"SELECT `gcontact`.`updated`
				FROM `contact`
				INNER JOIN `gcontact`
				WHERE `gcontact`.`nurl` = `contact`.`nurl`
				  AND `self`
				  AND `uid` = ?
				LIMIT 1",
				intval($a->profile['uid'])
			);
			if ($gcontact = DBA::fetch($stmt)) {
				$json_info["updated"] = date("c", strtotime($gcontact['updated']));
			}
			DBA::close($stmt);

			$json_info['contacts'] = DBA::count('contact',
				[
					'uid'     => $a->profile['uid'],
					'self'    => 0,
					'blocked' => 0,
					'pending' => 0,
					'hidden'  => 0,
					'archive' => 0,
					'network' => [Protocol::DFRN, Protocol::DIASPORA, Protocol::OSTATUS]
				]);
		}

		// We display the last activity (post or login), reduced to year and week number
		$last_active = 0;
		$condition   = ['uid' => $a->profile['uid'], 'self' => true];
		$contact     = DBA::selectFirst('contact', ['last-item'], $condition);
		if (DBA::isResult($contact)) {
			$last_active = strtotime($contact['last-item']);
		}

		$condition = ['uid' => $a->profile['uid']];
		$user      = DBA::selectFirst('user', ['login_date'], $condition);
		if (DBA::isResult($user)) {
			if ($last_active < strtotime($user['login_date'])) {
				$last_active = strtotime($user['login_date']);
			}
		}
		$json_info['last-activity'] = date('o-W', $last_active);

		//These are optional fields.
		$profile_fields = ['about', 'locality', 'region', 'postal-code', 'country-name'];
		foreach ($profile_fields as $field) {
			if (!empty($a->profile[$field])) {
				$json_info["$field"] = $a->profile[$field];
			}
		}

		System::jsonExit($json_info);
	}
}
