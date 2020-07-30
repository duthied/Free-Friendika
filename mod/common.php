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
use Friendica\Content\ContactSelector;
use Friendica\Content\Pager;
use Friendica\Core\Renderer;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model;
use Friendica\Model\Contact;
use Friendica\Module;
use Friendica\Util\Strings;

function common_content(App $a)
{
	$o = '';

	$cmd = $a->argv[1];
	$uid = intval($a->argv[2]);
	$cid = intval($a->argv[3]);
	$zcid = 0;

	if (!local_user()) {
		notice(DI::l10n()->t('Permission denied.'));
		return;
	}

	if ($cmd !== 'loc' && $cmd != 'rem') {
		return;
	}

	if (!$uid) {
		return;
	}

	if ($cmd === 'loc' && $cid) {
		$contact = DBA::selectFirst('contact', ['name', 'url', 'photo', 'uid', 'id'], ['id' => $cid, 'uid' => $uid]);

		if (DBA::isResult($contact)) {
			DI::page()['aside'] = "";
			Model\Profile::load($a, "", Model\Contact::getByURL($contact["url"], false));
		}
	} else {
		$contact = DBA::selectFirst('contact', ['name', 'url', 'photo', 'uid', 'id'], ['self' => true, 'uid' => $uid]);

		if (DBA::isResult($contact)) {
			$vcard_widget = Renderer::replaceMacros(Renderer::getMarkupTemplate('widget/vcard.tpl'), [
				'$name'  => $contact['name'],
				'$photo' => $contact['photo'],
				'url'    => 'contact/' . $cid
			]);

			if (empty(DI::page()['aside'])) {
				DI::page()['aside'] = '';
			}
			DI::page()['aside'] .= $vcard_widget;
		}
	}

	if (!DBA::isResult($contact)) {
		return;
	}

	if (!$cid && Model\Profile::getMyURL()) {
		$contact = DBA::selectFirst('contact', ['id'], ['nurl' => Strings::normaliseLink(Model\Profile::getMyURL()), 'uid' => $uid]);
		if (DBA::isResult($contact)) {
			$cid = $contact['id'];
		} else {
			$gcontact = DBA::selectFirst('gcontact', ['id'], ['nurl' => Strings::normaliseLink(Model\Profile::getMyURL())]);
			if (DBA::isResult($gcontact)) {
				$zcid = $gcontact['id'];
			}
		}
	}

	if ($cid == 0 && $zcid == 0) {
		return;
	}

	if ($cid) {
		$total = Model\GContact::countCommonFriends($uid, $cid);
	} else {
		$total = Model\GContact::countCommonFriendsZcid($uid, $zcid);
	}

	if ($total < 1) {
		notice(DI::l10n()->t('No contacts in common.'));
		return $o;
	}

	$pager = new Pager(DI::l10n(), DI::args()->getQueryString());

	if ($cid) {
		$common_friends = Model\GContact::commonFriends($uid, $cid, $pager->getStart(), $pager->getItemsPerPage());
	} else {
		$common_friends = Model\GContact::commonFriendsZcid($uid, $zcid, $pager->getStart(), $pager->getItemsPerPage());
	}

	if (!DBA::isResult($common_friends)) {
		return $o;
	}

	$id = 0;

	$entries = [];
	foreach ($common_friends as $common_friend) {
		$contact = Model\Contact::getByURL($common_friend['url']);
		if (!empty($contact)) {
			$entries[] = Model\Contact::getTemplateData($contact, ++$id);
		}
	}

	$title = '';
	$tab_str = '';
	if ($cmd === 'loc' && $cid && local_user() == $uid) {
		$tab_str = Module\Contact::getTabsHTML($a, $contact, 5);
	} else {
		$title = DI::l10n()->t('Common Friends');
	}

	$tpl = Renderer::getMarkupTemplate('viewcontact_template.tpl');

	$o .= Renderer::replaceMacros($tpl, [
		'$title'    => $title,
		'$tab_str'  => $tab_str,
		'$contacts' => $entries,
		'$paginate' => $pager->renderFull($total),
	]);

	return $o;
}
