<?php

namespace Friendica\Module\Notifications;

use Friendica\Content\ContactSelector;
use Friendica\Content\Nav;
use Friendica\Core\Protocol;
use Friendica\Core\Renderer;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Module\BaseNotifications;

/**
 * Prints notifications about introduction
 */
class Introductions extends BaseNotifications
{
	/**
	 * @inheritDoc
	 */
	public static function getNotifies()
	{
		$id  = (int)DI::args()->get(2, 0);
		$all = DI::args()->get(2) == 'all';

		$notifs = DI::notify()->getIntroList($all, self::$firstItemNum, self::ITEMS_PER_PAGE, $id);

		return [
			'header' => DI::l10n()->t('Notifications'),
			'notifs' => $notifs,
		];
	}

	public static function content(array $parameters = [])
	{
		Nav::setSelected('introductions');

		$all = DI::args()->get(2) == 'all';

		$notif_content   = [];
		$notif_nocontent = '';

		$notif_result = self::getNotifies();
		$notifs       = $notif_result['notifs'] ?? [];
		$notif_header = $notif_result['header'] ?? '';

		$sugg = Renderer::getMarkupTemplate('notifications/suggestions.tpl');
		$tpl  = Renderer::getMarkupTemplate('notifications/intros.tpl');

		// The link to switch between ignored and normal connection requests
		$notif_show_lnk = [
			'href' => (!$all ? 'notifications/intros/all' : 'notifications/intros'),
			'text' => (!$all ? DI::l10n()->t('Show Ignored Requests') : DI::l10n()->t('Hide Ignored Requests'))
		];

		// Loop through all introduction notifications.This creates an array with the output html for each
		// introduction
		foreach ($notifs['notifications'] as $notif) {

			// There are two kind of introduction. Contacts suggested by other contacts and normal connection requests.
			// We have to distinguish between these two because they use different data.
			switch ($notif['label']) {
				case 'friend_suggestion':
					$notif_content[] = Renderer::replaceMacros($sugg, [
						'$type'           => $notif['label'],
						'$str_notifytype' => DI::l10n()->t('Notification type:'),
						'$notify_type'    => $notif['notify_type'],
						'$intro_id'       => $notif['intro_id'],
						'$lbl_madeby'     => DI::l10n()->t('Suggested by:'),
						'$madeby'         => $notif['madeby'],
						'$madeby_url'     => $notif['madeby_url'],
						'$madeby_zrl'     => $notif['madeby_zrl'],
						'$madeby_addr'    => $notif['madeby_addr'],
						'$contact_id'     => $notif['contact_id'],
						'$photo'          => $notif['photo'],
						'$fullname'       => $notif['name'],
						'$url'            => $notif['url'],
						'$zrl'            => $notif['zrl'],
						'$lbl_url'        => DI::l10n()->t('Profile URL'),
						'$addr'           => $notif['addr'],
						'$hidden'         => ['hidden', DI::l10n()->t('Hide this contact from others'), ($notif['hidden'] == 1), ''],
						'$knowyou'        => $notif['knowyou'],
						'$approve'        => DI::l10n()->t('Approve'),
						'$note'           => $notif['note'],
						'$request'        => $notif['request'],
						'$ignore'         => DI::l10n()->t('Ignore'),
						'$discard'        => DI::l10n()->t('Discard'),
					]);
					break;

				// Normal connection requests
				default:
					$friend_selected = (($notif['network'] !== Protocol::OSTATUS) ? ' checked="checked" ' : ' disabled ');
					$fan_selected    = (($notif['network'] === Protocol::OSTATUS) ? ' checked="checked" disabled ' : '');

					$lbl_knowyou = '';
					$knowyou     = '';
					$helptext    = '';
					$helptext2   = '';
					$helptext3   = '';

					if ($notif['network'] === Protocol::DFRN) {
						$lbl_knowyou = DI::l10n()->t('Claims to be known to you: ');
						$knowyou     = (($notif['knowyou']) ? DI::l10n()->t('yes') : DI::l10n()->t('no'));
						$helptext    = DI::l10n()->t('Shall your connection be bidirectional or not?');
						$helptext2   = DI::l10n()->t('Accepting %s as a friend allows %s to subscribe to your posts, and you will also receive updates from them in your news feed.', $notif['name'], $notif['name']);
						$helptext3   = DI::l10n()->t('Accepting %s as a subscriber allows them to subscribe to your posts, but you will not receive updates from them in your news feed.', $notif['name']);
					} elseif ($notif['network'] === Protocol::DIASPORA) {
						$helptext  = DI::l10n()->t('Shall your connection be bidirectional or not?');
						$helptext2 = DI::l10n()->t('Accepting %s as a friend allows %s to subscribe to your posts, and you will also receive updates from them in your news feed.', $notif['name'], $notif['name']);
						$helptext3 = DI::l10n()->t('Accepting %s as a sharer allows them to subscribe to your posts, but you will not receive updates from them in your news feed.', $notif['name']);
					}

					$dfrn_tpl  = Renderer::getMarkupTemplate('notifications/netfriend.tpl');
					$dfrn_text = Renderer::replaceMacros($dfrn_tpl, [
						'$intro_id'        => $notif['intro_id'],
						'$friend_selected' => $friend_selected,
						'$fan_selected'    => $fan_selected,
						'$approve_as1'     => $helptext,
						'$approve_as2'     => $helptext2,
						'$approve_as3'     => $helptext3,
						'$as_friend'       => DI::l10n()->t('Friend'),
						'$as_fan'          => (($notif['network'] == Protocol::DIASPORA) ? DI::l10n()->t('Sharer') : DI::l10n()->t('Subscriber'))
					]);

					$contact = DBA::selectFirst('contact', ['network', 'protocol'], ['id' => $notif['contact_id']]);

					if (($contact['network'] != Protocol::DFRN) || ($contact['protocol'] == Protocol::ACTIVITYPUB)) {
						$action = 'follow_confirm';
					} else {
						$action = 'dfrn_confirm';
					}

					$header = $notif['name'];

					if ($notif['addr'] != '') {
						$header .= ' <' . $notif['addr'] . '>';
					}

					$header .= ' (' . ContactSelector::networkToName($notif['network'], $notif['url']) . ')';

					if ($notif['network'] != Protocol::DIASPORA) {
						$discard = DI::l10n()->t('Discard');
					} else {
						$discard = '';
					}

					$notif_content[] = Renderer::replaceMacros($tpl, [
						'$type'           => $notif['label'],
						'$header'         => $header,
						'$str_notifytype' => DI::l10n()->t('Notification type:'),
						'$notify_type'    => $notif['notify_type'],
						'$dfrn_text'      => $dfrn_text,
						'$dfrn_id'        => $notif['dfrn_id'],
						'$uid'            => $notif['uid'],
						'$intro_id'       => $notif['intro_id'],
						'$contact_id'     => $notif['contact_id'],
						'$photo'          => $notif['photo'],
						'$fullname'       => $notif['name'],
						'$location'       => $notif['location'],
						'$lbl_location'   => DI::l10n()->t('Location:'),
						'$about'          => $notif['about'],
						'$lbl_about'      => DI::l10n()->t('About:'),
						'$keywords'       => $notif['keywords'],
						'$lbl_keywords'   => DI::l10n()->t('Tags:'),
						'$gender'         => $notif['gender'],
						'$lbl_gender'     => DI::l10n()->t('Gender:'),
						'$hidden'         => ['hidden', DI::l10n()->t('Hide this contact from others'), ($notif['hidden'] == 1), ''],
						'$url'            => $notif['url'],
						'$zrl'            => $notif['zrl'],
						'$lbl_url'        => DI::l10n()->t('Profile URL'),
						'$addr'           => $notif['addr'],
						'$lbl_knowyou'    => $lbl_knowyou,
						'$lbl_network'    => DI::l10n()->t('Network:'),
						'$network'        => ContactSelector::networkToName($notif['network'], $notif['url']),
						'$knowyou'        => $knowyou,
						'$approve'        => DI::l10n()->t('Approve'),
						'$note'           => $notif['note'],
						'$ignore'         => DI::l10n()->t('Ignore'),
						'$discard'        => $discard,
						'$action'         => $action,
					]);
					break;
			}
		}

		if (count($notifs['notifications']) == 0) {
			info(DI::l10n()->t('No introductions.') . EOL);
			$notif_nocontent = DI::l10n()->t('No more %s notifications.', $notifs['ident']);
		}

		return self::printContent($notif_header, $notif_content, $notif_nocontent, $notif_show_lnk);
	}
}
