<?php
/**
 * @file src/Worker/Directory.php
 * Sends updated profile data to the directory
 */

namespace Friendica\Worker;

use Friendica\Core\Config;
use Friendica\Core\Hook;
use Friendica\Core\Logger;
use Friendica\Core\Worker;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Util\Network;

class Directory
{
	public static function execute($url = '')
	{
		$dir = DI::config()->get('system', 'directory');

		if (!strlen($dir)) {
			return;
		}

		if ($url == '') {
			self::updateAll();
			return;
		}

		$dir .= "/submit";

		$arr = ['url' => $url];

		Hook::callAll('globaldir_update', $arr);

		Logger::log('Updating directory: ' . $arr['url'], Logger::DEBUG);
		if (strlen($arr['url'])) {
			Network::fetchUrl($dir . '?url=' . bin2hex($arr['url']));
		}

		return;
	}

	private static function updateAll() {
		$r = q("SELECT `url` FROM `contact`
			INNER JOIN `profile` ON `profile`.`uid` = `contact`.`uid`
			INNER JOIN `user` ON `user`.`uid` = `contact`.`uid`
				WHERE `contact`.`self` AND `profile`.`net-publish` AND `profile`.`is-default` AND
					NOT `user`.`account_expired` AND `user`.`verified`");

		if (DBA::isResult($r)) {
			foreach ($r AS $user) {
				Worker::add(PRIORITY_LOW, 'Directory', $user['url']);
			}
		}
	}
}
