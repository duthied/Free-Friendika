<?php
require_once('include/items.php');
require_once('include/acl_selectors.php');
require_once('include/bbcode.php');
require_once('include/security.php');
require_once('include/redir.php');


function videos_init(&$a) {

	if($a->argc > 1)
		auto_redir($a, $a->argv[1]);

	if((get_config('system','block_public')) && (! local_user()) && (! remote_user())) {
		return;
	}

	$o = '';

	if($a->argc > 1) {
		$nick = $a->argv[1];
		$r = q("SELECT * FROM `user` WHERE `nickname` = '%s' AND `blocked` = 0 LIMIT 1",
			dbesc($nick)
		);

		if(! count($r))
			return;

		$a->data['user'] = $r[0];

		$o .= '<div class="vcard">';
		$o .= '<div class="fn">' . $a->data['user']['username'] . '</div>';
		$o .= '<div id="profile-photo-wrapper"><img class="photo" style="width: 175px; height: 175px;" src="' . $a->get_cached_avatar_image($a->get_baseurl() . '/photo/profile/' . $a->data['user']['uid'] . '.jpg') . '" alt="' . $a->data['user']['username'] . '" /></div>';
		$o .= '</div>';


		/*$sql_extra = permissions_sql($a->data['user']['uid']);

		$albums = q("SELECT distinct(`album`) AS `album` FROM `photo` WHERE `uid` = %d $sql_extra order by created desc",
			intval($a->data['user']['uid'])
		);

		if(count($albums)) {
			$a->data['albums'] = $albums;

			$albums_visible = ((intval($a->data['user']['hidewall']) && (! local_user()) && (! remote_user())) ? false : true);	

			if($albums_visible) {
				$o .= '<div id="side-bar-photos-albums" class="widget">';
				$o .= '<h3>' . '<a href="' . $a->get_baseurl() . '/photos/' . $a->data['user']['nickname'] . '">' . t('Photo Albums') . '</a></h3>';
					
				$o .= '<ul>';
				foreach($albums as $album) {

					// don't show contact photos. We once translated this name, but then you could still access it under
					// a different language setting. Now we store the name in English and check in English (and translated for legacy albums).

					if((! strlen($album['album'])) || ($album['album'] === 'Contact Photos') || ($album['album'] === t('Contact Photos')))
						continue;
					$o .= '<li>' . '<a href="photos/' . $a->argv[1] . '/album/' . bin2hex($album['album']) . '" >' . $album['album'] . '</a></li>'; 
				}
				$o .= '</ul>';
			}
			if(local_user() && $a->data['user']['uid'] == local_user()) {
				$o .= '<div id="photo-albums-upload-link"><a href="' . $a->get_baseurl() . '/photos/' . $a->data['user']['nickname'] . '/upload" >' .t('Upload New Photos') . '</a></div>';
			}

			$o .= '</div>';
		}*/

		if(! x($a->page,'aside'))
			$a->page['aside'] = '';
		$a->page['aside'] .= $o;


		$tpl = get_markup_template("videos_head.tpl");
		$a->page['htmlhead'] .= replace_macros($tpl,array(
			'$baseurl' => $a->get_baseurl(),
		));

		$tpl = get_markup_template("videos_end.tpl");
		$a->page['end'] .= replace_macros($tpl,array(
			'$baseurl' => $a->get_baseurl(),
		));

	}

	return;
}



function videos_post(&$a) {

	return;

	// DELETED -- look at mod/photos.php if you want to implement
}



function videos_content(&$a) {

	// URLs (most aren't currently implemented):
	// videos/name
	// videos/name/upload
	// videos/name/upload/xxxxx (xxxxx is album name)
	// videos/name/album/xxxxx
	// videos/name/album/xxxxx/edit
	// videos/name/video/xxxxx
	// videos/name/video/xxxxx/edit


	if((get_config('system','block_public')) && (! local_user()) && (! remote_user())) {
		notice( t('Public access denied.') . EOL);
		return;
	}
	
	
	require_once('include/bbcode.php');
	require_once('include/security.php');
	require_once('include/conversation.php');

	if(! x($a->data,'user')) {
		notice( t('No videos selected') . EOL );
		return;
	}

	//$phototypes = Photo::supportedTypes();

	$_SESSION['video_return'] = $a->cmd;

	//
	// Parse arguments 
	//

	if($a->argc > 3) {
		$datatype = $a->argv[2];
		$datum = $a->argv[3];
	}
	elseif(($a->argc > 2) && ($a->argv[2] === 'upload'))
		$datatype = 'upload';
	else
		$datatype = 'summary';

	if($a->argc > 4)
		$cmd = $a->argv[4];
	else
		$cmd = 'view';

	//
	// Setup permissions structures
	//

	$can_post       = false;
	$visitor        = 0;
	$contact        = null;
	$remote_contact = false;
	$contact_id     = 0;

	$owner_uid = $a->data['user']['uid'];

	$community_page = (($a->data['user']['page-flags'] == PAGE_COMMUNITY) ? true : false);

	if((local_user()) && (local_user() == $owner_uid))
		$can_post = true;
	else {
		if($community_page && remote_user()) {
			if(is_array($_SESSION['remote'])) {
				foreach($_SESSION['remote'] as $v) {
					if($v['uid'] == $owner_uid) {
						$contact_id = $v['cid'];
						break;
					}
				}
			}
			if($contact_id) {

				$r = q("SELECT `uid` FROM `contact` WHERE `blocked` = 0 AND `pending` = 0 AND `id` = %d AND `uid` = %d LIMIT 1",
					intval($contact_id),
					intval($owner_uid)
				);
				if(count($r)) {
					$can_post = true;
					$contact = $r[0];
					$remote_contact = true;
					$visitor = $cid;
				}
			}
		}
	}

	// perhaps they're visiting - but not a community page, so they wouldn't have write access

	if(remote_user() && (! $visitor)) {
		$contact_id = 0;
		if(is_array($_SESSION['remote'])) {
			foreach($_SESSION['remote'] as $v) {
				if($v['uid'] == $owner_uid) {
					$contact_id = $v['cid'];
					break;
				}
			}
		}
		if($contact_id) {
			$groups = init_groups_visitor($contact_id);
			$r = q("SELECT * FROM `contact` WHERE `blocked` = 0 AND `pending` = 0 AND `id` = %d AND `uid` = %d LIMIT 1",
				intval($contact_id),
				intval($owner_uid)
			);
			if(count($r)) {
				$contact = $r[0];
				$remote_contact = true;
			}
		}
	}

	if(! $remote_contact) {
		if(local_user()) {
			$contact_id = $_SESSION['cid'];
			$contact = $a->contact;
		}
	}

	if($a->data['user']['hidewall'] && (local_user() != $owner_uid) && (! $remote_contact)) {
		notice( t('Access to this item is restricted.') . EOL);
		return;
	}

	$sql_extra = permissions_sql($owner_uid,$remote_contact,$groups);

	$o = "";

	// tabs
	$_is_owner = (local_user() && (local_user() == $owner_uid));
	$o .= profile_tabs($a,$_is_owner, $a->data['user']['nickname']);	

	//
	// dispatch request
	//


	if($datatype === 'upload') {
		return; // no uploading for now

		// DELETED -- look at mod/photos.php if you want to implement
	}

	if($datatype === 'album') {

		return; // no albums for now

		// DELETED -- look at mod/photos.php if you want to implement
	}	


	if($datatype === 'video') {

		return; // no single video view for now

		// DELETED -- look at mod/photos.php if you want to implement
	}

	// Default - show recent videos (no upload link for now)
	//$o = '';

	$r = q("SELECT hash FROM `attach` WHERE `uid` = %d AND filetype LIKE '%%video%%'
		$sql_extra GROUP BY hash",
		intval($a->data['user']['uid'])
	);
	if(count($r)) {
		$a->set_pager_total(count($r));
		$a->set_pager_itemspage(20);
	}

	$r = q("SELECT hash, `id`, `filename`, filetype FROM `attach`
		WHERE `uid` = %d AND filetype LIKE '%%video%%'
		$sql_extra GROUP BY hash ORDER BY `created` DESC LIMIT %d , %d",
		intval($a->data['user']['uid']),
		intval($a->pager['start']),
		intval($a->pager['itemspage'])
	);



	$videos = array();
	if(count($r)) {
		foreach($r as $rr) {
			if($a->theme['template_engine'] === 'internal') {
				$alt_e = template_escape($rr['filename']);
				$name_e = template_escape($rr['album']);
			}
			else {
				$alt_e = $rr['filename'];
				$name_e = $rr['album'];
			}

			$videos[] = array(
				'id'       => $rr['id'],
				'link'  	=> $a->get_baseurl() . '/videos/' . $a->data['user']['nickname'] . '/video/' . $rr['resource-id'],
				'title' 	=> t('View Video'),
				'src'     	=> $a->get_baseurl() . '/attach/' . $rr['id'] . '?attachment=0',
				'alt'     	=> $alt_e,
				'mime'		=> $rr['filetype'],
				'album'	=> array(
					'link'  => $a->get_baseurl() . '/videos/' . $a->data['user']['nickname'] . '/album/' . bin2hex($rr['album']),
					'name'  => $name_e,
					'alt'   => t('View Album'),
				),
				
			);
		}
	}
	
	$tpl = get_markup_template('videos_recent.tpl'); 
	$o .= replace_macros($tpl, array(
		'$title' => t('Recent Videos'),
		'$can_post' => $can_post,
		'$upload' => array(t('Upload New Videos'), $a->get_baseurl().'/videos/'.$a->data['user']['nickname'].'/upload'),
		'$videos' => $videos,
	));

	
	$o .= paginate($a);
	return $o;
}

