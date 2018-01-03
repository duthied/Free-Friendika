<?php

namespace Friendica\Module;

use Friendica\BaseModule;

require_once 'boot.php';
require_once 'include/pgettext.php';
require_once 'include/plugin.php';
require_once 'include/security.php';

/**
 * Logout module
 *
 * @author Hypolite Petovan mrpetovan@gmail.com
 */
class Logout extends BaseModule
{
	/**
	 * @brief Process logout requests
	 */
	public static function init()
	{
		call_hooks("logging_out");
		nuke_session();
		info(t('Logged out.') . EOL);
		goaway(self::getApp()->get_baseurl());
	}
}
