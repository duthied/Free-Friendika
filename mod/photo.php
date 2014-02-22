<?php

require_once('include/security.php');
require_once('include/Photo.php');

function photo_init(&$a) {
	global $_SERVER;

	$prvcachecontrol = false;
	$file = "";

	switch($a->argc) {
		case 4:
			$person = $a->argv[3];
			$customres = intval($a->argv[2]);
			$type = $a->argv[1];
			break;
		case 3:
			$person = $a->argv[2];
			$type = $a->argv[1];
			break;
		case 2:
			$photo = $a->argv[1];
			$file = $photo;
			break;
		case 1:
		default:
			killme();
			// NOTREACHED
	}

	//	strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) >= filemtime($localFileName)) {
	if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])) {
		header('HTTP/1.1 304 Not Modified');
		header("Last-Modified: " . gmdate("D, d M Y H:i:s", time()) . " GMT");
		header('Etag: '.$_SERVER['HTTP_IF_NONE_MATCH']);
	 	header("Expires: " . gmdate("D, d M Y H:i:s", time() + (31536000)) . " GMT");
		header("Cache-Control: max-age=31536000");
		if(function_exists('header_remove')) {
			header_remove('Last-Modified');
			header_remove('Expires');
			header_remove('Cache-Control');
		}
		exit;
	}

	$default = 'images/person-175.jpg';

	if(isset($type)) {


		/**
		 * Profile photos
		 */

		switch($type) {

			case 'profile':
			case 'custom':
				$resolution = 4;
				break;
			case 'micro':
				$resolution = 6;
				$default = 'images/person-48.jpg';
				break;
			case 'avatar':
			default:
				$resolution = 5;
				$default = 'images/person-80.jpg';
				break;
		}

		$uid = str_replace(array('.jpg','.png'),array('',''), $person);

		$r = q("SELECT * FROM `photo` WHERE `scale` = %d AND `uid` = %d AND `profile` = 1 LIMIT 1",
			intval($resolution),
			intval($uid)
		);
		if(count($r)) {
			$data = $r[0]['data'];
			$mimetype = $r[0]['type'];
		}
		if(! isset($data)) {
			$data = file_get_contents($default);
			$mimetype = 'image/jpeg';
		}
	}
	else {

		/**
		 * Other photos
		 */

		$resolution = 0;
		foreach( Photo::supportedTypes() as $m=>$e){
			$photo = str_replace(".$e",'',$photo);
		}

		if(substr($photo,-2,1) == '-') {
			$resolution = intval(substr($photo,-1,1));
			$photo = substr($photo,0,-2);
		}

		$r = q("SELECT `uid` FROM `photo` WHERE `resource-id` = '%s' AND `scale` = %d LIMIT 1",
			dbesc($photo),
			intval($resolution)
		);
		if(count($r)) {

			$sql_extra = permissions_sql($r[0]['uid']);

			// Now we'll see if we can access the photo

			$r = q("SELECT * FROM `photo` WHERE `resource-id` = '%s' AND `scale` = %d $sql_extra LIMIT 1",
				dbesc($photo),
				intval($resolution)
			);

			$public = ($r[0]['allow_cid'] == '') AND ($r[0]['allow_gid'] == '') AND ($r[0]['deny_cid']  == '') AND ($r[0]['deny_gid']  == '');

			if(count($r)) {
				$data = $r[0]['data'];
				$mimetype = $r[0]['type'];
			}
			else {

				// Does the picture exist? It may be a remote person with no credentials,
				// but who should otherwise be able to view it. Show a default image to let 
				// them know permissions was denied. It may be possible to view the image 
				// through an authenticated profile visit.
				// There won't be many completely unauthorised people seeing this because
				// they won't have the photo link, so there's a reasonable chance that the person
				// might be able to obtain permission to view it.
 
				$r = q("SELECT * FROM `photo` WHERE `resource-id` = '%s' AND `scale` = %d LIMIT 1",
					dbesc($photo),
					intval($resolution)
				);
				if(count($r)) {
					$data = file_get_contents('images/nosign.jpg');
					$mimetype = 'image/jpeg';
					$prvcachecontrol = true;
				}
			}
		}
	}

	if(! isset($data)) {
		if(isset($resolution)) {
			switch($resolution) {

				case 4:
					$data = file_get_contents('images/person-175.jpg');
					$mimetype = 'image/jpeg';
					break;
				case 5:
					$data = file_get_contents('images/person-80.jpg');
					$mimetype = 'image/jpeg';
					break;
				case 6:
					$data = file_get_contents('images/person-48.jpg');
					$mimetype = 'image/jpeg';
					break;
				default:
					killme();
					// NOTREACHED
					break;
			}
		}
	}

	// Resize only if its not a GIF
	if ($mime != "image/gif") {
		$ph = new Photo($data, $mimetype);
		if($ph->is_valid()) {
			if(isset($customres) && $customres > 0 && $customres < 500) {
				$ph->scaleImageSquare($customres);
			}
			$data = $ph->imageString();
			$mimetype = $ph->getType();
		}
	}

	if(function_exists('header_remove')) {
		header_remove('Pragma');
		header_remove('pragma');
	}

	header("Content-type: ".$mimetype);

	if($prvcachecontrol) {

		// it is a private photo that they have no permission to view.
		// tell the browser not to cache it, in case they authenticate
		// and subsequently have permission to see it

		header("Cache-Control: no-store, no-cache, must-revalidate");

	}
	else {
		header("Last-Modified: " . gmdate("D, d M Y H:i:s", time()) . " GMT");
		header('Etag: "'.md5($data).'"');
	 	header("Expires: " . gmdate("D, d M Y H:i:s", time() + (31536000)) . " GMT");
		header("Cache-Control: max-age=31536000");
	}
	echo $data;

	// If the photo is public and there is an existing photo directory store the photo there
	if ($public and ($file != ""))
		if (is_dir($_SERVER["DOCUMENT_ROOT"]."/photo"))
			file_put_contents($_SERVER["DOCUMENT_ROOT"]."/photo/".$file, $data);

	killme();
	// NOTREACHED
}
