<?php
/**
 * @file src/Worker/UpdateGContacts.php
 */
namespace Friendica\Worker;

use Friendica\Core\Config;
use Friendica\Core\Logger;
use Friendica\Core\Protocol;
use Friendica\Core\Worker;
use Friendica\Database\DBA;
use Friendica\Model\Contact;
use Friendica\Model\GServer;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Strings;

class UpdateGContacts
{
	// Updates gcontact entries
	public static function execute()
	{
		if (!Config::get('system', 'poco_completion')) {
			return;
		}

		Logger::info('Discover contacts');

		$starttime = time();

		$contacts = DBA::p("SELECT `url`, `created`, `updated`, `last_failure`, `last_contact`, `server_url`, `network` FROM `gcontact`
				WHERE `last_contact` < UTC_TIMESTAMP - INTERVAL 1 MONTH AND
					`last_failure` < UTC_TIMESTAMP - INTERVAL 1 MONTH AND
					`network` IN (?, ?, ?, ?, '') ORDER BY rand()",
				Protocol::DFRN, Protocol::DIASPORA, Protocol::OSTATUS, Protocol::FEED);

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

			$server_url = Contact::getBasepath($contact['url']);
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
