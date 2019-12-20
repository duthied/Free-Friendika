<?php
/**
 * @file src/Worker/UpdateServerDirectory.php
 */
namespace Friendica\Worker;

use Friendica\Core\Logger;
use Friendica\Protocol\PortableContact;

class UpdateServerDirectory
{
	// Discover the given server id for their contacts
	public static function execute($gserverid)
	{
		PortableContact::discoverSingleServer($gserverid);
		return;
	}
}
