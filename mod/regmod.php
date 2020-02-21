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
use Friendica\DI;
use Friendica\Model\User;
use Friendica\Module\Security\Login;

function regmod_content(App $a)
{
	if (!local_user()) {
		info(DI::l10n()->t('Please login.'));
		return Login::form(DI::args()->getQueryString(), intval(DI::config()->get('config', 'register_policy')) === \Friendica\Module\Register::CLOSED ? 0 : 1);
	}

	if (!is_site_admin() || !empty($_SESSION['submanage'])) {
		notice(DI::l10n()->t('Permission denied.'));
		return '';
	}

	if ($a->argc != 3) {
		exit();
	}

	$cmd = $a->argv[1];
	$hash = $a->argv[2];

	if ($cmd === 'deny') {
		if (User::deny($hash)) {
			notice(DI::l10n()->t('Registration revoked'));
		}
		DI::baseUrl()->redirect('admin/users/');
	}

	if ($cmd === 'allow') {
		if (User::allow($hash)) {
			info(DI::l10n()->t('Account approved.'));
		}
		DI::baseUrl()->redirect('admin/users/');
	}
}
