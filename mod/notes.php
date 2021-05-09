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

use Friendica\App;
use Friendica\Content\Nav;
use Friendica\Content\Pager;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Item;
use Friendica\Model\Post;
use Friendica\Module\BaseProfile;

function notes_init(App $a)
{
	if (! local_user()) {
		return;
	}

	Nav::setSelected('home');
}


function notes_content(App $a, $update = false)
{
	if (!local_user()) {
		notice(DI::l10n()->t('Permission denied.'));
		return;
	}

	$o = BaseProfile::getTabsHTML($a, 'notes', true);

	if (!$update) {
		$o .= '<h3>' . DI::l10n()->t('Personal Notes') . '</h3>';

		$x = [
			'is_owner' => true,
			'allow_location' => (($a->user['allow_location']) ? true : false),
			'default_location' => $a->user['default-location'],
			'nickname' => $a->user['nickname'],
			'lockstate' => 'lock',
			'acl' => \Friendica\Core\ACL::getSelfOnlyHTML(local_user(), DI::l10n()->t('Personal notes are visible only by yourself.')),
			'bang' => '',
			'visitor' => 'block',
			'profile_uid' => local_user(),
			'button' => DI::l10n()->t('Save'),
			'acl_data' => '',
		];

		$o .= status_editor($a, $x, $a->contact['id']);
	}

	$condition = ['uid' => local_user(), 'post-type' => Item::PT_PERSONAL_NOTE, 'gravity' => GRAVITY_PARENT,
		'contact-id'=> $a->contact['id']];

	if (DI::mode()->isMobile()) {
		$itemsPerPage = DI::pConfig()->get(local_user(), 'system', 'itemspage_mobile_network',
			DI::config()->get('system', 'itemspage_network_mobile'));
	} else {
		$itemsPerPage = DI::pConfig()->get(local_user(), 'system', 'itemspage_network',
			DI::config()->get('system', 'itemspage_network'));
	}

	$pager = new Pager(DI::l10n(), DI::args()->getQueryString(), $itemsPerPage);

	$params = ['order' => ['created' => true],
		'limit' => [$pager->getStart(), $pager->getItemsPerPage()]];
	$r = Post::selectThreadForUser(local_user(), ['uri-id'], $condition, $params);

	$count = 0;

	if (DBA::isResult($r)) {
		$notes = Post::toArray($r);

		$count = count($notes);

		$o .= conversation($a, $notes, 'notes', $update);
	}

	$o .= $pager->renderMinimal($count);

	return $o;
}
