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

namespace Friendica\Module\Admin;

use Friendica\Content\Pager;
use Friendica\Core\Renderer;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Register;
use Friendica\Model\User;
use Friendica\Module\BaseAdmin;
use Friendica\Util\Temporal;

class Users extends BaseAdmin
{
	public static function post(array $parameters = [])
	{
		parent::post($parameters);

		$pending     = $_POST['pending']           ?? [];
		$users       = $_POST['user']              ?? [];
		$nu_name     = $_POST['new_user_name']     ?? '';
		$nu_nickname = $_POST['new_user_nickname'] ?? '';
		$nu_email    = $_POST['new_user_email']    ?? '';
		$nu_language = DI::config()->get('system', 'language');

		parent::checkFormSecurityTokenRedirectOnError('/admin/users', 'admin_users');

		if ($nu_name !== '' && $nu_email !== '' && $nu_nickname !== '') {
			try {
				User::createMinimal($nu_name, $nu_email, $nu_nickname, $nu_language);
			} catch (\Exception $ex) {
				notice($ex->getMessage());
				return;
			}
		}

		if (!empty($_POST['page_users_block'])) {
			foreach ($users as $uid) {
				User::block($uid);
			}
			notice(DI::l10n()->tt('%s user blocked', '%s users blocked', count($users)));
		}

		if (!empty($_POST['page_users_unblock'])) {
			foreach ($users as $uid) {
				User::block($uid, false);
			}
			notice(DI::l10n()->tt('%s user unblocked', '%s users unblocked', count($users)));
		}

		if (!empty($_POST['page_users_delete'])) {
			foreach ($users as $uid) {
				if (local_user() != $uid) {
					User::remove($uid);
				} else {
					notice(DI::l10n()->t('You can\'t remove yourself'));
				}
			}

			notice(DI::l10n()->tt('%s user deleted', '%s users deleted', count($users)));
		}

		if (!empty($_POST['page_users_approve'])) {
			foreach ($pending as $hash) {
				User::allow($hash);
			}
			notice(DI::l10n()->tt('%s user approved', '%s users approved', count($pending)));
		}

		if (!empty($_POST['page_users_deny'])) {
			foreach ($pending as $hash) {
				User::deny($hash);
			}
			notice(DI::l10n()->tt('%s registration revoked', '%s registrations revoked', count($pending)));
		}

		DI::baseUrl()->redirect('admin/users');
	}

	public static function content(array $parameters = [])
	{
		parent::content($parameters);

		$a = DI::app();

		if ($a->argc > 3) {
			// @TODO: Replace with parameter from router
			$action = $a->argv[2];
			$uid = $a->argv[3];
			$user = User::getById($uid, ['username', 'blocked']);
			if (!DBA::isResult($user)) {
				notice('User not found' . EOL);
				DI::baseUrl()->redirect('admin/users');
				return ''; // NOTREACHED
			}

			switch ($action) {
				case 'delete':
					if (local_user() != $uid) {
						parent::checkFormSecurityTokenRedirectOnError('/admin/users', 'admin_users', 't');
						// delete user
						User::remove($uid);

						notice(DI::l10n()->t('User "%s" deleted', $user['username']));
					} else {
						notice(DI::l10n()->t('You can\'t remove yourself'));
					}
					break;
				case 'block':
					parent::checkFormSecurityTokenRedirectOnError('/admin/users', 'admin_users', 't');
					User::block($uid);
					notice(DI::l10n()->t('User "%s" blocked', $user['username']));
					break;
				case 'unblock':
					parent::checkFormSecurityTokenRedirectOnError('/admin/users', 'admin_users', 't');
					User::block($uid, false);
					notice(DI::l10n()->t('User "%s" unblocked', $user['username']));
					break;
				case 'allow':
					parent::checkFormSecurityTokenRedirectOnError('/admin/users', 'admin_users', 't');
					User::allow(Register::getPendingForUser($uid)['hash'] ?? '');
					notice(DI::l10n()->t('Account approved.'));
					break;
				case 'deny':
					parent::checkFormSecurityTokenRedirectOnError('/admin/users', 'admin_users', 't');
					User::deny(Register::getPendingForUser($uid)['hash'] ?? '');
					notice(DI::l10n()->t('Registration revoked'));
					break;
			}

			DI::baseUrl()->redirect('admin/users');
		}

		/* get pending */
		$pending = Register::getPending();

		$pager = new Pager(DI::l10n(), DI::args()->getQueryString(), 100);

		$valid_orders = [
			'contact.name',
			'user.email',
			'user.register_date',
			'user.login_date',
			'lastitem_date',
			'user.page-flags'
		];

		$order = 'contact.name';
		$order_direction = '+';
		if (!empty($_GET['o'])) {
			$new_order = $_GET['o'];
			if ($new_order[0] === '-') {
				$order_direction = '-';
				$new_order = substr($new_order, 1);
			}

			if (in_array($new_order, $valid_orders)) {
				$order = $new_order;
			}
		}

		$users = User::getList($pager->getStart(), $pager->getItemsPerPage(), 'all', $order, $order_direction);

		$adminlist = explode(',', str_replace(' ', '', DI::config()->get('config', 'admin_email')));
		$_setup_users = function ($e) use ($adminlist) {
			$page_types = [
				User::PAGE_FLAGS_NORMAL    => DI::l10n()->t('Normal Account Page'),
				User::PAGE_FLAGS_SOAPBOX   => DI::l10n()->t('Soapbox Page'),
				User::PAGE_FLAGS_COMMUNITY => DI::l10n()->t('Public Forum'),
				User::PAGE_FLAGS_FREELOVE  => DI::l10n()->t('Automatic Friend Page'),
				User::PAGE_FLAGS_PRVGROUP  => DI::l10n()->t('Private Forum')
			];
			$account_types = [
				User::ACCOUNT_TYPE_PERSON       => DI::l10n()->t('Personal Page'),
				User::ACCOUNT_TYPE_ORGANISATION => DI::l10n()->t('Organisation Page'),
				User::ACCOUNT_TYPE_NEWS         => DI::l10n()->t('News Page'),
				User::ACCOUNT_TYPE_COMMUNITY    => DI::l10n()->t('Community Forum'),
				User::ACCOUNT_TYPE_RELAY        => DI::l10n()->t('Relay'),
			];

			$e['page_flags_raw'] = $e['page-flags'];
			$e['page-flags'] = $page_types[$e['page-flags']];

			$e['account_type_raw'] = ($e['page_flags_raw'] == 0) ? $e['account-type'] : -1;
			$e['account-type'] = ($e['page_flags_raw'] == 0) ? $account_types[$e['account-type']] : '';

			$e['register_date'] = Temporal::getRelativeDate($e['register_date']);
			$e['login_date'] = Temporal::getRelativeDate($e['login_date']);
			$e['lastitem_date'] = Temporal::getRelativeDate($e['lastitem_date']);
			$e['is_admin'] = in_array($e['email'], $adminlist);
			$e['is_deletable'] = (intval($e['uid']) != local_user());
			$e['deleted'] = ($e['account_removed'] ? Temporal::getRelativeDate($e['account_expires_on']) : False);

			return $e;
		};

		$tmp_users = array_map($_setup_users, $users);

		// Get rid of dashes in key names, Smarty3 can't handle them
		// and extracting deleted users

		$deleted = [];
		$users = [];
		foreach ($tmp_users as $user) {
			foreach ($user as $k => $v) {
				$newkey = str_replace('-', '_', $k);
				$user[$newkey] = $v;
			}

			if ($user['deleted']) {
				$deleted[] = $user;
			} else {
				$users[] = $user;
			}
		}

		$th_users = array_map(null, [DI::l10n()->t('Name'), DI::l10n()->t('Email'), DI::l10n()->t('Register date'), DI::l10n()->t('Last login'), DI::l10n()->t('Last public item'), DI::l10n()->t('Type')], $valid_orders);

		$t = Renderer::getMarkupTemplate('admin/users.tpl');
		$o = Renderer::replaceMacros($t, [
			// strings //
			'$title' => DI::l10n()->t('Administration'),
			'$page' => DI::l10n()->t('Users'),
			'$submit' => DI::l10n()->t('Add User'),
			'$select_all' => DI::l10n()->t('select all'),
			'$h_pending' => DI::l10n()->t('User registrations waiting for confirm'),
			'$h_deleted' => DI::l10n()->t('User waiting for permanent deletion'),
			'$th_pending' => [DI::l10n()->t('Request date'), DI::l10n()->t('Name'), DI::l10n()->t('Email')],
			'$no_pending' => DI::l10n()->t('No registrations.'),
			'$pendingnotetext' => DI::l10n()->t('Note from the user'),
			'$approve' => DI::l10n()->t('Approve'),
			'$deny' => DI::l10n()->t('Deny'),
			'$delete' => DI::l10n()->t('Delete'),
			'$block' => DI::l10n()->t('Block'),
			'$blocked' => DI::l10n()->t('User blocked'),
			'$unblock' => DI::l10n()->t('Unblock'),
			'$siteadmin' => DI::l10n()->t('Site admin'),
			'$accountexpired' => DI::l10n()->t('Account expired'),

			'$h_users' => DI::l10n()->t('Users'),
			'$h_newuser' => DI::l10n()->t('New User'),
			'$th_deleted' => [DI::l10n()->t('Name'), DI::l10n()->t('Email'), DI::l10n()->t('Register date'), DI::l10n()->t('Last login'), DI::l10n()->t('Last public item'), DI::l10n()->t('Permanent deletion')],
			'$th_users' => $th_users,
			'$order_users' => $order,
			'$order_direction_users' => $order_direction,

			'$confirm_delete_multi' => DI::l10n()->t('Selected users will be deleted!\n\nEverything these users had posted on this site will be permanently deleted!\n\nAre you sure?'),
			'$confirm_delete' => DI::l10n()->t('The user {0} will be deleted!\n\nEverything this user has posted on this site will be permanently deleted!\n\nAre you sure?'),

			'$form_security_token' => parent::getFormSecurityToken('admin_users'),

			// values //
			'$baseurl' => DI::baseUrl()->get(true),

			'$pending' => $pending,
			'deleted' => $deleted,
			'$users' => $users,
			'$newusername' => ['new_user_name', DI::l10n()->t('Name'), '', DI::l10n()->t('Name of the new user.')],
			'$newusernickname' => ['new_user_nickname', DI::l10n()->t('Nickname'), '', DI::l10n()->t('Nickname of the new user.')],
			'$newuseremail' => ['new_user_email', DI::l10n()->t('Email'), '', DI::l10n()->t('Email address of the new user.'), '', '', 'email'],
		]);

		$o .= $pager->renderFull(DBA::count('user'));

		return $o;
	}
}
