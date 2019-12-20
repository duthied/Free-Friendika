<?php
/**
 * @file src/Worker/DiscoverPoCo.php
 */
namespace Friendica\Worker;

use Friendica\Core\Cache;
use Friendica\Core\Config;
use Friendica\Core\Logger;
use Friendica\Core\Protocol;
use Friendica\Core\Worker;
use Friendica\Database\DBA;
use Friendica\Model\GContact;
use Friendica\Model\Contact;
use Friendica\Model\GServer;
use Friendica\Network\Probe;
use Friendica\Protocol\PortableContact;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Network;
use Friendica\Util\Strings;

class DiscoverPoCo
{
	/// @todo Clean up this mess of a parameter hell and split it in several classes
	public static function execute($command = '', $param1 = '', $param2 = '', $param3 = '', $param4 = '')
	{
		/*
		This function can be called in these ways:
		- checkcontact: Updates gcontact entries
		- server <poco url>: Searches for the poco server list. "poco url" is base64 encoded.
		- PortableContact::load: Load POCO data from a given POCO address
		*/

		$search = "";
		$mode = 0;
		if ($command == "server") {
			$server_url = $param1;
			if ($server_url == "") {
				return;
			}
			$server_url = filter_var($server_url, FILTER_SANITIZE_URL);
			if (substr(Strings::normaliseLink($server_url), 0, 7) != "http://") {
				return;
			}
			$result = "Checking server ".$server_url." - ";
			$ret = GServer::check($server_url);
			if ($ret) {
				$result .= "success";
			} else {
				$result .= "failed";
			}
			Logger::log($result, Logger::DEBUG);
		} elseif ($command == "load") {
			if (!empty($param4)) {
				$url = $param4;
			} else {
				$url = '';
			}
			PortableContact::load(intval($param1), intval($param2), intval($param3), $url);
		} elseif ($command !== "") {
			Logger::log("Unknown or missing parameter ".$command."\n");
			return;
		}

		Logger::log('start '.$search);

		if (($mode == 0) && ($search == "") && (Config::get('system', 'poco_discovery') != PortableContact::DISABLED)) {
			// Query Friendica and Hubzilla servers for their users
			PortableContact::discover();

			// Query GNU Social servers for their users ("statistics" addon has to be enabled on the GS server)
			if (!Config::get('system', 'ostatus_disabled')) {
				GContact::discoverGsUsers();
			}
		}

		Logger::log('end '.$search);

		return;
	}
}
