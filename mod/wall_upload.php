<?php

require_once('include/Photo.php');

function wall_upload_post(&$a) {

	logger("wall upload: starting new upload", LOGGER_DEBUG);

	if($a->argc > 1) {
	        if(! x($_FILES,'media')) {
		        $nick = $a->argv[1];
		        $r = q("SELECT `user`.*, `contact`.`id` FROM `user` LEFT JOIN `contact` on `user`.`uid` = `contact`.`uid`  WHERE `user`.`nickname` = '%s' AND `user`.`blocked` = 0 and `contact`.`self` = 1 LIMIT 1",
			        dbesc($nick)
		        );

		        if(! count($r))
                                return;
		}
                else {
			$user_info = api_get_user($a);
		        $r = q("SELECT `user`.*, `contact`.`id` FROM `user` LEFT JOIN `contact` on `user`.`uid` = `contact`.`uid`  WHERE `user`.`nickname` = '%s' AND `user`.`blocked` = 0 and `contact`.`self` = 1 LIMIT 1",
			        dbesc($user_info['screen_name'])
		        );
                }
	}
	else
		return;


	$can_post  = false;
	$visitor   = 0;

	$page_owner_uid   = $r[0]['uid'];
	$default_cid      = $r[0]['id'];
	$page_owner_nick  = $r[0]['nickname'];
	$community_page   = (($r[0]['page-flags'] == PAGE_COMMUNITY) ? true : false);

	if((local_user()) && (local_user() == $page_owner_uid))
		$can_post = true;
	else {
		if($community_page && remote_user()) {
			$cid = 0;
			if(is_array($_SESSION['remote'])) {
				foreach($_SESSION['remote'] as $v) {
					if($v['uid'] == $page_owner_uid) {
						$cid = $v['cid'];
						break;
					}
				}
			}
			if($cid) {

				$r = q("SELECT `uid` FROM `contact` WHERE `blocked` = 0 AND `pending` = 0 AND `id` = %d AND `uid` = %d LIMIT 1",
					intval($cid),
					intval($page_owner_uid)
				);
				if(count($r)) {
					$can_post = true;
					$visitor = $cid;
				}
			}
		}
	}

	if(! $can_post) {
		notice( t('Permission denied.') . EOL );
		killme();
	}

	if(! x($_FILES,'userfile') && ! x($_FILES,'media'))
		killme();

	if(x($_FILES,'userfile')) {
		$src      = $_FILES['userfile']['tmp_name'];
		$filename = basename($_FILES['userfile']['name']);
		$filesize = intval($_FILES['userfile']['size']);
		$filetype = $_FILES['userfile']['type'];
	}
	elseif(x($_FILES,'media')) {
		$src = $_FILES['media']['tmp_name'];
		$filename = basename($_FILES['media']['name']);
		$filesize = intval($_FILES['media']['size']);
		$filetype = $_FILES['media']['type'];
	}
	
    if ($filetype=="") $filetype=guess_image_type($filename);
	$maximagesize = get_config('system','maximagesize');

	if(($maximagesize) && ($filesize > $maximagesize)) {
		echo  sprintf( t('Image exceeds size limit of %d'), $maximagesize) . EOL;
		@unlink($src);
		killme();
	}

	$r = q("select sum(octet_length(data)) as total from photo where uid = %d and scale = 0 and album != 'Contact Photos' ",
		intval($page_owner_uid)
	);

	$limit = service_class_fetch($page_owner_uid,'photo_upload_limit');

	if(($limit !== false) && (($r[0]['total'] + strlen($imagedata)) > $limit)) {
		echo upgrade_message(true) . EOL ;
		@unlink($src);
		killme();
	}


	$imagedata = @file_get_contents($src);
	$ph = new Photo($imagedata, $filetype);

	if(! $ph->is_valid()) {
		echo ( t('Unable to process image.') . EOL);
		@unlink($src);
		killme();
	}

	$ph->orient($src);
	@unlink($src);

	$max_length = get_config('system','max_image_length');
	if(! $max_length)
		$max_length = MAX_IMAGE_LENGTH;
	if($max_length > 0)
		$ph->scaleImage($max_length);

	$width = $ph->getWidth();
	$height = $ph->getHeight();

	$hash = photo_new_resource();
	
	$smallest = 0;

	$defperm = '<' . $default_cid . '>';

	$r = $ph->store($page_owner_uid, $visitor, $hash, $filename, t('Wall Photos'), 0, 0, $defperm);

	if(! $r) {
		echo ( t('Image upload failed.') . EOL);
		killme();
	}

	if($width > 640 || $height > 640) {
		$ph->scaleImage(640);
		$r = $ph->store($page_owner_uid, $visitor, $hash, $filename, t('Wall Photos'), 1, 0, $defperm);
		if($r) 
			$smallest = 1;
	}

	if($width > 320 || $height > 320) {
		$ph->scaleImage(320);
		$r = $ph->store($page_owner_uid, $visitor, $hash, $filename, t('Wall Photos'), 2, 0, $defperm);
		if($r)
			$smallest = 2;
	}

	$basename = basename($filename);


/* mod Waitman Gobble NO WARRANTY */

//if we get the signal then return the image url info in BBCODE, otherwise this outputs the info and bails (for the ajax image uploader on wall post)
	if ($_REQUEST['hush']!='yeah') {
		if(local_user() && (! feature_enabled(local_user(),'richtext') || x($_REQUEST['nomce'])) ) {
			echo  "\n\n" . '[url=' . $a->get_baseurl() . '/photos/' . $page_owner_nick . '/image/' . $hash . '][img]' . $a->get_baseurl() . "/photo/{$hash}-{$smallest}.".$ph->getExt()."[/img][/url]\n\n";
		}
		else {
			echo  '<br /><br /><a href="' . $a->get_baseurl() . '/photos/' . $page_owner_nick . '/image/' . $hash . '" ><img src="' . $a->get_baseurl() . "/photo/{$hash}-{$smallest}.".$ph->getExt()."\" alt=\"$basename\" /></a><br /><br />";
		}
	}
	else {
		$m = '[url=' . $a->get_baseurl() . '/photos/' . $page_owner_nick . '/image/' . $hash . '][img]' . $a->get_baseurl() . "/photo/{$hash}-{$smallest}.".$ph->getExt()."[/img][/url]";
		return($m);
	}
/* mod Waitman Gobble NO WARRANTY */

	killme();
	// NOTREACHED
}
