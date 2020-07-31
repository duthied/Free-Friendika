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

namespace Friendica\Module\Profile;

use Friendica\Content\Nav;
use Friendica\Content\Pager;
use Friendica\Core\Protocol;
use Friendica\Core\Renderer;
use Friendica\Core\Session;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Profile;
use Friendica\Module\BaseProfile;
use Friendica\Module\Contact as ModuleContact;

class Contacts extends BaseProfile
{
	public static function content(array $parameters = [])
	{
		if (DI::config()->get('system', 'block_public') && !Session::isAuthenticated()) {
			throw new \Friendica\Network\HTTPException\NotFoundException(DI::l10n()->t('User not found.'));
		}

		$a = DI::app();

		//@TODO: Get value from router parameters
		$nickname = $a->argv[1];
		$type = ($a->argv[3] ?? '') ?: 'all';

		Nav::setSelected('home');

		$user = DBA::selectFirst('user', [], ['nickname' => $nickname, 'blocked' => false]);
		if (!DBA::isResult($user)) {
			throw new \Friendica\Network\HTTPException\NotFoundException(DI::l10n()->t('User not found.'));
		}

		$a->profile_uid  = $user['uid'];

		Profile::load($a, $nickname);

		$is_owner = $a->profile['uid'] == local_user();

		$o = self::getTabsHTML($a, 'contacts', $is_owner, $nickname);

		if (!count($a->profile) || $a->profile['hide-friends']) {
			notice(DI::l10n()->t('Permission denied.'));
			return $o;
		}

		$condition = [
			'uid'     => $a->profile['uid'],
			'blocked' => false,
			'pending' => false,
			'hidden'  => false,
			'archive' => false,
			'network' => [Protocol::ACTIVITYPUB, Protocol::DFRN, Protocol::DIASPORA, Protocol::OSTATUS, Protocol::FEED]
		];

		switch ($type) {
			case 'followers': $condition['rel'] = [1, 3]; break;
			case 'following': $condition['rel'] = [2, 3]; break;
			case 'mutuals': $condition['rel'] = 3; break;
		}

		$total = DBA::count('contact', $condition);

		$pager = new Pager(DI::l10n(), DI::args()->getQueryString());

		$params = ['order' => ['name' => false], 'limit' => [$pager->getStart(), $pager->getItemsPerPage()]];

		$contacts_stmt = DBA::select('contact', [], $condition, $params);

		if (!DBA::isResult($contacts_stmt)) {
			notice(DI::l10n()->t('No contacts.'));
			return $o;
		}

		$contacts = [];

		while ($contact = DBA::fetch($contacts_stmt)) {
			if ($contact['self']) {
				continue;
			}
			$contacts[] = ModuleContact::getContactTemplateVars($contact);
		}

		DBA::close($contacts_stmt);

		switch ($type) {
			case 'followers':    $title = DI::l10n()->tt('Follower (%s)', 'Followers (%s)', $total); break;
			case 'following':    $title = DI::l10n()->tt('Following (%s)', 'Following (%s)', $total); break;
			case 'mutuals':      $title = DI::l10n()->tt('Mutual friend (%s)', 'Mutual friends (%s)', $total); break;

			case 'all': default: $title = DI::l10n()->tt('Contact (%s)', 'Contacts (%s)', $total); break;
		}

		$tpl = Renderer::getMarkupTemplate('profile/contacts.tpl');
		$o .= Renderer::replaceMacros($tpl, [
			'$title'    => $title,
			'$nickname' => $nickname,
			'$type'     => $type,

			'$all_label' => DI::l10n()->t('All contacts'),
			'$followers_label' => DI::l10n()->t('Followers'),
			'$following_label' => DI::l10n()->t('Following'),
			'$mutuals_label' => DI::l10n()->t('Mutual friends'),

			'$contacts' => $contacts,
			'$paginate' => $pager->renderFull($total),
		]);

		return $o;
	}
}
