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

use Friendica\Content\Nav;
use Friendica\Core\Renderer;
use Friendica\DI;
use Friendica\Module\BaseNotifications;
use Friendica\Navigation\Notifications\Collection\FormattedNotifications;
use Friendica\Navigation\Notifications\ValueObject\FormattedNotification;
use Friendica\Network\HTTPException\InternalServerErrorException;

/**
 * Prints all notification types except introduction:
 * - Network
 * - System
 * - Personal
 * - Home
 */
class Notifications extends BaseNotifications
{
	/**
	 * {@inheritDoc}
	 */
	public static function getNotifications()
	{
		$notificationHeader = '';
		$notifications = [];

		/** @var \Friendica\Navigation\Notifications\Factory\FormattedNotification $factory */
		$factory = DI::getDice()->create(\Friendica\Navigation\Notifications\Factory\FormattedNotification::class);

		if ((DI::args()->get(1) == 'network')) {
			$notificationHeader = DI::l10n()->t('Network Notifications');
			$notifications      = [
				'ident'        => FormattedNotification::NETWORK,
				'notifications' => $factory->getNetworkList(self::$showAll, self::$firstItemNum, self::ITEMS_PER_PAGE),
			];
		} elseif ((DI::args()->get(1) == 'system')) {
			$notificationHeader = DI::l10n()->t('System Notifications');
			$notifications      = [
				'ident'        => FormattedNotification::SYSTEM,
				'notifications' => $factory->getSystemList(self::$showAll, self::$firstItemNum, self::ITEMS_PER_PAGE),
			];
		} elseif ((DI::args()->get(1) == 'personal')) {
			$notificationHeader = DI::l10n()->t('Personal Notifications');
			$notifications      = [
				'ident'        => FormattedNotification::PERSONAL,
				'notifications' => $factory->getPersonalList(self::$showAll, self::$firstItemNum, self::ITEMS_PER_PAGE),
			];
		} elseif ((DI::args()->get(1) == 'home')) {
			$notificationHeader = DI::l10n()->t('Home Notifications');
			$notifications      = [
				'ident'        => FormattedNotification::HOME,
				'notifications' => $factory->getHomeList(self::$showAll, self::$firstItemNum, self::ITEMS_PER_PAGE),
			];
		} else {
			DI::baseUrl()->redirect('notifications');
		}

		return [
			'header'        => $notificationHeader,
			'notifications' => $notifications,
		];
	}

	public static function content(array $parameters = [])
	{
		Nav::setSelected('notifications');

		$notificationContent   = [];
		$notificationNoContent = '';

		$notificationResult = self::getNotifications();
		$notifications      = $notificationResult['notifications'] ?? [];
		$notificationHeader = $notificationResult['header'] ?? '';

		if (!empty($notifications['notifications'])) {
			// Loop trough ever notification This creates an array with the output html for each
			// notification and apply the correct template according to the notificationtype (label).
			/** @var FormattedNotification $notification */
			foreach ($notifications['notifications'] as $notification) {
				$notification_templates = [
					'like'         => 'notifications/likes_item.tpl',
					'dislike'      => 'notifications/dislikes_item.tpl',
					'attend'       => 'notifications/attend_item.tpl',
					'attendno'     => 'notifications/attend_item.tpl',
					'attendmaybe'  => 'notifications/attend_item.tpl',
					'friend'       => 'notifications/friends_item.tpl',
					'comment'      => 'notifications/comments_item.tpl',
					'post'         => 'notifications/posts_item.tpl',
					'notification' => 'notifications/notification.tpl',
				];

				$notificationArray = $notification->toArray();

				$notificationTemplate = Renderer::getMarkupTemplate($notification_templates[$notificationArray['label']]);

				$notificationContent[] = Renderer::replaceMacros($notificationTemplate, [
					'$notification' => $notificationArray
				]);
			}
		} else {
			$notificationNoContent = DI::l10n()->t('No more %s notifications.', $notificationResult['ident']);
		}

		$notificationShowLink = [
			'href' => (self::$showAll ? 'notifications/' . $notifications['ident'] : 'notifications/' . $notifications['ident'] . '?show=all'),
			'text' => (self::$showAll ? DI::l10n()->t('Show unread') : DI::l10n()->t('Show all')),
		];

		return self::printContent($notificationHeader, $notificationContent, $notificationNoContent, $notificationShowLink);
	}
}
