<?php
/**
 * @copyright Copyright (C) 2010-2021, the Friendica project
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
use Friendica\Model\Contact;
use Friendica\Model\User;

/**
 * Endpoint for getting current user infos
 *
 * @see Contact::updateFromNoScrape() for usage
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
			$which = $a->user['nickname'];
		} else {
			System::jsonError(403, 'Authentication required');
		}

		$profile = User::getOwnerDataByNick($which);

		if (empty($profile['uid'])) {
			System::jsonError(404, 'Profile not found');
		}

		$json_info = [
			'addr'         => $profile['addr'],
			'nick'         => $which,
			'guid'         => $profile['guid'],
			'key'          => $profile['upubkey'],
			'homepage'     => DI::baseUrl() . "/profile/{$which}",
			'comm'         => ($profile['account-type'] == User::ACCOUNT_TYPE_COMMUNITY),
			'account-type' => $profile['account-type'],
		];

		$dfrn_pages = ['request', 'confirm', 'notify', 'poll'];
		foreach ($dfrn_pages as $dfrn) {
			$json_info["dfrn-{$dfrn}"] = DI::baseUrl() . "/dfrn_{$dfrn}/{$which}";
		}

		if (!$profile['net-publish']) {
			$json_info['hide'] = true;
			System::jsonExit($json_info);
		}

		$keywords = $profile['pub_keywords'] ?? '';
		$keywords = str_replace(['#', ',', ' ', ',,'], ['', ' ', ',', ','], $keywords);
		$keywords = explode(',', $keywords);

		$json_info['fn']       = $profile['name'];
		$json_info['photo']    = Contact::getAvatarUrlForUrl($profile['url'], $profile['uid']);
		$json_info['tags']     = $keywords;
		$json_info['language'] = $profile['language'];

		if (!empty($profile['last-item'])) {
			$json_info['updated'] = date("c", strtotime($profile['last-item']));
		}

		if (!($profile['hide-friends'] ?? false)) {
			$json_info['contacts'] = DBA::count('contact',
				[
					'uid'     => $profile['uid'],
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
		$condition   = ['uid' => $profile['uid'], 'self' => true];
		$contact     = DBA::selectFirst('contact', ['last-item'], $condition);
		if (DBA::isResult($contact)) {
			$last_active = strtotime($contact['last-item']);
		}

		$condition = ['uid' => $profile['uid']];
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
			if (!empty($profile[$field])) {
				$json_info["$field"] = $profile[$field];
			}
		}

		System::jsonExit($json_info);
	}
}
