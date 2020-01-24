<?php

namespace Friendica\Module\Notifications;

use Friendica\Content\Nav;
use Friendica\Content\Pager;
use Friendica\Core\Renderer;
use Friendica\DI;
use Friendica\Module\BaseNotifications;

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
		$nm = DI::notification();

		$notificationHeader = '';

		// Get the network notifications
		if ((DI::args()->get(1) == 'network')) {
			$notificationHeader = DI::l10n()->t('Network Notifications');
			$notifications      = $nm->getNetworkList(self::$showAll, self::$firstItemNum, self::ITEMS_PER_PAGE);

			// Get the system notifications
		} elseif ((DI::args()->get(1) == 'system')) {
			$notificationHeader = DI::l10n()->t('System Notifications');
			$notifications      = $nm->getSystemList(self::$showAll, self::$firstItemNum, self::ITEMS_PER_PAGE);

			// Get the personal notifications
		} elseif ((DI::args()->get(1) == 'personal')) {
			$notificationHeader = DI::l10n()->t('Personal Notifications');
			$notifications      = $nm->getPersonalList(self::$showAll, self::$firstItemNum, self::ITEMS_PER_PAGE);

			// Get the home notifications
		} elseif ((DI::args()->get(1) == 'home')) {
			$notificationHeader = DI::l10n()->t('Home Notifications');
			$notifications      = $nm->getHomeList(self::$showAll, self::$firstItemNum, self::ITEMS_PER_PAGE);
			// fallback - redirect to main page
		} else {
			DI::baseUrl()->redirect('notifications');
		}

		// Set the pager
		$pager = new Pager(DI::args()->getQueryString(), self::ITEMS_PER_PAGE);

		// Add additional informations (needed for json output)
		$notifications['items_page'] = $pager->getItemsPerPage();
		$notifications['page']       = $pager->getPage();

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

				$notificationTemplate = Renderer::getMarkupTemplate($notification_templates[$notification['label']]);

				$notificationContent[] = Renderer::replaceMacros($notificationTemplate, [
					'$item_label' => $notification['label'],
					'$item_link'  => $notification['link'],
					'$item_image' => $notification['image'],
					'$item_url'   => $notification['url'],
					'$item_text'  => $notification['text'],
					'$item_when'  => $notification['when'],
					'$item_ago'   => $notification['ago'],
					'$item_seen'  => $notification['seen'],
				]);
			}
		} else {
			$notificationNoContent = DI::l10n()->t('No more %s notifications.', $notifications['ident']);
		}

		$notificationShowLink = [
			'href' => (self::$showAll ? 'notifications/' . $notifications['ident'] : 'notifications/' . $notifications['ident'] . '?show=all'),
			'text' => (self::$showAll ? DI::l10n()->t('Show unread') : DI::l10n()->t('Show all')),
		];

		return self::printContent($notificationHeader, $notificationContent, $notificationNoContent, $notificationShowLink);
	}
}
