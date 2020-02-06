<?php

/**
 * @file src/Worker/AddContact.php
 */

namespace Friendica\Worker;

use Friendica\Core\Logger;
use Friendica\Model\Contact;

class AddContact
{
	/**
	 * Add contact data via probe
	 * @param int    $uid User ID
	 * @param string $url Contact link
	 */
	public static function execute(int $uid, string $url)
	{
		$result = Contact::createFromProbe($uid, $url, '', false);
		Logger::info('Added contact', ['uid' => $uid, 'url' => $url, 'result' => $result]);
	}
}
