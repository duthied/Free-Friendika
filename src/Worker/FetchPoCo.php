<?php
/**
 * @file src/Worker/FetchPoCo.php
 */
namespace Friendica\Worker;

use Friendica\Core\Logger;
use Friendica\Protocol\PortableContact;

class FetchPoCo
{
	/**
	 * Fetch PortableContacts from a given PoCo server address
	 *
	 * @param integer $cid  Contact ID
	 * @param integer $uid  User ID
	 * @param integer $zcid Global Contact ID
	 * @param integer $url  PoCo address that should be polled
	 */
	public static function execute($cid, $uid, $zcid, $url)
	{
		PortableContact::load($cid, $uid, $zcid, $url);
	}
}
