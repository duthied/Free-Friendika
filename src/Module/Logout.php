<?php
/**
 * @file src/Module/Logout.php
 */

namespace Friendica\Module;

use Friendica\BaseModule;
use Friendica\Core\Authentication;
use Friendica\Core\Hook;
use Friendica\Core\L10n;

/**
 * Logout module
 *
 * @author Hypolite Petovan <hypolite@mrpetovan.com>
 */
class Logout extends BaseModule
{
	/**
	 * @brief Process logout requests
	 */
	public static function init()
	{
		Hook::callAll("logging_out");
		Authentication::deleteSession();
		info(L10n::t('Logged out.') . EOL);
		self::getApp()->internalRedirect();
	}
}
