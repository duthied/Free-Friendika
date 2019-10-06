<?php
/**
 * @file mod/manage.php
 */

use Friendica\App;
use Friendica\Core\Hook;
use Friendica\Core\L10n;
use Friendica\Core\Renderer;
use Friendica\Core\Session;
use Friendica\Database\DBA;

function manage_post(App $a) {

	if (!local_user()) {
		return;
	}

	$uid = local_user();
	$orig_record = $a->user;

	if(!empty($_SESSION['submanage'])) {
		$user = DBA::selectFirst('user', [], ['uid' => $_SESSION['submanage']]);
		if (DBA::isResult($user)) {
			$uid = intval($user['uid']);
			$orig_record = $user;
		}
	}

	$identity = (!empty($_POST['identity']) ? intval($_POST['identity']) : 0);
	if (!$identity) {
		return;
	}

	$limited_id = 0;
	$original_id = $uid;

	$manage = DBA::select('manage', ['mid'], ['uid' => $uid]);
	while ($m = DBA::fetch($manage)) {
		if ($identity == $m['mid']) {
			$limited_id = $m['mid'];
			break;
		}
	}
	DBA::close($manage);

	if ($limited_id) {
		$user = DBA::selectFirst('user', [], ['uid' => $limited_id]);
	} else {
		// Check if the target user is one of our children
		$user = DBA::selectFirst('user', [], ['uid' => $identity, 'parent-uid' => $orig_record['uid']]);

		// Check if the target user is one of our siblings
		if (!DBA::isResult($user) && ($orig_record['parent-uid'] != 0)) {
			$user = DBA::selectFirst('user', [], ['uid' => $identity, 'parent-uid' => $orig_record['parent-uid']]);
		}

		// Check if it's our parent
		if (!DBA::isResult($user) && ($orig_record['parent-uid'] != 0) && ($orig_record['parent-uid'] == $identity)) {
			$user = DBA::selectFirst('user', [], ['uid' => $identity]);
		}

		// Finally check if it's out own user
		if (!DBA::isResult($user) && ($orig_record['uid'] != 0) && ($orig_record['uid'] == $identity)) {
			$user = DBA::selectFirst('user', [], ['uid' => $identity]);
		}

	}

	if (!DBA::isResult($user)) {
		return;
	}

	Session::clear();

	Session::setAuthenticatedForUser($a, $user, true, true);

	if ($limited_id) {
		$_SESSION['submanage'] = $original_id;
	}

	$ret = [];
	Hook::callAll('home_init', $ret);

	$a->internalRedirect('profile/' . $a->user['nickname']);
	// NOTREACHED
}

function manage_content(App $a) {

	if (!local_user()) {
		notice(L10n::t('Permission denied.') . EOL);
		return;
	}

	if (!empty($_GET['identity'])) {
		$_POST['identity'] = $_GET['identity'];
		manage_post($a);
		return;
	}

	$identities = $a->identities;

	//getting additinal information for each identity
	foreach ($identities as $key => $id) {
		$thumb = DBA::selectFirst('contact', ['thumb'], ['uid' => $id['uid'] , 'self' => true]);
		if (!DBA::isResult($thumb)) {
			continue;
		}

		$identities[$key]['thumb'] = $thumb['thumb'];

		$identities[$key]['selected'] = ($id['nickname'] === $a->user['nickname']);

		$condition = ["`uid` = ? AND `msg` != '' AND NOT (`type` IN (?, ?)) AND NOT `seen`", $id['uid'], NOTIFY_INTRO, NOTIFY_MAIL];
		$params = ['distinct' => true, 'expression' => 'parent'];
		$notifications = DBA::count('notify', $condition, $params);

		$params = ['distinct' => true, 'expression' => 'convid'];
		$notifications += DBA::count('mail', ['uid' => $id['uid'], 'seen' => false], $params);

		$notifications += DBA::count('intro', ['blocked' => false, 'ignore' => false, 'uid' => $id['uid']]);

		$identities[$key]['notifications'] = $notifications;
	}

	$o = Renderer::replaceMacros(Renderer::getMarkupTemplate('manage.tpl'), [
		'$title' => L10n::t('Manage Identities and/or Pages'),
		'$desc' => L10n::t('Toggle between different identities or community/group pages which share your account details or which you have been granted "manage" permissions'),
		'$choose' => L10n::t('Select an identity to manage: '),
		'$identities' => $identities,
		'$submit' => L10n::t('Submit'),
	]);

	return $o;

}
