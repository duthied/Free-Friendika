<?php
/**
 * @file src/Worker/FetchPoCo.php
 */
namespace Friendica\Worker;

use Friendica\Core\Logger;
use Friendica\Protocol\PortableContact;

class FetchPoCo
{
	// Load POCO data from a given POCO address
	public static function execute($cid, $uid, $zcid, $url)
	{
		PortableContact::load($cid, $uid, $zcid, $url);
	}
}
