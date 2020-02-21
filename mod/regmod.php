<?php
/**
 * @copyright Copyright (C) 2020, Friendica
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

use Friendica\App;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Register;
use Friendica\Model\User;
use Friendica\Module\Security\Login;

// This does not have to go through user_remove() and save the nickname
// permanently against re-registration, as the person was not yet
// allowed to have friends on this system
function user_deny($hash)
{
	$register = Register::getByHash($hash);
	if (!DBA::isResult($register)) {
		return false;
	}

	$user = User::getById($register['uid']);
	if (!DBA::isResult($user)) {
		exit();
	}

	DBA::delete('user', ['uid' => $register['uid']]);

	Register::deleteByHash($register['hash']);

	notice(DI::l10n()->t('Registration revoked for %s', $user['username']) . EOL);
	return true;
}

function regmod_content(App $a)
{
	if (!local_user()) {
		info(DI::l10n()->t('Please login.') . EOL);
		return Login::form(DI::args()->getQueryString(), intval(DI::config()->get('config', 'register_policy')) === \Friendica\Module\Register::CLOSED ? 0 : 1);
	}

	if (!is_site_admin() || !empty($_SESSION['submanage'])) {
		notice(DI::l10n()->t('Permission denied.') . EOL);
		return '';
	}

	if ($a->argc != 3) {
		exit();
	}

	$cmd = $a->argv[1];
	$hash = $a->argv[2];

	if ($cmd === 'deny') {
		user_deny($hash);
		DI::baseUrl()->redirect('admin/users/');
	}

	if ($cmd === 'allow') {
		if (User::allow($hash)) {
			info(DI::l10n()->t('Account approved.') . EOL);
		}
		DI::baseUrl()->redirect('admin/users/');
	}
}
