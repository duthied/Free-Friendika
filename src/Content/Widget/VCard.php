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

namespace Friendica\Content\Widget;

use Friendica\Content\Text\BBCode;
use Friendica\Core\Protocol;
use Friendica\Core\Renderer;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Util\Strings;

/**
 * VCard widget
 *
 * @author Michael Vogel
 */
class VCard
{
	/**
	 * Get HTML for vcard block
	 *
	 * @template widget/vcard.tpl
	 * @return string
	 */
	public static function getHTML(array $contact)
	{
		if (($contact['network'] != '') && ($contact['network'] != Protocol::DFRN)) {
			$network_link = Strings::formatNetworkName($contact['network'], $contact['url']);
		} else {
			$network_link = '';
		}

		$follow_link = '';
		$unfollow_link = '';
		$wallmessage_link = '';

		if (local_user()) {
			if ($contact['uid']) {
				$id      = $contact['id'];
				$rel     = $contact['rel'];
				$pending = $contact['pending'];
			} else {
				$pcontact = Contact::selectFirst(['id', 'rel', 'pending'], ['uid' => local_user(), 'uri-id' => $contact['uri-id']]);
				$id      = $pcontact['id'] ?? 0;
				$rel     = $pcontact['rel'] ?? Contact::NOTHING;
				$pending = $pcontact['pending'] ?? false;
			}

			if (in_array($contact['network'], Protocol::NATIVE_SUPPORT)) {
				if (in_array($rel, [Contact::SHARING, Contact::FRIEND])) {
					$unfollow_link = 'unfollow?url=' . urlencode($contact['url']) . '&auto=1';
				} elseif(!$pending) {
					$follow_link = 'follow?url=' . urlencode($contact['url']) . '&auto=1';
				}
			}

			if (in_array($rel, [Contact::FOLLOWER, Contact::FRIEND]) && Contact::canReceivePrivateMessages($contact)) {
				$wallmessage_link = 'message/new/' . $id;
			}
		}

		return Renderer::replaceMacros(Renderer::getMarkupTemplate('widget/vcard.tpl'), [
			'$contact'          => $contact,
			'$photo'            => Contact::getPhoto($contact),
			'$url'              => Contact::magicLinkByContact($contact, $contact['url']),
			'$about'            => BBCode::convertForUriId($contact['uri-id'] ?? 0, $contact['about'] ?? ''),
			'$xmpp'             => DI::l10n()->t('XMPP:'),
			'$location'         => DI::l10n()->t('Location:'),
			'$network_link'     => $network_link,
			'$network'          => DI::l10n()->t('Network:'),
			'$account_type'     => Contact::getAccountType($contact),
			'$follow'           => DI::l10n()->t('Follow'),
			'$follow_link'      => $follow_link,
			'$unfollow'         => DI::l10n()->t('Unfollow'),
			'$unfollow_link'    => $unfollow_link,
			'$wallmessage'      => DI::l10n()->t('Message'),
			'$wallmessage_link' => $wallmessage_link,
		]);
	}
}
