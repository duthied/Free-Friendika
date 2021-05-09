<?php
/**
 * @copyright Copyright (C) 2010-2021, the Friendica project
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

namespace Friendica\Module;

use Friendica\BaseModule;
use Friendica\Core\Hook;
use Friendica\Core\Renderer;
use Friendica\Core\Session;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Model\Notification;
use Friendica\Model\User;
use Friendica\Network\HTTPException\ForbiddenException;

/**
 * Switches current user between delegates/parent user
 */
class Delegation extends BaseModule
{
	public static function post(array $parameters = [])
	{
		if (!local_user()) {
			return;
		}

		$uid = local_user();
		$orig_record = DI::app()->user;

		if (Session::get('submanage')) {
			$user = User::getById(Session::get('submanage'));
			if (DBA::isResult($user)) {
				$uid = intval($user['uid']);
				$orig_record = $user;
			}
		}

		$identity = intval($_POST['identity'] ?? 0);
		if (!$identity) {
			return;
		}

		$limited_id = 0;
		$original_id = $uid;

		$manages = DBA::selectToArray('manage', ['mid'], ['uid' => $uid]);
		foreach ($manages as $manage) {
			if ($identity == $manage['mid']) {
				$limited_id = $manage['mid'];
				break;
			}
		}

		if ($limited_id) {
			$user = User::getById($limited_id);
		} else {
			// Check if the target user is one of our children
			$user = DBA::selectFirst('user', [], ['uid' => $identity, 'parent-uid' => $orig_record['uid']]);

			// Check if the target user is one of our siblings
			if (!DBA::isResult($user) && ($orig_record['parent-uid'] != 0)) {
				$user = DBA::selectFirst('user', [], ['uid' => $identity, 'parent-uid' => $orig_record['parent-uid']]);
			}

			// Check if it's our parent or our own user
			if (!DBA::isResult($user)
				&& (
					$orig_record['parent-uid'] != 0 && $orig_record['parent-uid'] == $identity
					||
					$orig_record['uid'] != 0 && $orig_record['uid'] == $identity
				)
			) {
				$user = User::getById($identity);
			}
		}

		if (!DBA::isResult($user)) {
			return;
		}

		Session::clear();

		DI::auth()->setForUser(DI::app(), $user, true, true);

		if ($limited_id) {
			Session::set('submanage', $original_id);
		}

		$ret = [];
		Hook::callAll('home_init', $ret);

		DI::baseUrl()->redirect('profile/' . DI::app()->user['nickname']);
		// NOTREACHED
	}

	public static function content(array $parameters = [])
	{
		if (!local_user()) {
			throw new ForbiddenException(DI::l10n()->t('Permission denied.'));
		}

		$identities = DI::app()->identities;

		//getting additinal information for each identity
		foreach ($identities as $key => $identity) {
			$thumb = Contact::selectFirst(['thumb'], ['uid' => $identity['uid'], 'self' => true]);
			if (!DBA::isResult($thumb)) {
				continue;
			}

			$identities[$key]['thumb'] = $thumb['thumb'];

			$identities[$key]['selected'] = ($identity['nickname'] === DI::app()->user['nickname']);

			$condition = ["`uid` = ? AND `msg` != '' AND NOT (`type` IN (?, ?)) AND NOT `seen`", $identity['uid'], Notification\Type::INTRO, Notification\Type::MAIL];
			$params = ['distinct' => true, 'expression' => 'parent'];
			$notifications = DBA::count('notify', $condition, $params);

			$params = ['distinct' => true, 'expression' => 'convid'];
			$notifications += DBA::count('mail', ['uid' => $identity['uid'], 'seen' => false], $params);

			$notifications += DBA::count('intro', ['blocked' => false, 'ignore' => false, 'uid' => $identity['uid']]);

			$identities[$key]['notifications'] = $notifications;
		}

		$o = Renderer::replaceMacros(Renderer::getMarkupTemplate('delegation.tpl'), [
			'$title'      => DI::l10n()->t('Switch between your accounts'),
			'$settings_label' => DI::l10n()->t('Manage your accounts'),
			'$desc'       => DI::l10n()->t('Toggle between different identities or community/group pages which share your account details or which you have been granted "manage" permissions'),
			'$choose'     => DI::l10n()->t('Select an identity to manage: '),
			'$identities' => $identities,
			'$submit'     => DI::l10n()->t('Submit'),
		]);

		return $o;
	}
}
