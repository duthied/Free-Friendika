<?php
/**
 * @copyright Copyright (C) 2010-2022, the Friendica project
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
use Friendica\Core\System;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Attach;
use Friendica\Model\User;
use Friendica\Util\Strings;

function wall_attach_post(App $a)
{
	$isJson = (!empty($_GET['response']) && $_GET['response'] == 'json');

	if (DI::args()->getArgc() > 1) {
		$nick  = DI::args()->getArgv()[1];
		$owner = User::getOwnerDataByNick($nick);
		if (!DBA::isResult($owner)) {
			Logger::warning('owner is not a valid record:', ['owner' => $owner, 'nick' => $nick]);
			if ($isJson) {
				System::jsonExit(['error' => DI::l10n()->t('Invalid request.')]);
			}
			return;
		}
	} else {
		Logger::warning('Argument count is zero or one (invalid)');
		if ($isJson) {
			System::jsonExit(['error' => DI::l10n()->t('Invalid request.')]);
		}

		return;
	}

	$can_post = false;

	$page_owner_uid = $owner['uid'];
	$page_owner_cid = $owner['id'];
	$community_page = $owner['page-flags'] == User::PAGE_FLAGS_COMMUNITY;

	if (DI::userSession()->getLocalUserId() && (DI::userSession()->getLocalUserId() == $page_owner_uid)) {
		$can_post = true;
	} elseif ($community_page && !empty(DI::userSession()->getRemoteContactID($page_owner_uid))) {
		$contact_id = DI::userSession()->getRemoteContactID($page_owner_uid);
		$can_post   = DBA::exists('contact', ['blocked' => false, 'pending' => false, 'id' => $contact_id, 'uid' => $page_owner_uid]);
	}

	if (!$can_post) {
		Logger::warning('User does not have required permissions', ['contact_id' => $contact_id, 'page_owner_uid' => $page_owner_uid]);
		if ($isJson) {
			System::jsonExit(['error' => DI::l10n()->t('Permission denied.')]);
		}
		DI::sysmsg()->addNotice(DI::l10n()->t('Permission denied.'));
		System::exit();
	}

	if (empty($_FILES['userfile'])) {
		Logger::warning('No file uploaded (empty userfile)');
		if ($isJson) {
			System::jsonExit(['error' => DI::l10n()->t('Invalid request.')]);
		}
		System::exit();
	}

	$tempFileName = $_FILES['userfile']['tmp_name'];
	$fileName     = basename($_FILES['userfile']['name']);
	$fileSize     = intval($_FILES['userfile']['size']);
	$maxFileSize  = DI::config()->get('system', 'maxfilesize');

	/*
	 * Found html code written in text field of form, when trying to upload a
	 * file with filesize greater than upload_max_filesize. Cause is unknown.
	 * Then Filesize gets <= 0.
	 */
	if ($fileSize <= 0) {
		$msg = DI::l10n()->t('Sorry, maybe your upload is bigger than the PHP configuration allows') . '<br />' . DI::l10n()->t('Or - did you try to upload an empty file?');
		Logger::warning($msg, ['fileSize' => $fileSize]);
		@unlink($tempFileName);
		if ($isJson) {
			System::jsonExit(['error' => $msg]);
		} else {
			DI::sysmsg()->addNotice($msg);
		}
		System::exit();
	}

	if ($maxFileSize && $fileSize > $maxFileSize) {
		$msg = DI::l10n()->t('File exceeds size limit of %s', Strings::formatBytes($maxFileSize));
		Logger::warning($msg, ['fileSize' => $fileSize]);
		@unlink($tempFileName);
		if ($isJson) {
			System::jsonExit(['error' => $msg]);
		} else {
			echo $msg . '<br />';
		}
		System::exit();
	}

	$newid = Attach::storeFile($tempFileName, $page_owner_uid, $fileName, '<' . $page_owner_cid . '>');

	@unlink($tempFileName);

	if ($newid === false) {
		$msg = DI::l10n()->t('File upload failed.');
		Logger::warning($msg);
		if ($isJson) {
			System::jsonExit(['error' => $msg]);
		} else {
			echo $msg . '<br />';
		}
		System::exit();
	}

	if ($isJson) {
		System::jsonExit(['ok' => true, 'id' => $newid]);
	}

	$lf = "\n";

	echo  $lf . $lf . '[attachment]' . $newid . '[/attachment]' . $lf;
	System::exit();
	// NOTREACHED
}
