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

namespace Friendica\Content\Widget;

use Friendica\Content\Text\HTML;
use Friendica\Core\Hook;
use Friendica\Core\Protocol;
use Friendica\Core\Renderer;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Model\User;

/**
 * ContactBlock widget
 *
 * @author Hypolite Petovan
 */
class ContactBlock
{
	/**
	 * Get HTML for contact block
	 *
	 * @template widget/contacts.tpl
	 * @hook contact_block_end (contacts=>array, output=>string)
	 * @return string
	 */
	public static function getHTML(array $profile)
	{
		$o = '';

		$shown = DI::pConfig()->get($profile['uid'], 'system', 'display_friend_count', 24);
		if ($shown == 0) {
			return $o;
		}

		if (!empty($profile['hide-friends'])) {
			return $o;
		}

		$contacts = [];

		$total = DBA::count('contact', [
			'uid' => $profile['uid'],
			'self' => false,
			'blocked' => false,
			'pending' => false,
			'hidden' => false,
			'archive' => false,
			'network' => [Protocol::DFRN, Protocol::ACTIVITYPUB, Protocol::OSTATUS, Protocol::DIASPORA, Protocol::FEED],
		]);

		$contacts_title = DI::l10n()->t('No contacts');

		$micropro = [];

		if ($total) {
			// Only show followed for personal accounts, followers for pages
			if ((($profile['account-type'] ?? '') ?: User::ACCOUNT_TYPE_PERSON) == User::ACCOUNT_TYPE_PERSON) {
				$rel = [Contact::SHARING, Contact::FRIEND];
			} else {
				$rel = [Contact::FOLLOWER, Contact::FRIEND];
			}

			$contact_ids_stmt = DBA::select('contact', ['id'], [
				'uid' => $profile['uid'],
				'self' => false,
				'blocked' => false,
				'pending' => false,
				'hidden' => false,
				'archive' => false,
				'rel' => $rel,
				'network' => Protocol::FEDERATED,
			], ['limit' => $shown]);

			if (DBA::isResult($contact_ids_stmt)) {
				$contact_ids = [];
				while($contact = DBA::fetch($contact_ids_stmt)) {
					$contact_ids[] = $contact["id"];
				}

				$contacts_stmt = DBA::select('contact', ['id', 'uid', 'addr', 'url', 'name', 'thumb', 'network'], ['id' => $contact_ids]);

				if (DBA::isResult($contacts_stmt)) {
					$contacts_title = DI::l10n()->tt('%d Contact', '%d Contacts', $total);
					$micropro = [];

					while ($contact = DBA::fetch($contacts_stmt)) {
						$contacts[] = $contact;
						$micropro[] = HTML::micropro($contact, true, 'mpfriend');
					}
				}

				DBA::close($contacts_stmt);
			}

			DBA::close($contact_ids_stmt);
		}

		$tpl = Renderer::getMarkupTemplate('widget/contacts.tpl');
		$o = Renderer::replaceMacros($tpl, [
			'$contacts' => $contacts_title,
			'$nickname' => $profile['nickname'],
			'$viewcontacts' => DI::l10n()->t('View Contacts'),
			'$micropro' => $micropro,
		]);

		$arr = ['contacts' => $contacts, 'output' => $o];

		Hook::callAll('contact_block_end', $arr);

		return $o;
	}
}
