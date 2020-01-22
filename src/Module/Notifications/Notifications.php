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
	public static function getNotifies()
	{
		$nm = DI::notify();

		$notif_header = '';

		// Get the network notifications
		if ((DI::args()->get(1) == 'network')) {
			$notif_header = DI::l10n()->t('Network Notifications');
			$notifs       = $nm->getNetworkList(self::$show, self::$start, self::PER_PAGE);

			// Get the system notifications
		} elseif ((DI::args()->get(1) == 'system')) {
			$notif_header = DI::l10n()->t('System Notifications');
			$notifs       = $nm->getSystemList(self::$show, self::$start, self::PER_PAGE);

			// Get the personal notifications
		} elseif ((DI::args()->get(1) == 'personal')) {
			$notif_header = DI::l10n()->t('Personal Notifications');
			$notifs       = $nm->getPersonalList(self::$show, self::$start, self::PER_PAGE);

			// Get the home notifications
		} elseif ((DI::args()->get(1) == 'home')) {
			$notif_header = DI::l10n()->t('Home Notifications');
			$notifs       = $nm->getHomeList(self::$show, self::$start, self::PER_PAGE);
			// fallback - redirect to main page
		} else {
			DI::baseUrl()->redirect('notifications');
		}

		// Set the pager
		$pager = new Pager(DI::args()->getQueryString(), self::PER_PAGE);

		// Add additional informations (needed for json output)
		$notifs['items_page'] = $pager->getItemsPerPage();
		$notifs['page']       = $pager->getPage();

		return [
			'header' => $notif_header,
			'notifs' => $notifs,
		];
	}

	public static function content(array $parameters = [])
	{
		Nav::setSelected('notifications');

		$notif_content   = [];
		$notif_nocontent = '';

		$notif_result = self::getNotifies();
		$notifs       = $notif_result['notifs'] ?? [];
		$notif_header = $notif_result['header'] ?? '';


		if (!empty($notifs['notifications'])) {
			// Loop trough ever notification This creates an array with the output html for each
			// notification and apply the correct template according to the notificationtype (label).
			foreach ($notifs['notifications'] as $notif) {
				$notification_templates = [
					'like'        => 'notifications_likes_item.tpl',
					'dislike'     => 'notifications_dislikes_item.tpl',
					'attend'      => 'notifications_attend_item.tpl',
					'attendno'    => 'notifications_attend_item.tpl',
					'attendmaybe' => 'notifications_attend_item.tpl',
					'friend'      => 'notifications_friends_item.tpl',
					'comment'     => 'notifications_comments_item.tpl',
					'post'        => 'notifications_posts_item.tpl',
					'notify'      => 'notify.tpl',
				];

				$tpl_notif = Renderer::getMarkupTemplate($notification_templates[$notif['label']]);

				$notif_content[] = Renderer::replaceMacros($tpl_notif, [
					'$item_label' => $notif['label'],
					'$item_link'  => $notif['link'],
					'$item_image' => $notif['image'],
					'$item_url'   => $notif['url'],
					'$item_text'  => $notif['text'],
					'$item_when'  => $notif['when'],
					'$item_ago'   => $notif['ago'],
					'$item_seen'  => $notif['seen'],
				]);
			}
		} else {
			$notif_nocontent = DI::l10n()->t('No more %s notifications.', $notifs['ident']);
		}

		$notif_show_lnk = [
			'href' => (self::$show ? 'notifications/' . $notifs['ident'] : 'notifications/' . $notifs['ident'] . '?show=all'),
			'text' => (self::$show ? DI::l10n()->t('Show unread') : DI::l10n()->t('Show all')),
		];

		return self::printContent($notif_header, $notif_content, $notif_nocontent, $notif_show_lnk);
	}
}
