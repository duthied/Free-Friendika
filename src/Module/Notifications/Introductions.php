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
	public static function getNotifications()
	{
		$id  = (int)DI::args()->get(2, 0);
		$all = DI::args()->get(2) == 'all';

		$notifications = DI::notification()->getIntroList($all, self::$firstItemNum, self::ITEMS_PER_PAGE, $id);

		return [
			'header'        => DI::l10n()->t('Notifications'),
			'notifications' => $notifications,
		];
	}

	public static function content(array $parameters = [])
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
			'text' => (!$all ? DI::l10n()->t('Show Ignored Requests') : DI::l10n()->t('Hide Ignored Requests'))
		];

		// Loop through all introduction notifications.This creates an array with the output html for each
		// introduction
		foreach ($notifications['notifications'] as $notification) {

			// There are two kind of introduction. Contacts suggested by other contacts and normal connection requests.
			// We have to distinguish between these two because they use different data.
			switch ($notification['label']) {
				case 'friend_suggestion':
					$notificationContent[] = Renderer::replaceMacros($notificationSuggestions, [
						'$type'           => $notification['label'],
						'str_notification_type' => DI::l10n()->t('Notification type:'),
						'str_type'    => $notification['str_type'],
						'$intro_id'       => $notification['intro_id'],
						'$lbl_madeby'     => DI::l10n()->t('Suggested by:'),
						'$madeby'         => $notification['madeby'],
						'$madeby_url'     => $notification['madeby_url'],
						'$madeby_zrl'     => $notification['madeby_zrl'],
						'$madeby_addr'    => $notification['madeby_addr'],
						'$contact_id'     => $notification['contact_id'],
						'$photo'          => $notification['photo'],
						'$fullname'       => $notification['name'],
						'$url'            => $notification['url'],
						'$zrl'            => $notification['zrl'],
						'$lbl_url'        => DI::l10n()->t('Profile URL'),
						'$addr'           => $notification['addr'],
						'$hidden'         => ['hidden', DI::l10n()->t('Hide this contact from others'), ($notification['hidden'] == 1), ''],
						'$knowyou'        => $notification['knowyou'],
						'$approve'        => DI::l10n()->t('Approve'),
						'$note'           => $notification['note'],
						'$request'        => $notification['request'],
						'$ignore'         => DI::l10n()->t('Ignore'),
						'$discard'        => DI::l10n()->t('Discard'),
					]);
					break;

				// Normal connection requests
				default:
					$friend_selected = (($notification['network'] !== Protocol::OSTATUS) ? ' checked="checked" ' : ' disabled ');
					$fan_selected    = (($notification['network'] === Protocol::OSTATUS) ? ' checked="checked" disabled ' : '');

					$lbl_knowyou = '';
					$knowyou     = '';
					$helptext    = '';
					$helptext2   = '';
					$helptext3   = '';

					if ($notification['network'] === Protocol::DFRN) {
						$lbl_knowyou = DI::l10n()->t('Claims to be known to you: ');
						$knowyou     = (($notification['knowyou']) ? DI::l10n()->t('yes') : DI::l10n()->t('no'));
						$helptext    = DI::l10n()->t('Shall your connection be bidirectional or not?');
						$helptext2   = DI::l10n()->t('Accepting %s as a friend allows %s to subscribe to your posts, and you will also receive updates from them in your news feed.', $notification['name'], $notification['name']);
						$helptext3   = DI::l10n()->t('Accepting %s as a subscriber allows them to subscribe to your posts, but you will not receive updates from them in your news feed.', $notification['name']);
					} elseif ($notification['network'] === Protocol::DIASPORA) {
						$helptext  = DI::l10n()->t('Shall your connection be bidirectional or not?');
						$helptext2 = DI::l10n()->t('Accepting %s as a friend allows %s to subscribe to your posts, and you will also receive updates from them in your news feed.', $notification['name'], $notification['name']);
						$helptext3 = DI::l10n()->t('Accepting %s as a sharer allows them to subscribe to your posts, but you will not receive updates from them in your news feed.', $notification['name']);
					}

					$dfrn_tpl  = Renderer::getMarkupTemplate('notifications/netfriend.tpl');
					$dfrn_text = Renderer::replaceMacros($dfrn_tpl, [
						'$intro_id'        => $notification['intro_id'],
						'$friend_selected' => $friend_selected,
						'$fan_selected'    => $fan_selected,
						'$approve_as1'     => $helptext,
						'$approve_as2'     => $helptext2,
						'$approve_as3'     => $helptext3,
						'$as_friend'       => DI::l10n()->t('Friend'),
						'$as_fan'          => (($notification['network'] == Protocol::DIASPORA) ? DI::l10n()->t('Sharer') : DI::l10n()->t('Subscriber'))
					]);

					$contact = DBA::selectFirst('contact', ['network', 'protocol'], ['id' => $notification['contact_id']]);

					if (($contact['network'] != Protocol::DFRN) || ($contact['protocol'] == Protocol::ACTIVITYPUB)) {
						$action = 'follow_confirm';
					} else {
						$action = 'dfrn_confirm';
					}

					$header = $notification['name'];

					if ($notification['addr'] != '') {
						$header .= ' <' . $notification['addr'] . '>';
					}

					$header .= ' (' . ContactSelector::networkToName($notification['network'], $notification['url']) . ')';

					if ($notification['network'] != Protocol::DIASPORA) {
						$discard = DI::l10n()->t('Discard');
					} else {
						$discard = '';
					}

					$notificationContent[] = Renderer::replaceMacros($notificationTemplate, [
						'$type'           => $notification['label'],
						'$header'         => $header,
						'str_notification_type' => DI::l10n()->t('Notification type:'),
						'str_type'    => $notification['notifytype'],
						'$dfrn_text'      => $dfrn_text,
						'$dfrn_id'        => $notification['dfrn_id'],
						'$uid'            => $notification['uid'],
						'$intro_id'       => $notification['intro_id'],
						'$contact_id'     => $notification['contact_id'],
						'$photo'          => $notification['photo'],
						'$fullname'       => $notification['name'],
						'$location'       => $notification['location'],
						'$lbl_location'   => DI::l10n()->t('Location:'),
						'$about'          => $notification['about'],
						'$lbl_about'      => DI::l10n()->t('About:'),
						'$keywords'       => $notification['keywords'],
						'$lbl_keywords'   => DI::l10n()->t('Tags:'),
						'$gender'         => $notification['gender'],
						'$lbl_gender'     => DI::l10n()->t('Gender:'),
						'$hidden'         => ['hidden', DI::l10n()->t('Hide this contact from others'), ($notification['hidden'] == 1), ''],
						'$url'            => $notification['url'],
						'$zrl'            => $notification['zrl'],
						'$lbl_url'        => DI::l10n()->t('Profile URL'),
						'$addr'           => $notification['addr'],
						'$lbl_knowyou'    => $lbl_knowyou,
						'$lbl_network'    => DI::l10n()->t('Network:'),
						'$network'        => ContactSelector::networkToName($notification['network'], $notification['url']),
						'$knowyou'        => $knowyou,
						'$approve'        => DI::l10n()->t('Approve'),
						'$note'           => $notification['note'],
						'$ignore'         => DI::l10n()->t('Ignore'),
						'$discard'        => $discard,
						'$action'         => $action,
					]);
					break;
			}
		}

		if (count($notifications['notifications']) == 0) {
			info(DI::l10n()->t('No introductions.') . EOL);
			$notificationNoContent = DI::l10n()->t('No more %s notifications.', $notifications['ident']);
		}

		return self::printContent($notificationHeader, $notificationContent, $notificationNoContent, $notificationShowLink);
	}
}
