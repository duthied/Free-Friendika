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

namespace Friendica\Module\Notifications;

use Friendica\Content\ContactSelector;
use Friendica\Content\Nav;
use Friendica\Content\Text\BBCode;
use Friendica\Core\Protocol;
use Friendica\Core\Renderer;
use Friendica\DI;
use Friendica\Model\User;
use Friendica\Module\BaseNotifications;
use Friendica\Navigation\Notifications\ValueObject\Introduction;

/**
 * Prints notifications about introduction
 */
class Introductions extends BaseNotifications
{
	/**
	 * @inheritDoc
	 */
	public static function getNotifications()
	{
		$id  = (int)DI::args()->get(2, 0);
		$all = DI::args()->get(2) == 'all';

		$notifications = [
			'ident'         => 'introductions',
			'notifications' => DI::notificationIntro()->getList($all, self::$firstItemNum, self::ITEMS_PER_PAGE, $id),
		];

		return [
			'header'        => DI::l10n()->t('Notifications'),
			'notifications' => $notifications,
		];
	}

	public function content(): string
	{
		Nav::setSelected('introductions');

		$all = DI::args()->get(2) == 'all';

		$notificationContent   = [];
		$notificationNoContent = '';

		$notificationResult = self::getNotifications();
		$notifications      = $notificationResult['notifications'] ?? [];
		$notificationHeader = $notificationResult['header'] ?? '';

		$notificationSuggestions = Renderer::getMarkupTemplate('notifications/suggestions.tpl');
		$notificationTemplate    = Renderer::getMarkupTemplate('notifications/intros.tpl');

		// The link to switch between ignored and normal connection requests
		$notificationShowLink = [
			'href' => (!$all ? 'notifications/intros/all' : 'notifications/intros'),
			'text' => (!$all ? DI::l10n()->t('Show Ignored Requests') : DI::l10n()->t('Hide Ignored Requests')),
		];

		$owner = User::getOwnerDataById(local_user());
	
		// Loop through all introduction notifications.This creates an array with the output html for each
		// introduction
		/** @var Introduction $Introduction */
		foreach ($notifications['notifications'] as $Introduction) {

			// There are two kind of introduction. Contacts suggested by other contacts and normal connection requests.
			// We have to distinguish between these two because they use different data.
			switch ($Introduction->getLabel()) {
				case 'friend_suggestion':
					$notificationContent[] = Renderer::replaceMacros($notificationSuggestions, [
						'$type'                  => $Introduction->getLabel(),
						'$str_notification_type' => DI::l10n()->t('Notification type:'),
						'$str_type'              => $Introduction->getType(),
						'$intro_id'              => $Introduction->getIntroId(),
						'$lbl_madeby'            => DI::l10n()->t('Suggested by:'),
						'$madeby'                => $Introduction->getMadeBy(),
						'$madeby_url'            => $Introduction->getMadeByUrl(),
						'$madeby_zrl'            => $Introduction->getMadeByZrl(),
						'$madeby_addr'           => $Introduction->getMadeByAddr(),
						'$contact_id'            => $Introduction->getContactId(),
						'$photo'                 => $Introduction->getPhoto(),
						'$fullname'              => $Introduction->getName(),
						'$dfrn_url'              => $owner['url'],
						'$url'                   => $Introduction->getUrl(),
						'$zrl'                   => $Introduction->getZrl(),
						'$lbl_url'               => DI::l10n()->t('Profile URL'),
						'$addr'                  => $Introduction->getAddr(),
						'$action'                => 'follow',
						'$approve'               => DI::l10n()->t('Approve'),
						'$note'                  => $Introduction->getNote(),
						'$ignore'                => DI::l10n()->t('Ignore'),
						'$discard'               => DI::l10n()->t('Discard'),
						'$is_mobile'             => DI::mode()->isMobile(),
					]);
					break;

				// Normal connection requests
				default:
					if ($Introduction->getNetwork() === Protocol::DFRN) {
						$lbl_knowyou = DI::l10n()->t('Claims to be known to you: ');
						$knowyou     = ($Introduction->getKnowYou() ? DI::l10n()->t('Yes') : DI::l10n()->t('No'));
					} else {
						$lbl_knowyou = '';
						$knowyou = '';
					}

					$convertedName = BBCode::convert($Introduction->getName());

					$helptext  = DI::l10n()->t('Shall your connection be bidirectional or not?');
					$helptext2 = DI::l10n()->t('Accepting %s as a friend allows %s to subscribe to your posts, and you will also receive updates from them in your news feed.', $convertedName, $convertedName);
					$helptext3 = DI::l10n()->t('Accepting %s as a subscriber allows them to subscribe to your posts, but you will not receive updates from them in your news feed.', $convertedName);
		
					$friend = ['duplex', DI::l10n()->t('Friend'), '1', $helptext2, true];
					$follower = ['duplex', DI::l10n()->t('Subscriber'), '0', $helptext3, false];

					$action = 'follow_confirm';

					$header = $Introduction->getName();

					if ($Introduction->getAddr() != '') {
						$header .= ' <' . $Introduction->getAddr() . '>';
					}

					$header .= ' (' . ContactSelector::networkToName($Introduction->getNetwork(), $Introduction->getUrl()) . ')';

					if ($Introduction->getNetwork() != Protocol::DIASPORA) {
						$discard = DI::l10n()->t('Discard');
					} else {
						$discard = '';
					}

					$notificationContent[] = Renderer::replaceMacros($notificationTemplate, [
						'$type'                  => $Introduction->getLabel(),
						'$header'                => $header,
						'$str_notification_type' => DI::l10n()->t('Notification type:'),
						'$str_type'              => $Introduction->getType(),
						'$dfrn_id'               => $Introduction->getDfrnId(),
						'$uid'                   => $Introduction->getUid(),
						'$intro_id'              => $Introduction->getIntroId(),
						'$contact_id'            => $Introduction->getContactId(),
						'$photo'                 => $Introduction->getPhoto(),
						'$fullname'              => $Introduction->getName(),
						'$location'              => $Introduction->getLocation(),
						'$lbl_location'          => DI::l10n()->t('Location:'),
						'$about'                 => $Introduction->getAbout(),
						'$lbl_about'             => DI::l10n()->t('About:'),
						'$keywords'              => $Introduction->getKeywords(),
						'$lbl_keywords'          => DI::l10n()->t('Tags:'),
						'$hidden'                => ['hidden', DI::l10n()->t('Hide this contact from others'), $Introduction->isHidden(), ''],
						'$lbl_connection_type'   => $helptext,
						'$friend'                => $friend,
						'$follower'              => $follower,
						'$url'                   => $Introduction->getUrl(),
						'$zrl'                   => $Introduction->getZrl(),
						'$lbl_url'               => DI::l10n()->t('Profile URL'),
						'$addr'                  => $Introduction->getAddr(),
						'$lbl_knowyou'           => $lbl_knowyou,
						'$lbl_network'           => DI::l10n()->t('Network:'),
						'$network'               => ContactSelector::networkToName($Introduction->getNetwork(), $Introduction->getUrl()),
						'$knowyou'               => $knowyou,
						'$approve'               => DI::l10n()->t('Approve'),
						'$note'                  => $Introduction->getNote(),
						'$ignore'                => DI::l10n()->t('Ignore'),
						'$discard'               => $discard,
						'$action'                => $action,
						'$is_mobile'              => DI::mode()->isMobile(),
					]);
					break;
			}
		}

		if (count($notifications['notifications']) == 0) {
			notice(DI::l10n()->t('No introductions.'));
			$notificationNoContent = DI::l10n()->t('No more %s notifications.', $notifications['ident']);
		}

		return self::printContent($notificationHeader, $notificationContent, $notificationNoContent, $notificationShowLink);
	}
}
