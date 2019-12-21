<?php
/**
 * @file src/Worker/UpdateServers.php
 */
namespace Friendica\Worker;

use Friendica\Core\Logger;
use Friendica\Core\Worker;
use Friendica\Database\DBA;
use Friendica\Model\GServer;

class UpdateServers
{
	/**
	 * @brief Updates the first 250 servers
	 *
	 */
	public static function execute()
	{
		$gservers = DBA::p("SELECT `url`, `created`, `last_failure`, `last_contact` FROM `gserver` ORDER BY rand()");
		if (!DBA::isResult($gservers)) {
			return;
		}

		$updated = 0;

		while ($gserver = DBA::fetch($gservers)) {
			if (!GServer::updateNeeded($gserver['created'], '', $gserver['last_failure'], $gserver['last_contact'])) {
				continue;
			}
			Logger::info('Update server status', ['server' => $gserver['url']]);

			Worker::add(PRIORITY_LOW, 'CheckServer', $gserver['url']);

			if (++$updated > 250) {
				return;
			}
		}
	}
}
