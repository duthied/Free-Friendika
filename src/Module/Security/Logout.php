<?php
/**
 * @file src/Module/Logout.php
 */

namespace Friendica\Module\Security;

use Friendica\BaseModule;
use Friendica\Core\Hook;
use Friendica\Core\System;
use Friendica\DI;
use Friendica\Model\Profile;

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
	public static function init(array $parameters = [])
	{
		$visitor_home = null;
		if (remote_user()) {
			$visitor_home = Profile::getMyURL();
			DI::cache()->delete('zrlInit:' . $visitor_home);
		}

		Hook::callAll("logging_out");
		DI::cookie()->clear();
		DI::session()->clear();

		if ($visitor_home) {
			System::externalRedirect($visitor_home);
		} else {
			info(DI::l10n()->t('Logged out.'));
			DI::baseUrl()->redirect();
		}
	}
}
