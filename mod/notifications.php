<?php
include_once("include/NotificationsManager.php");
include_once("include/bbcode.php");
include_once("include/contact_selectors.php");
include_once("include/Scrape.php");

function notifications_post(&$a) {

	if(! local_user()) {
		goaway(z_root());
	}

	$request_id = (($a->argc > 1) ? $a->argv[1] : 0);

	if($request_id === "all")
		return;

	if($request_id) {

		$r = q("SELECT * FROM `intro` WHERE `id` = %d  AND `uid` = %d LIMIT 1",
			intval($request_id),
			intval(local_user())
		);

		if(count($r)) {
			$intro_id = $r[0]['id'];
			$contact_id = $r[0]['contact-id'];
		}
		else {
			notice( t('Invalid request identifier.') . EOL);
			return;
		}

		// If it is a friend suggestion, the contact is not a new friend but an existing friend
		// that should not be deleted.

		$fid = $r[0]['fid'];

		if($_POST['submit'] == t('Discard')) {
			$r = q("DELETE FROM `intro` WHERE `id` = %d",
				intval($intro_id)
			);
			if(! $fid) {

				// The check for blocked and pending is in case the friendship was already approved
				// and we just want to get rid of the now pointless notification

				$r = q("DELETE FROM `contact` WHERE `id` = %d AND `uid` = %d AND `self` = 0 AND `blocked` = 1 AND `pending` = 1",
					intval($contact_id),
					intval(local_user())
				);
			}
			goaway('notifications/intros');
		}
		if($_POST['submit'] == t('Ignore')) {
			$r = q("UPDATE `intro` SET `ignore` = 1 WHERE `id` = %d",
				intval($intro_id));
			goaway('notifications/intros');
		}
	}
}

function notifications_content(&$a) {

	if(! local_user()) {
		notice( t('Permission denied.') . EOL);
		return;
	}

	$page	=	(x($_REQUEST,'page')		? $_REQUEST['page']		: 1);
	$show	=	(x($_REQUEST,'show')		? $_REQUEST['show']		: 0);

	nav_set_selected('notifications');

	$json = (($a->argc > 1 && $a->argv[$a->argc - 1] === 'json') ? true : false);

	$nm = new NotificationsManager();

	$o = '';
	// get the nav tabs for the notification pages
	$tabs = $nm->getTabs();
	$notif_content = array();
	$perpage = 20;
	$startrec = ($page * $perpage) - $perpage;

	if( (($a->argc > 1) && ($a->argv[1] == 'intros')) || (($a->argc == 1))) {
		nav_set_selected('introductions');

		if(($a->argc > 2) && ($a->argv[2] == 'all'))
			$sql_extra = '';
		else
			$sql_extra = " AND `ignore` = 0 ";

		$notif_header = t('Notifications');
		$notif_tpl = get_markup_template('notifications.tpl');


		$notif_show_lnk = array(
			'href' => ((strlen($sql_extra)) ? 'notifications/intros/all' : 'notifications/intros' ),
			'text' => ((strlen($sql_extra)) ? t('Show Ignored Requests') : t('Hide Ignored Requests')),
			'id' => "notifications-show-hide-link",
		);

		$r = q("SELECT COUNT(*) AS `total` FROM `intro`
			WHERE `intro`.`uid` = %d $sql_extra AND `intro`.`blocked` = 0 ",
				intval($_SESSION['uid'])
		);
		if(dbm::is_result($r)) {
			$a->set_pager_total($r[0]['total']);
			$a->set_pager_itemspage($perpage);
		}

		/// @todo Fetch contact details by "get_contact_details_by_url" instead of queries to contact, fcontact and gcontact

		$r = q("SELECT `intro`.`id` AS `intro_id`, `intro`.*, `contact`.*, `fcontact`.`name` AS `fname`,`fcontact`.`url` AS `furl`,`fcontact`.`photo` AS `fphoto`,`fcontact`.`request` AS `frequest`,
				`gcontact`.`location` AS `glocation`, `gcontact`.`about` AS `gabout`,
				`gcontact`.`keywords` AS `gkeywords`, `gcontact`.`gender` AS `ggender`,
				`gcontact`.`network` AS `gnetwork`
			FROM `intro`
				LEFT JOIN `contact` ON `contact`.`id` = `intro`.`contact-id`
				LEFT JOIN `gcontact` ON `gcontact`.`nurl` = `contact`.`nurl`
				LEFT JOIN `fcontact` ON `intro`.`fid` = `fcontact`.`id`
			WHERE `intro`.`uid` = %d $sql_extra AND `intro`.`blocked` = 0 ",
				intval($_SESSION['uid']));

		if(dbm::is_result($r)) {

			$sugg = get_markup_template('suggestions.tpl');
			$tpl = get_markup_template("intros.tpl");

			foreach($r as $rr) {

				if($rr['fid']) {

					$return_addr = bin2hex($a->user['nickname'] . '@' . $a->get_hostname() . (($a->path) ? '/' . $a->path : ''));

					$notif_content[] = replace_macros($sugg, array(
						'$str_notifytype' => t('Notification type: '),
						'$notify_type' => t('Friend Suggestion'),
						'$intro_id' => $rr['intro_id'],
						'$madeby' => sprintf( t('suggested by %s'),$rr['name']),
						'$contact_id' => $rr['contact-id'],
						'$photo' => ((x($rr,'fphoto')) ? proxy_url($rr['fphoto'], false, PROXY_SIZE_SMALL) : "images/person-175.jpg"),
						'$fullname' => $rr['fname'],
						'$url' => zrl($rr['furl']),
						'$hidden' => array('hidden', t('Hide this contact from others'), ($rr['hidden'] == 1), ''),
						'$activity' => array('activity', t('Post a new friend activity'), (intval(get_pconfig(local_user(),'system','post_newfriend')) ? '1' : 0), t('if applicable')),

						'$knowyou' => $knowyou,
						'$approve' => t('Approve'),
						'$note' => $rr['note'],
						'$request' => $rr['frequest'] . '?addr=' . $return_addr,
						'$ignore' => t('Ignore'),
						'$discard' => t('Discard'),

					));

					continue;

				}
				$friend_selected = (($rr['network'] !== NETWORK_OSTATUS) ? ' checked="checked" ' : ' disabled ');
				$fan_selected = (($rr['network'] === NETWORK_OSTATUS) ? ' checked="checked" disabled ' : '');
				$dfrn_tpl = get_markup_template('netfriend.tpl');

				$knowyou   = '';
				$dfrn_text = '';

				if($rr['network'] === NETWORK_DFRN || $rr['network'] === NETWORK_DIASPORA) {
					if($rr['network'] === NETWORK_DFRN) {
						$lbl_knowyou = t('Claims to be known to you: ');
						$knowyou = (($rr['knowyou']) ? t('yes') : t('no'));
						$helptext = t('Shall your connection be bidirectional or not? "Friend" implies that you allow to read and you subscribe to their posts. "Fan/Admirer" means that you allow to read but you do not want to read theirs. Approve as: ');
					} else {
						$knowyou = '';
						$helptext = t('Shall your connection be bidirectional or not? "Friend" implies that you allow to read and you subscribe to their posts. "Sharer" means that you allow to read but you do not want to read theirs. Approve as: ');
					}

					$dfrn_text = replace_macros($dfrn_tpl,array(
						'$intro_id' => $rr['intro_id'],
						'$friend_selected' => $friend_selected,
						'$fan_selected' => $fan_selected,
						'$approve_as' => $helptext,
						'$as_friend' => t('Friend'),
						'$as_fan' => (($rr['network'] == NETWORK_DIASPORA) ? t('Sharer') : t('Fan/Admirer'))
					));
				}

				$header = $rr["name"];

				$ret = probe_url($rr["url"]);

				if ($rr['gnetwork'] == "")
					$rr['gnetwork'] = $ret["network"];

				if ($ret["addr"] != "")
					$header .= " <".$ret["addr"].">";

				$header .= " (".network_to_name($rr['gnetwork'], $rr['url']).")";

				// Don't show these data until you are connected. Diaspora is doing the same.
				if($rr['gnetwork'] === NETWORK_DIASPORA) {
					$rr['glocation'] = "";
					$rr['gabout'] = "";
					$rr['ggender'] = "";
				}

				$notif_content[] = replace_macros($tpl, array(
					'$header' => htmlentities($header),
					'$str_notifytype' => t('Notification type: '),
					'$notify_type' => (($rr['network'] !== NETWORK_OSTATUS) ? t('Friend/Connect Request') : t('New Follower')),
					'$dfrn_text' => $dfrn_text,
					'$dfrn_id' => $rr['issued-id'],
					'$uid' => $_SESSION['uid'],
					'$intro_id' => $rr['intro_id'],
					'$contact_id' => $rr['contact-id'],
					'$photo' => ((x($rr,'photo')) ? proxy_url($rr['photo'], false, PROXY_SIZE_SMALL) : "images/person-175.jpg"),
					'$fullname' => $rr['name'],
					'$location' => bbcode($rr['glocation'], false, false),
					'$location_label' => t('Location:'),
					'$about' => bbcode($rr['gabout'], false, false),
					'$about_label' => t('About:'),
					'$keywords' => $rr['gkeywords'],
					'$keywords_label' => t('Tags:'),
					'$gender' => $rr['ggender'],
					'$gender_label' => t('Gender:'),
					'$hidden' => array('hidden', t('Hide this contact from others'), ($rr['hidden'] == 1), ''),
					'$activity' => array('activity', t('Post a new friend activity'), (intval(get_pconfig(local_user(),'system','post_newfriend')) ? '1' : 0), t('if applicable')),
					'$url' => $rr['url'],
					'$zrl' => zrl($rr['url']),
					'$url_label' => t('Profile URL'),
					'$addr' => $rr['addr'],
					'$lbl_knowyou' => $lbl_knowyou,
					'$lbl_network' => t('Network:'),
					'$network' => network_to_name($rr['gnetwork'], $rr['url']),
					'$knowyou' => $knowyou,
					'$approve' => t('Approve'),
					'$note' => $rr['note'],
					'$ignore' => t('Ignore'),
					'$discard' => t('Discard'),

				));
			}
		}
		else
			info( t('No introductions.') . EOL);

	} else if (($a->argc > 1) && ($a->argv[1] == 'network')) {

		$notif_header = t('Network Notifications');
		$notif_tpl = get_markup_template('notifications.tpl');

		$notifs = $nm->networkNotifs($show, $startrec, $perpage);

		$notif_show_lnk = array(
			'href' => ($show ? 'notifications/network' : 'notifications/network?show=all' ),
			'text' => ($show ? t('Show unread') : t('Show all')),
		);

		if(!dbm::is_result($notifs)) {
			if($show)
				$notif_show_lnk = array();

			$notif_nocontent = t('No more network notifications.');
		}

	} else if (($a->argc > 1) && ($a->argv[1] == 'system')) {

		$notif_header = t('System Notifications');
		$notif_tpl = get_markup_template('notifications.tpl');

		$notifs = $nm->systemNotifs($show, $startrec, $perpage);

		$notif_show_lnk = array(
			'href' => ($show ? 'notifications/system' : 'notifications/system?show=all' ),
			'text' => ($show ? t('Show unread') : t('Show all')),
		);

		if(!dbm::is_result($notifs)) {
			if($show)
				$notif_show_lnk = array();

			$notif_nocontent = t('No more system notifications.');
		}

	} else if (($a->argc > 1) && ($a->argv[1] == 'personal')) {

		$notif_header = t('Personal Notifications');
		$notif_tpl = get_markup_template('notifications.tpl');

		$notifs = $nm->personalNotifs($show, $startrec, $perpage);

		$notif_show_lnk = array(
			'href' => ($show ? 'notifications/personal' : 'notifications/personal?show=all' ),
			'text' => ($show ? t('Show unread') : t('Show all')),
		);

		if(!dbm::is_result($notifs)) {
			if($show)
				$notif_show_lnk = array();

			$notif_nocontent = t('No more personal notifications.');
		}

	} else if (($a->argc > 1) && ($a->argv[1] == 'home')) {

		$notif_header = t('Home Notifications');
		$notif_tpl = get_markup_template('notifications.tpl');

		$notifs = $nm->homeNotifs($show, $startrec, $perpage);

		$notif_show_lnk = array(
			'href' => ($show ? 'notifications/home' : 'notifications/home?show=all' ),
			'text' => ($show ? t('Show unread') : t('Show all')),
		);

		if(!dbm::is_result($notifs)) {
			if($show)
				$notif_show_lnk = array();

			$notif_nocontent = t('No more home notifications.');
		}

	}

	if(count($notifs['notifications']) > 0 ) {
		// set the pager
		$a->set_pager_total($notifs['total']);
		$a->set_pager_itemspage($perpage);

		// add additional informations (needed for json output)
		$notifs['items_page'] = $a->pager['itemspage'];
		$notifs['page'] = $a->pager['page'];

		// The template files we need in different cases for formatting the content
		$tpl_item_like = 'notifications_likes_item.tpl';
		$tpl_item_dislike = 'notifications_dislikes_item.tpl';
		$tpl_item_attend = 'notifications_attend_item.tpl';
		$tpl_item_attendno = 'notifications_attend_item.tpl';
		$tpl_item_attendmaybe = 'notifications_attend_item.tpl';
		$tpl_item_friend = 'notifications_friends_item.tpl';
		$tpl_item_comment = 'notifications_comments_item.tpl';
		$tpl_item_post = 'notifications_posts_item.tpl';
		$tpl_item_notify = 'notify.tpl';

		foreach ($notifs['notifications'] as $it) {
			$tplname = 'tpl_item_'.$it['label'];
			$templ = get_markup_template($$tplname);

			$notif_content[] = replace_macros($templ,array(
				'$item_label' => $it['label'],
				'$item_link' => $it['link'],
				'$item_image' => $it['image'],
				'$item_text' => $it['text'],
				'$item_when' => $it['when'],
				'$item_seen' => $it['seen'],
			));
		}

	}

	$o .= replace_macros($notif_tpl, array(
		'$notif_header' => $notif_header,
		'$tabs' => $tabs,
		'$notif_content' => $notif_content,
		'$notif_nocontent' => $notif_nocontent,
		'$notif_show_lnk' => $notif_show_lnk,
		'$notif_paginate' => paginate($a)
	));

	return $o;
}
