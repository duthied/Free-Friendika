<?php
/**
 * @file src/Worker/UpdateServerDirectories.php
 */
namespace Friendica\Worker;

use Friendica\DI;
use Friendica\Model\GContact;
use Friendica\Model\GServer;
use Friendica\Protocol\PortableContact;

class UpdateServerDirectories
{
	/**
	 * Query global servers for their users
	 */
	public static function execute()
	{
		if (DI::config()->get('system', 'poco_discovery') == PortableContact::DISABLED) {
			return;
		}

		// Query Friendica and Hubzilla servers for their users
		GServer::discover();

		// Query GNU Social servers for their users ("statistics" addon has to be enabled on the GS server)
		if (!DI::config()->get('system', 'ostatus_disabled')) {
			GContact::discoverGsUsers();
		}
	}
}
