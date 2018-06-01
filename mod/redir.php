<?php

use Friendica\App;
use Friendica\Core\L10n;
use Friendica\Core\System;
use Friendica\Database\DBM;
use Friendica\Model\Contact;
use Friendica\Model\Profile;

function redir_init(App $a) {

	$url = defaults($_GET, 'url', '');
	$quiet = !empty($_GET['quiet']) ? '&quiet=1' : '';
	$con_url = defaults($_GET, 'conurl', '');

	if (local_user() && ($a->argc > 1) && intval($a->argv[1])) {
		$cid = intval($a->argv[1]);
	} elseif (local_user() && !empty($con_url)) {
		$cid = Contact::getIdForURL($con_url, local_user());
	} else {
		$cid = 0;
	}

	if (!empty($cid)) {
		$fields = ['id', 'uid', 'nurl', 'url', 'name', 'network', 'poll', 'issued-id', 'dfrn-id', 'duplex'];
		$contact = dba::selectFirst('contact', $fields, ['id' => $cid, 'uid' => [0, local_user()]]);
		if (!DBM::is_result($contact)) {
			notice(L10n::t('Contact not found.'));
			goaway(System::baseUrl());
		}

		if ($contact['network'] !== NETWORK_DFRN) {
			goaway(($url != '' ? $url : $contact['url']));
		}

		if ($contact['uid'] == 0) {
			$contact_url = $contact['url'];
			$contact = dba::selectFirst('contact', $fields, ['nurl' => $contact['nurl'], 'uid' => local_user()]);
			if (!DBM::is_result($contact)) {
				$target_url = ($url != '' ? $url : $contact_url);

				$my_profile = Profile::getMyURL();

				if (!empty($my_profile) && !link_compare($my_profile, $target_url)) {
					$separator = strpos($target_url, '?') ? '&' : '?';

					$target_url .= $separator . 'zrl=' . urlencode($my_profile);
				}
				goaway($target_url);
			} else {
				$cid = $contact['id'];
			}
		}

		$dfrn_id = $orig_id = (($contact['issued-id']) ? $contact['issued-id'] : $contact['dfrn-id']);

		if ($contact['duplex'] && $contact['issued-id']) {
			$orig_id = $contact['issued-id'];
			$dfrn_id = '1:' . $orig_id;
		}
		if ($contact['duplex'] && $contact['dfrn-id']) {
			$orig_id = $contact['dfrn-id'];
			$dfrn_id = '0:' . $orig_id;
		}

		$sec = random_string();

		$fields = ['uid' => local_user(), 'cid' => $cid, 'dfrn_id' => $dfrn_id,
			'sec' => $sec, 'expire' => time() + 45];
		dba::insert('profile_check', $fields);

		logger('mod_redir: ' . $contact['name'] . ' ' . $sec, LOGGER_DEBUG);

		$dest = (!empty($url) ? '&destination_url=' . $url : '');

		goaway($contact['poll'] . '?dfrn_id=' . $dfrn_id
			. '&dfrn_version=' . DFRN_PROTOCOL_VERSION . '&type=profile&sec=' . $sec . $dest . $quiet);
	}

	if (local_user()) {
		$handle = $a->user['nickname'] . '@' . substr(System::baseUrl(), strpos(System::baseUrl(), '://') + 3);
	}
	if (remote_user()) {
		$handle = $_SESSION['handle'];
	}

	if (!empty($url)) {
		$url = str_replace('{zid}', '&zid=' . $handle, $url);
		goaway($url);
	}

	notice(L10n::t('Contact not found.'));
	goaway(System::baseUrl());
}
