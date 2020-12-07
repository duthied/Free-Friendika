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
use Friendica\Core\Hook;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Group;
use Friendica\Model\Item;

function lockview_content(App $a)
{
	$type = (($a->argc > 1) ? $a->argv[1] : 0);
	if (is_numeric($type)) {
		$item_id = intval($type);
		$type = 'item';
	} else {
		$item_id = (($a->argc > 2) ? intval($a->argv[2]) : 0);
	}

	if (!$item_id) {
		exit();
	}

	if (!in_array($type, ['item','photo','event'])) {
		exit();
	}

	$fields = ['uid', 'allow_cid', 'allow_gid', 'deny_cid', 'deny_gid'];
	$condition = ['id' => $item_id];

	if ($type != 'item') {
		$item = DBA::selectFirst($type, $fields, $condition);
	} else {
		$fields[] = 'private';
		$item = Item::selectFirst($fields, $condition);
	}

	if (!DBA::isResult($item)) {
		exit();
	}

	Hook::callAll('lockview_content', $item);

	if ($item['uid'] != local_user()) {
		echo DI::l10n()->t('Remote privacy information not available.') . '<br />';
		exit();
	}

	if (isset($item['private'])
		&& $item['private'] == Item::PRIVATE
		&& empty($item['allow_cid'])
		&& empty($item['allow_gid'])
		&& empty($item['deny_cid'])
		&& empty($item['deny_gid']))
	{
		echo DI::l10n()->t('Remote privacy information not available.') . '<br />';
		exit();
	}

	$aclFormatter = DI::aclFormatter();

	$allowed_users = $aclFormatter->expand($item['allow_cid']);
	$allowed_groups = $aclFormatter->expand($item['allow_gid']);
	$deny_users = $aclFormatter->expand($item['deny_cid']);
	$deny_groups = $aclFormatter->expand($item['deny_gid']);

	$o = DI::l10n()->t('Visible to:') . '<br />';
	$l = [];

	if (count($allowed_groups)) {
		$key = array_search(Group::FOLLOWERS, $allowed_groups);
		if ($key !== false) {
			$l[] = '<b>' . DI::l10n()->t('Followers') . '</b>';
			unset($allowed_groups[$key]);
		}

		$key = array_search(Group::MUTUALS, $allowed_groups);
		if ($key !== false) {
			$l[] = '<b>' . DI::l10n()->t('Mutuals') . '</b>';
			unset($allowed_groups[$key]);
		}


		$r = q("SELECT `name` FROM `group` WHERE `id` IN ( %s )",
			DBA::escape(implode(', ', $allowed_groups))
		);
		if (DBA::isResult($r)) {
			foreach ($r as $rr) {
				$l[] = '<b>' . $rr['name'] . '</b>';
			}
		}
	}

	if (count($allowed_users)) {
		$r = q("SELECT `name` FROM `contact` WHERE `id` IN ( %s )",
			DBA::escape(implode(', ', $allowed_users))
		);
		if (DBA::isResult($r)) {
			foreach ($r as $rr) {
				$l[] = $rr['name'];
			}
		}
	}

	if (count($deny_groups)) {
		$key = array_search(Group::FOLLOWERS, $deny_groups);
		if ($key !== false) {
			$l[] = '<b><strike>' . DI::l10n()->t('Followers') . '</strike></b>';
			unset($deny_groups[$key]);
		}

		$key = array_search(Group::MUTUALS, $deny_groups);
		if ($key !== false) {
			$l[] = '<b><strike>' . DI::l10n()->t('Mutuals') . '</strike></b>';
			unset($deny_groups[$key]);
		}

		$r = q("SELECT `name` FROM `group` WHERE `id` IN ( %s )",
			DBA::escape(implode(', ', $deny_groups))
		);
		if (DBA::isResult($r)) {
			foreach ($r as $rr) {
				$l[] = '<b><strike>' . $rr['name'] . '</strike></b>';
			}
		}
	}

	if (count($deny_users)) {
		$r = q("SELECT `name` FROM `contact` WHERE `id` IN ( %s )",
			DBA::escape(implode(', ', $deny_users))
		);
		if (DBA::isResult($r)) {
			foreach ($r as $rr) {
				$l[] = '<strike>' . $rr['name'] . '</strike>';
			}
		}
	}

	echo $o . implode(', ', $l);
	exit();

}
