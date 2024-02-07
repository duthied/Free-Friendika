<?php
/**
 * @copyright Copyright (C) 2010-2024, the Friendica project
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

namespace Friendica\Module\Update;

use Friendica\BaseModule;
use Friendica\Content\Conversation;
use Friendica\Core\System;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Item;
use Friendica\Model\Post;
use Friendica\Model\Profile as ProfileModel;
use Friendica\Model\User;
use Friendica\Network\HTTPException\ForbiddenException;
use Friendica\Util\DateTimeFormat;

class Profile extends BaseModule
{
	protected function rawContent(array $request = [])
	{
		$a = DI::app();

		// Ensure we've got a profile owner if updating.
		$a->setProfileOwner((int)($request['p'] ?? 0));

		if (DI::config()->get('system', 'block_public') && !DI::userSession()->getLocalUserId() && !DI::userSession()->getRemoteContactID($a->getProfileOwner())) {
			throw new ForbiddenException();
		}

		$remote_contact = DI::userSession()->getRemoteContactID($a->getProfileOwner());
		$is_owner = DI::userSession()->getLocalUserId() == $a->getProfileOwner();
		$last_updated_key = "profile:" . $a->getProfileOwner() . ":" . DI::userSession()->getLocalUserId() . ":" . $remote_contact;

		if (!DI::userSession()->isAuthenticated()) {
			$user = User::getById($a->getProfileOwner(), ['hidewall']);
			if ($user['hidewall']) {
				throw new ForbiddenException(DI::l10n()->t('Access to this profile has been restricted.'));
			}
		}

		$o = '';

		if (empty($request['force'])) {
			System::htmlUpdateExit($o);
		}

		// Get permissions SQL - if $remote_contact is true, our remote user has been pre-verified and we already have fetched their circles
		$sql_extra = Item::getPermissionsSQLByUserId($a->getProfileOwner());

		$last_updated_array = DI::session()->get('last_updated', []);

		$last_updated = $last_updated_array[$last_updated_key] ?? 0;

		$condition = ["`uid` = ? AND NOT `contact-blocked` AND NOT `contact-pending`
				AND `visible` AND (NOT `deleted` OR `gravity` = ?)
				AND `wall` " . $sql_extra, $a->getProfileOwner(), Item::GRAVITY_ACTIVITY];

		if ($request['force'] && !empty($request['item'])) {
			// When the parent is provided, we only fetch this
			$condition = DBA::mergeConditions($condition, ['parent' => $request['item']]);
		} elseif ($is_owner || !$last_updated) {
			// If the page user is the owner of the page we should query for unseen
			// items. Otherwise use a timestamp of the last succesful update request.
			$condition = DBA::mergeConditions($condition, ['unseen' => true]);
		} else {
			$gmupdate = gmdate(DateTimeFormat::MYSQL, $last_updated);
			$condition = DBA::mergeConditions($condition, ["`received` > ?", $gmupdate]);
		}

		$items = Post::selectToArray(['parent-uri-id', 'created', 'received'], $condition, ['group_by' => ['parent-uri-id'], 'order' => ['received' => true]]);
		if (!DBA::isResult($items)) {
			return;
		}

		// @todo the DBA functions should handle "SELECT field AS alias" in the future,
		// so that this workaround here could be removed.
		$items = array_map(function ($item) {
			$item['uri-id'] = $item['parent-uri-id'];
			unset($item['parent-uri-id']);
			return $item;
		}, $items);

		// Set a time stamp for this page. We will make use of it when we
		// search for new items (update routine)
		$last_updated_array[$last_updated_key] = time();
		DI::session()->set('last_updated', $last_updated_array);

		if ($is_owner && !$a->getProfileOwner() && ProfileModel::shouldDisplayEventList(DI::userSession()->getLocalUserId(), DI::mode())) {
			$o .= ProfileModel::getBirthdays(DI::userSession()->getLocalUserId());
			$o .= ProfileModel::getEventsReminderHTML(DI::userSession()->getLocalUserId(), DI::userSession()->getPublicContactId());
		}

		if ($is_owner) {
			$unseen = Post::exists(['wall' => true, 'unseen' => true, 'uid' => DI::userSession()->getLocalUserId()]);
			if ($unseen) {
				Item::update(['unseen' => false], ['wall' => true, 'unseen' => true, 'uid' => DI::userSession()->getLocalUserId()]);
			}
		}

		$o .= DI::conversation()->render($items, Conversation::MODE_PROFILE, $a->getProfileOwner(), false, 'received', $a->getProfileOwner());

		System::htmlUpdateExit($o);
	}
}
