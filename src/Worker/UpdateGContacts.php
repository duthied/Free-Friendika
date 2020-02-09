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

namespace Friendica\Worker;

use Friendica\Core\Logger;
use Friendica\Core\Protocol;
use Friendica\Core\Worker;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\GContact;
use Friendica\Model\GServer;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Strings;

class UpdateGContacts
{
	/**
	 * Updates global contacts
	 */
	public static function execute()
	{
		if (!DI::config()->get('system', 'poco_completion')) {
			return;
		}

		Logger::info('Update global contacts');

		$starttime = time();

		$contacts = DBA::p("SELECT `url`, `created`, `updated`, `last_failure`, `last_contact`, `server_url`, `network` FROM `gcontact`
				WHERE `last_contact` < UTC_TIMESTAMP - INTERVAL 1 MONTH AND
					`last_failure` < UTC_TIMESTAMP - INTERVAL 1 MONTH AND
					`network` IN (?, ?, ?, ?, ?, '') ORDER BY rand()",
				Protocol::ACTIVITYPUB, Protocol::DFRN, Protocol::DIASPORA, Protocol::OSTATUS, Protocol::FEED);

		$checked = 0;

		while ($contact = DBA::fetch($contacts)) {
			$urlparts = parse_url($contact['url']);
			if (empty($urlparts['scheme'])) {
				DBA::update('gcontact', ['network' => Protocol::PHANTOM],
					['nurl' => Strings::normaliseLink($contact['url'])]);
				continue;
			 }

			if (in_array($urlparts['host'], ['twitter.com', 'identi.ca'])) {
				$networks = ['twitter.com' => Protocol::TWITTER, 'identi.ca' => Protocol::PUMPIO];

				DBA::update('gcontact', ['network' => $networks[$urlparts['host']]],
					['nurl' => Strings::normaliseLink($contact['url'])]);
				continue;
			}

			$server_url = GContact::getBasepath($contact['url'], true);
			$force_update = false;

			if (!empty($contact['server_url'])) {
				$force_update = (Strings::normaliseLink($contact['server_url']) != Strings::normaliseLink($server_url));

				$server_url = $contact['server_url'];
			}

			if ((empty($server_url) && ($contact['network'] == Protocol::FEED)) || $force_update || GServer::check($server_url, $contact['network'])) {
				Logger::info('Check profile', ['profile' => $contact['url']]);
				Worker::add(PRIORITY_LOW, 'UpdateGContact', $contact['url'], 'force');

				if (++$checked > 100) {
					return;
				}
			} else {
				DBA::update('gcontact', ['last_failure' => DateTimeFormat::utcNow()],
					['nurl' => Strings::normaliseLink($contact['url'])]);
			}

			// Quit the loop after 3 minutes
			if (time() > ($starttime + 180)) {
				return;
			}
		}
	}
}
