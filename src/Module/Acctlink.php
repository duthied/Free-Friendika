<?php

namespace Friendica\Module;

use Friendica\BaseModule;
use Friendica\Network\Probe;
use Friendica\Core\System;

/**
 * Redirects to another URL based on the parameter 'addr'
 */
class Acctlink extends BaseModule
{
	public static function content(array $parameters = [])
	{
		$addr = trim($_GET['addr'] ?? '');

		if ($addr) {
			$url = Probe::uri($addr)['url'] ?? '';

			if ($url) {
				System::externalRedirect($url);
				exit();
			}
		}
	}
}
