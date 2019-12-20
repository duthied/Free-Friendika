<?php
/**
 * @file src/Worker/DiscoverPoCo.php
 */
namespace Friendica\Worker;

use Friendica\Core\Config;
use Friendica\Core\Logger;
use Friendica\Model\GContact;
use Friendica\Protocol\PortableContact;

class DiscoverPoCo
{
	public static function execute()
	{
		if (Config::get('system', 'poco_discovery') == PortableContact::DISABLED) {
			return;
		}

		// Query Friendica and Hubzilla servers for their users
		PortableContact::discover();

		// Query GNU Social servers for their users ("statistics" addon has to be enabled on the GS server)
		if (!Config::get('system', 'ostatus_disabled')) {
			GContact::discoverGsUsers();
		}
	}
}
