<?php
/**
 * @file include/photos.php
 * @brief Functions related to photo handling.
 */


/**
 * @brief Get the permissions for the photos page
 * 
 * @param int $owner_uid Owner of the photos page
 * @param bool $community_page If it's an forum account
 * 
 * @return array
 *......'can_post'
 *......'visitor'
 *......'contact'
 *      'remote_contact'
 * .....'contact_id'
 *      'groups'
 */
function photos_permissions($owner_uid, $community_page = 0) {

	$arr = array();

	if((local_user()) && (local_user() == $owner_uid))
		$arr['can_post'] = true;
	else {
		if($community_page && remote_user()) {
			if(is_array($_SESSION['remote'])) {
				foreach($_SESSION['remote'] as $v) {
					if($v['uid'] == $owner_uid) {
						$arr['contact_id'] = $v['cid'];
						break;
					}
				}
			}
			if($arr['contact_id']) {

				$r = q("SELECT `uid` FROM `contact` WHERE `blocked` = 0 AND `pending` = 0 AND `id` = %d AND `uid` = %d LIMIT 1",
					intval($arr['contact_id']),
					intval($owner_uid)
				);
				if(count($r)) {
					$arr['can_post'] = true;
					$arr['contact'] = $r[0];
					$arr['remote_contact'] = true;
					$arr['visitor'] = $cid;
				}
			}
		}
	}

	// perhaps they're visiting - but not a community page, so they wouldn't have write access

	if(remote_user() && (! $arr['visitor'])) {
		$arr['contact_id'] = 0;
		if(is_array($_SESSION['remote'])) {
			foreach($_SESSION['remote'] as $v) {
				if($v['uid'] == $owner_uid) {
					$arr['contact_id'] = $v['cid'];
					break;
				}
			}
		}
		if($arr['contact_id']) {
			$arr['groups'] = init_groups_visitor($arr['contact_id']);
			$r = q("SELECT * FROM `contact` WHERE `blocked` = 0 AND `pending` = 0 AND `id` = %d AND `uid` = %d LIMIT 1",
				intval($arr['contact_id']),
				intval($owner_uid)
			);
			if(count($r)) {
				$arr['contact'] = $r[0];
				$arr['remote_contact'] = true;
			}
		}
	}

	if(! $arr['remote_contact']) {
		if(local_user()) {
			$arr['contact_id'] = $_SESSION['cid'];
			$arr['contact'] = $a->contact;
		}
	}

	return $arr;
}

/**
 * @brief Construnct a widget with last uploaded photos
 * 
 * It displays the last 9 photos
 * 
 * @param array $profile_data
 *......'profile_uid'...=> The user.id of the profile (owner of the hotos)
 *......'nickname'......=> Nick of the owner of the profile
 *......'page-flags'....=> Account type of the profile
 * 
 * @return string
 *......formatted html
 * 
 * @template widget_photos.tpl
 */
function widget_photos($profile_data) {

	$community_page = (($profile_data['page-flags'] == PAGE_COMMUNITY) ? true : false);
	$nickname = $profile_data['nickname'];
	$owner_id = $profile_data['profile_uid'];

	$phototypes = Photo::supportedTypes();
	$photos_perms = photos_permissions($owner_id, $community_page);

	$sql_extra = permissions_sql($owner_id, $photos_perms['remote_contact'], $photos_perms['groups']);

	$r = q("SELECT `resource-id`, `id`, `filename`, `type`, max(`scale`) AS `scale` FROM `photo`
		WHERE `uid` = %d AND `album` != '%s' AND `album` != '%s' AND `album` != '%s'
		$sql_extra GROUP BY `resource-id` ORDER BY `created` DESC LIMIT 9",
		intval($owner_id),
		dbesc('Contact Photos'),
		dbesc( t('Contact Photos')),
		dbesc( t('Profile Photos'))
	);

	$photos = array();
	if(count($r)) {
		foreach($r as $rr) {
			$ext = $phototypes[$rr['type']];
	
			$photos[] = array(
				'id'		=> $rr['id'],
				'src'		=> z_root() . '/photos/' . $nickname . '/image/' . $rr['resource-id'],
				'photo'		=> z_root() . '/photo/' . $rr['resource-id'] . '-' . ((($rr['scale']) == 6) ? 4 : $rr['scale']) . '.' . $ext,
				'alt_text'	=> $rr['filename'],
			);
		}

		$tpl = get_markup_template('widget_photos.tpl');
		$o .= replace_macros($tpl, array(
			'$title' => t('Photos'),
			'$photos' => $photos,
			'$photo_albums_page'	=> z_root() . '/photos/' . $nickname,
			'$photo_albums_page_title' => t('Vist the Photo Albums'),
		));

		return $o;
	}
}

function getGps($exifCoord, $hemi) {
	$degrees = count($exifCoord) > 0 ? gps2Num($exifCoord[0]) : 0;
	$minutes = count($exifCoord) > 1 ? gps2Num($exifCoord[1]) : 0;
	$seconds = count($exifCoord) > 2 ? gps2Num($exifCoord[2]) : 0;

	$flip = ($hemi == 'W' or $hemi == 'S') ? -1 : 1;

	return floatval($flip * ($degrees + ($minutes / 60) + ($seconds / 3600)));
}

function gps2Num($coordPart) {
	$parts = explode('/', $coordPart);

	if (count($parts) <= 0)
		return 0;

	if (count($parts) == 1)
		return $parts[0];

	return floatval($parts[0]) / floatval($parts[1]);
}
