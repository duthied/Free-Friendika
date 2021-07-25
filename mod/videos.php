<?php
/**
 * @copyright Copyright (C) 2010-2021, the Friendica project
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
use Friendica\Content\Nav;
use Friendica\Content\Pager;
use Friendica\Content\Widget;
use Friendica\Core\Renderer;
use Friendica\Core\Session;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Attach;
use Friendica\Model\Item;
use Friendica\Model\User;
use Friendica\Module\BaseProfile;
use Friendica\Security\Security;
use Friendica\Network\HTTPException;

function videos_init(App $a)
{
	if (DI::config()->get('system', 'block_public') && !Session::isAuthenticated()) {
		return;
	}

	Nav::setSelected('home');

	if ($a->argc > 1) {
		$owner = User::getOwnerDataByNick($a->argv[1]);
		if (empty($owner)) {
			throw new HTTPException\NotFoundException(DI::l10n()->t('User not found.'));
		}

		// If not there, create 'aside' empty
		if (!isset(DI::page()['aside'])) {
			DI::page()['aside'] = '';
		}

		DI::page()['aside'] .= Widget\VCard::getHTML($owner);

		$tpl = Renderer::getMarkupTemplate("videos_head.tpl");
		DI::page()['htmlhead'] .= Renderer::replaceMacros($tpl);
	}

	return;
}

function videos_post(App $a)
{
	$user = User::getByNickname($a->argv[1]);
	if (!DBA::isResult($user)) {
		throw new HTTPException\NotFoundException(DI::l10n()->t('User not found.'));
	}

	if (local_user() != $user['uid']) {
		DI::baseUrl()->redirect('videos/' . $user['nickname']);
	}

	if (($a->argc == 2) && !empty($_POST['delete']) && !empty($_POST['id'])) {
		$video_id = $_POST['id'];

		if (Attach::exists(['id' => $video_id, 'uid' => local_user()])) {
			// delete the attachment
			Attach::delete(['id' => $video_id, 'uid' => local_user()]);

			// delete items where the attach is used
			Item::deleteForUser(['`attach` LIKE ? AND `uid` = ?',
				'%attach/' . $video_id . '%',
				local_user()
			], local_user());
		}

		DI::baseUrl()->redirect('videos/' . $user['nickname']);
		return; // NOTREACHED
	}

	DI::baseUrl()->redirect('videos/' . $user['nickname']);
}

function videos_content(App $a)
{
	// URLs (most aren't currently implemented):
	// videos/name
	// videos/name/upload
	// videos/name/upload/xxxxx (xxxxx is album name)
	// videos/name/album/xxxxx
	// videos/name/album/xxxxx/edit
	// videos/name/video/xxxxx
	// videos/name/video/xxxxx/edit

	$user = User::getByNickname($a->argv[1]);
	if (!DBA::isResult($user)) {
		throw new HTTPException\NotFoundException(DI::l10n()->t('User not found.'));
	}

	if (DI::config()->get('system', 'block_public') && !Session::isAuthenticated()) {
		notice(DI::l10n()->t('Public access denied.'));
		return;
	}

	if (empty($user)) {
		notice(DI::l10n()->t('No videos selected') . EOL );
		return;
	}

	//$phototypes = Photo::supportedTypes();

	$_SESSION['video_return'] = DI::args()->getCommand();

	//
	// Parse arguments
	//
	if ($a->argc > 3) {
		$datatype = $a->argv[2];
	} elseif(($a->argc > 2) && ($a->argv[2] === 'upload')) {
		$datatype = 'upload';
	} else {
		$datatype = 'summary';
	}

	//
	// Setup permissions structures
	//
	$can_post       = false;
	$visitor        = 0;
	$remote_contact = false;
	$contact_id     = 0;

	$community_page = (($user['page-flags'] == User::PAGE_FLAGS_COMMUNITY) ? true : false);

	if ((local_user()) && (local_user() == $user['uid'])) {
		$can_post = true;
	} elseif ($community_page && !empty(Session::getRemoteContactID($user['uid']))) {
		$contact_id = Session::getRemoteContactID($user['uid']);
		$can_post = true;
		$remote_contact = true;
		$visitor = $contact_id;
	}

	// perhaps they're visiting - but not a community page, so they wouldn't have write access
	if (!empty(Session::getRemoteContactID($user['uid'])) && !$visitor) {
		$contact_id = Session::getRemoteContactID($user['uid']);
		$remote_contact = true;
	}

	if ($user['hidewall'] && (local_user() != $user['uid']) && !$remote_contact) {
		notice(DI::l10n()->t('Access to this item is restricted.'));
		return;
	}

	$sql_extra = Security::getPermissionsSQLByUserId($user['uid']);

	$o = "";

	// tabs
	$_is_owner = (local_user() && (local_user() == $user['uid']));
	$o .= BaseProfile::getTabsHTML($a, 'videos', $_is_owner, $user);

	//
	// dispatch request
	//
	if ($datatype === 'upload') {
		return; // no uploading for now

		// DELETED -- look at mod/photos.php if you want to implement
	}

	if ($datatype === 'album') {
		return; // no albums for now

		// DELETED -- look at mod/photos.php if you want to implement
	}


	if ($datatype === 'video') {
		return; // no single video view for now

		// DELETED -- look at mod/photos.php if you want to implement
	}

	// Default - show recent videos (no upload link for now)
	//$o = '';

	$total = 0;
	$r = q("SELECT hash FROM `attach` WHERE `uid` = %d AND filetype LIKE '%%video%%'
		$sql_extra GROUP BY hash",
		intval($user['uid'])
	);
	if (DBA::isResult($r)) {
		$total = count($r);
	}

	$pager = new Pager(DI::l10n(), DI::args()->getQueryString(), 20);

	$r = q("SELECT hash, ANY_VALUE(`id`) AS `id`, ANY_VALUE(`created`) AS `created`,
		ANY_VALUE(`filename`) AS `filename`, ANY_VALUE(`filetype`) as `filetype`
		FROM `attach`
		WHERE `uid` = %d AND filetype LIKE '%%video%%'
		$sql_extra GROUP BY hash ORDER BY `created` DESC LIMIT %d , %d",
		intval($user['uid']),
		$pager->getStart(),
		$pager->getItemsPerPage()
	);

	$videos = [];

	if (DBA::isResult($r)) {
		foreach ($r as $rr) {
			$alt_e = $rr['filename'];
			/// @todo The album isn't part of the above query. This seems to be some unfinished code that needs to be reworked completely.
			$rr['album'] = '';
			$name_e = $rr['album'];

			$videos[] = [
				'id'       => $rr['id'],
				'link'     => DI::baseUrl() . '/videos/' . $user['nickname'] . '/video/' . $rr['hash'],
				'title'    => DI::l10n()->t('View Video'),
				'src'      => DI::baseUrl() . '/attach/' . $rr['id'] . '?attachment=0',
				'alt'      => $alt_e,
				'mime'     => $rr['filetype'],
				'album' => [
					'link'  => DI::baseUrl() . '/videos/' . $user['nickname'] . '/album/' . bin2hex($rr['album']),
					'name'  => $name_e,
					'alt'   => DI::l10n()->t('View Album'),
				],
			];
		}
	}

	$tpl = Renderer::getMarkupTemplate('videos_recent.tpl');
	$o .= Renderer::replaceMacros($tpl, [
		'$title'      => DI::l10n()->t('Recent Videos'),
		'$can_post'   => $can_post,
		'$upload'     => [DI::l10n()->t('Upload New Videos'), DI::baseUrl() . '/videos/' . $user['nickname'] . '/upload'],
		'$videos'     => $videos,
		'$delete_url' => (($can_post) ? DI::baseUrl() . '/videos/' . $user['nickname'] : false)
	]);

	$o .= $pager->renderFull($total);

	return $o;
}
