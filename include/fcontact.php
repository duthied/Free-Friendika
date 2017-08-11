<?php



function fcontact_store($url,$name,$photo) {

	$nurl = str_replace(array('https:','//www.'), array('http:','//'), $url);

	$r = q("SELECT `id` FROM `fcontact` WHERE `url` = '%s' LIMIT 1",
		dbesc($nurl)
	);

	if (dbm::is_result($r))
		return $r[0]['id'];

	$r = dba::insert('fcontact', array('url' => $nurl, 'name' => $name, 'photo' => $photo));

	if (dbm::is_result($r)) {
		$r = q("SELECT `id` FROM `fcontact` WHERE `url` = '%s' LIMIT 1",
			dbesc($nurl)
		);
		if (dbm::is_result($r))
			return $r[0]['id'];
	}

	return 0;
}

function ffinder_store($uid,$cid,$fid) {
	$r = dba::insert('ffinder', array('uid' => $uid, 'cid' => $cid, 'fid' => $fid));
	return $r;
}

