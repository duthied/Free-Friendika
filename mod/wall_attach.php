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
use Friendica\Core\Session;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Attach;
use Friendica\Model\User;
use Friendica\Util\Strings;

function wall_attach_post(App $a) {

	$r_json = (!empty($_GET['response']) && $_GET['response']=='json');

	if ($a->argc > 1) {
		$nick = $a->argv[1];
		$r = q("SELECT `user`.*, `contact`.`id` FROM `user` LEFT JOIN `contact` on `user`.`uid` = `contact`.`uid`  WHERE `user`.`nickname` = '%s' AND `user`.`blocked` = 0 and `contact`.`self` = 1 LIMIT 1",
			DBA::escape($nick)
		);

		if (! DBA::isResult($r)) {
			if ($r_json) {
				echo json_encode(['error' => DI::l10n()->t('Invalid request.')]);
				exit();
			}
			return;
		}
	} else {
		if ($r_json) {
			echo json_encode(['error' => DI::l10n()->t('Invalid request.')]);
			exit();
		}

		return;
	}

	$can_post  = false;

	$page_owner_uid   = $r[0]['uid'];
	$page_owner_cid   = $r[0]['id'];
	$community_page   = (($r[0]['page-flags'] == User::PAGE_FLAGS_COMMUNITY) ? true : false);

	if (local_user() && (local_user() == $page_owner_uid)) {
		$can_post = true;
	} elseif ($community_page && !empty(Session::getRemoteContactID($page_owner_uid))) {
		$contact_id = Session::getRemoteContactID($page_owner_uid);
		$r = q("SELECT `uid` FROM `contact` WHERE `blocked` = 0 AND `pending` = 0 AND `id` = %d AND `uid` = %d LIMIT 1",
			intval($contact_id),
			intval($page_owner_uid)
		);

		if (DBA::isResult($r)) {
			$can_post = true;
		}
	}

	if (!$can_post) {
		if ($r_json) {
			echo json_encode(['error' => DI::l10n()->t('Permission denied.')]);
			exit();
		}
		notice(DI::l10n()->t('Permission denied.') . EOL );
		exit();
	}

	if (empty($_FILES['userfile'])) {
		if ($r_json) {
			echo json_encode(['error' => DI::l10n()->t('Invalid request.')]);
		}
		exit();
	}

	$src      = $_FILES['userfile']['tmp_name'];
	$filename = basename($_FILES['userfile']['name']);
	$filesize = intval($_FILES['userfile']['size']);

	$maxfilesize = DI::config()->get('system','maxfilesize');

	/* Found html code written in text field of form,
	 * when trying to upload a file with filesize
	 * greater than upload_max_filesize. Cause is unknown.
	 * Then Filesize gets <= 0.
	 */

	if ($filesize <= 0) {
		$msg = DI::l10n()->t('Sorry, maybe your upload is bigger than the PHP configuration allows') . EOL .(DI::l10n()->t('Or - did you try to upload an empty file?'));
		if ($r_json) {
			echo json_encode(['error' => $msg]);
		} else {
			notice($msg . EOL);
		}
		@unlink($src);
		exit();
	}

	if ($maxfilesize && $filesize > $maxfilesize) {
		$msg = DI::l10n()->t('File exceeds size limit of %s', Strings::formatBytes($maxfilesize));
		if ($r_json) {
			echo json_encode(['error' => $msg]);
		} else {
			echo $msg . EOL;
		}
		@unlink($src);
		exit();
	}

	$newid = Attach::storeFile($src, $page_owner_uid, $filename, '<' . $page_owner_cid . '>');

	@unlink($src);

	if ($newid === false) {
		$msg =  DI::l10n()->t('File upload failed.');
		if ($r_json) {
			echo json_encode(['error' => $msg]);
		} else {
			echo $msg . EOL;
		}
		exit();
	}

	if ($r_json) {
		echo json_encode(['ok' => true, 'id' => $newid]);
		exit();
	}

	$lf = "\n";

	echo  $lf . $lf . '[attachment]' . $newid . '[/attachment]' . $lf;

	exit();
	// NOTREACHED
}
