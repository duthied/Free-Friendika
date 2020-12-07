<?php
/**
 * @copyright Copyright (C) 2020, Friendica
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

use Friendica\App;
use Friendica\Core\Logger;
use Friendica\Core\Renderer;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Mail;
use Friendica\Model\Profile;
use Friendica\Util\Strings;

function wallmessage_post(App $a) {

	$replyto = Profile::getMyURL();
	if (!$replyto) {
		notice(DI::l10n()->t('Permission denied.') . EOL);
		return;
	}

	$subject   = (!empty($_REQUEST['subject'])   ? Strings::escapeTags(trim($_REQUEST['subject']))   : '');
	$body      = (!empty($_REQUEST['body'])      ? Strings::escapeHtml(trim($_REQUEST['body'])) : '');

	$recipient = (($a->argc > 1) ? Strings::escapeTags($a->argv[1]) : '');
	if ((! $recipient) || (! $body)) {
		return;
	}

	$r = q("select * from user where nickname = '%s' limit 1",
		DBA::escape($recipient)
	);

	if (! DBA::isResult($r)) {
		Logger::log('wallmessage: no recipient');
		return;
	}

	$user = $r[0];

	if (! intval($user['unkmail'])) {
		notice(DI::l10n()->t('Permission denied.') . EOL);
		return;
	}

	$r = q("select count(*) as total from mail where uid = %d and created > UTC_TIMESTAMP() - INTERVAL 1 day and unknown = 1",
			intval($user['uid'])
	);

	if ($r[0]['total'] > $user['cntunkmail']) {
		notice(DI::l10n()->t('Number of daily wall messages for %s exceeded. Message failed.', $user['username']));
		return;
	}

	$ret = Mail::sendWall($user, $body, $subject, $replyto);

	switch ($ret) {
		case -1:
			notice(DI::l10n()->t('No recipient selected.') . EOL);
			break;
		case -2:
			notice(DI::l10n()->t('Unable to check your home location.') . EOL);
			break;
		case -3:
			notice(DI::l10n()->t('Message could not be sent.') . EOL);
			break;
		case -4:
			notice(DI::l10n()->t('Message collection failure.') . EOL);
			break;
		default:
			info(DI::l10n()->t('Message sent.') . EOL);
	}

	DI::baseUrl()->redirect('profile/'.$user['nickname']);
}


function wallmessage_content(App $a) {

	if (!Profile::getMyURL()) {
		notice(DI::l10n()->t('Permission denied.') . EOL);
		return;
	}

	$recipient = (($a->argc > 1) ? $a->argv[1] : '');

	if (!$recipient) {
		notice(DI::l10n()->t('No recipient.') . EOL);
		return;
	}

	$r = q("select * from user where nickname = '%s' limit 1",
		DBA::escape($recipient)
	);

	if (! DBA::isResult($r)) {
		notice(DI::l10n()->t('No recipient.') . EOL);
		Logger::log('wallmessage: no recipient');
		return;
	}

	$user = $r[0];

	if (!intval($user['unkmail'])) {
		notice(DI::l10n()->t('Permission denied.') . EOL);
		return;
	}

	$r = q("select count(*) as total from mail where uid = %d and created > UTC_TIMESTAMP() - INTERVAL 1 day and unknown = 1",
			intval($user['uid'])
	);

	if ($r[0]['total'] > $user['cntunkmail']) {
		notice(DI::l10n()->t('Number of daily wall messages for %s exceeded. Message failed.', $user['username']));
		return;
	}

	$tpl = Renderer::getMarkupTemplate('wallmsg-header.tpl');
	DI::page()['htmlhead'] .= Renderer::replaceMacros($tpl, [
		'$baseurl' => DI::baseUrl()->get(true),
		'$nickname' => $user['nickname'],
		'$linkurl' => DI::l10n()->t('Please enter a link URL:')
	]);

	$tpl = Renderer::getMarkupTemplate('wallmessage.tpl');
	$o = Renderer::replaceMacros($tpl, [
		'$header'     => DI::l10n()->t('Send Private Message'),
		'$subheader'  => DI::l10n()->t('If you wish for %s to respond, please check that the privacy settings on your site allow private mail from unknown senders.', $user['username']),
		'$to'         => DI::l10n()->t('To:'),
		'$subject'    => DI::l10n()->t('Subject:'),
		'$recipname'  => $user['username'],
		'$nickname'   => $user['nickname'],
		'$subjtxt'    => $_REQUEST['subject'] ?? '',
		'$text'       => $_REQUEST['body'] ?? '',
		'$readonly'   => '',
		'$yourmessage'=> DI::l10n()->t('Your message:'),
		'$parent'     => '',
		'$upload'     => DI::l10n()->t('Upload photo'),
		'$insert'     => DI::l10n()->t('Insert web link'),
		'$wait'       => DI::l10n()->t('Please wait')
	]);

	return $o;
}
