<?php

require_once('include/datetime.php');

/*
 * poco_load
 *
 * Given a contact-id (minimum), load the PortableContacts friend list for that contact,
 * and add the entries to the gcontact (Global Contact) table, or update existing entries
 * if anything (name or photo) has changed.
 * We use normalised urls for comparison which ignore http vs https and www.domain vs domain
 *
 * Once the global contact is stored add (if necessary) the contact linkage which associates
 * the given uid, cid to the global contact entry. There can be many uid/cid combinations
 * pointing to the same global contact id.
 *
 */




function poco_load($cid,$uid = 0,$zcid = 0,$url = null) {
	$a = get_app();

	if($cid) {
		if((! $url) || (! $uid)) {
			$r = q("select `poco`, `uid` from `contact` where `id` = %d limit 1",
				intval($cid)
			);
			if(count($r)) {
				$url = $r[0]['poco'];
				$uid = $r[0]['uid'];
			}
		}
		if(! $uid)
			return;
	}

	if(! $url)
		return;

	$url = $url . (($uid) ? '/@me/@all?fields=displayName,urls,photos,updated,network,aboutMe,currentLocation,tags,gender' : '?fields=displayName,urls,photos,updated,network,aboutMe,currentLocation,tags,gender') ;

	logger('poco_load: ' . $url, LOGGER_DEBUG);

	$s = fetch_url($url);

	logger('poco_load: returns ' . $s, LOGGER_DATA);

	logger('poco_load: return code: ' . $a->get_curl_code(), LOGGER_DEBUG);

	if(($a->get_curl_code() > 299) || (! $s))
		return;

	$j = json_decode($s);

	logger('poco_load: json: ' . print_r($j,true),LOGGER_DATA);

	if(! isset($j->entry))
		return;

	$total = 0;
	foreach($j->entry as $entry) {

		$total ++;
		$profile_url = '';
		$profile_photo = '';
		$connect_url = '';
		$name = '';
		$network = '';
		$updated = '0000-00-00 00:00:00';
		$location = '';
		$about = '';
		$keywords = '';
		$gender = '';

		$name = $entry->displayName;

		if(isset($entry->urls)) {
			foreach($entry->urls as $url) {
				if($url->type == 'profile') {
					$profile_url = $url->value;
					continue;
				}
				if($url->type == 'webfinger') {
					$connect_url = str_replace('acct:' , '', $url->value);
					continue;
				}
			}
		}
		if(isset($entry->photos)) {
			foreach($entry->photos as $photo) {
				if($photo->type == 'profile') {
					$profile_photo = $photo->value;
					continue;
				}
			}
		}

		if(isset($entry->updated))
			$updated = date("Y-m-d H:i:s", strtotime($entry->updated));

		if(isset($entry->network))
			$network = $entry->network;

		if(isset($entry->currentLocation))
			$location = $entry->currentLocation;

		if(isset($entry->aboutMe))
			$about = $entry->aboutMe;

		if(isset($entry->gender))
			$gender = $entry->gender;

		if(isset($entry->tags))
			foreach($entry->tags as $tag)
				$keywords = implode(", ", $tag);

		poco_check($profile_url, $name, $network, $profile_photo, $about, $location, $gender, $keywords, $connect_url, $updated, $cid, $uid, $zcid);

		// Update the Friendica contacts. Diaspora is doing it via a message. (See include/diaspora.php)
		if (($location != "") OR ($about != "") OR ($keywords != "") OR ($gender != ""))
			q("UPDATE `contact` SET `location` = '%s', `about` = '%s', `keywords` = '%s', `gender` = '%s'
				WHERE `nurl` = '%s' AND NOT `self` AND `network` = '%s'",
				dbesc($location),
				dbesc($about),
				dbesc($keywords),
				dbesc($gender),
				dbesc(normalise_link($profile_url)),
				dbesc(NETWORK_DFRN));
	}
	logger("poco_load: loaded $total entries",LOGGER_DEBUG);

	q("DELETE FROM `glink` WHERE `cid` = %d AND `uid` = %d AND `zcid` = %d AND `updated` < UTC_TIMESTAMP - INTERVAL 2 DAY",
		intval($cid),
		intval($uid),
		intval($zcid)
	);

}

function poco_check($profile_url, $name, $network, $profile_photo, $about, $location, $gender, $keywords, $connect_url, $updated, $cid = 0, $uid = 0, $zcid = 0) {
	$gcid = "";

	if ($profile_url == "")
		return $gcid;

	$x = q("SELECT * FROM `gcontact` WHERE `nurl` = '%s' LIMIT 1",
		dbesc(normalise_link($profile_url))
	);
	if(count($x))
		$network = $x[0]["network"];

	if (($network == "") OR ($name == "") OR ($profile_photo == "")) {
		require_once("include/Scrape.php");

		$data = probe_url($profile_url, PROBE_DIASPORA);
		$network = $data["network"];
		$name = $data["name"];
		$profile_photo = $data["photo"];
	}

	if (count($x) AND ($x[0]["network"] == "") AND ($network != "")) {
		q("UPDATE `gcontact` SET `network` = '%s' WHERE `nurl` = '%s'",
			dbesc($network),
			dbesc(normalise_link($profile_url))
		);
	}

	if (($name == "") OR ($profile_photo == ""))
		return $gcid;

	if (!in_array($network, array(NETWORK_DFRN, NETWORK_OSTATUS, NETWORK_DIASPORA, NETWORK_STATUSNET)))
		return $gcid;

	logger("profile-check URL: ".$profile_url." name: ".$name." avatar: ".$profile_photo, LOGGER_DEBUG);

	if(count($x)) {
		$gcid = $x[0]['id'];

		if (($location == "") AND ($x[0]['location'] != ""))
			$location = $x[0]['location'];

		if (($about == "") AND ($x[0]['about'] != ""))
			$about = $x[0]['about'];

		if (($gender == "") AND ($x[0]['gender'] != ""))
			$gender = $x[0]['gender'];

		if (($keywords == "") AND ($x[0]['keywords'] != ""))
			$keywords = $x[0]['keywords'];

		if($x[0]['name'] != $name || $x[0]['photo'] != $profile_photo || $x[0]['updated'] < $updated) {
			q("update gcontact set `name` = '%s', `network` = '%s', `photo` = '%s', `connect` = '%s', `url` = '%s',
				`updated` = '%s', `location` = '%s', `about` = '%s', `keywords` = '%s', `gender` = '%s'
				where `nurl` = '%s'",
				dbesc($name),
				dbesc($network),
				dbesc($profile_photo),
				dbesc($connect_url),
				dbesc($profile_url),
				dbesc($updated),
				dbesc($location),
				dbesc($about),
				dbesc($keywords),
				dbesc($gender),
				dbesc(normalise_link($profile_url))
			);
		}
	} else {
		q("insert into `gcontact` (`name`,`network`, `url`,`nurl`,`photo`,`connect`, `updated`, `location`, `about`, `keywords`, `gender`)
			values ('%s', '%s', '%s', '%s', '%s','%s', '%s', '%s', '%s', '%s', '%s')",
			dbesc($name),
			dbesc($network),
			dbesc($profile_url),
			dbesc(normalise_link($profile_url)),
			dbesc($profile_photo),
			dbesc($connect_url),
			dbesc($updated),
			dbesc($location),
			dbesc($about),
			dbesc($keywords),
			dbesc($gender)
		);
		$x = q("SELECT * FROM `gcontact` WHERE `nurl` = '%s' LIMIT 1",
			dbesc(normalise_link($profile_url))
		);
		if(count($x))
			$gcid = $x[0]['id'];
	}

	if(! $gcid)
		return $gcid;

	$r = q("SELECT * FROM `glink` WHERE `cid` = %d AND `uid` = %d AND `gcid` = %d AND `zcid` = %d LIMIT 1",
		intval($cid),
		intval($uid),
		intval($gcid),
		intval($zcid)
	);
	if(! count($r)) {
		q("INSERT INTO `glink` (`cid`,`uid`,`gcid`,`zcid`, `updated`) VALUES (%d,%d,%d,%d, '%s') ",
			intval($cid),
			intval($uid),
			intval($gcid),
			intval($zcid),
			dbesc(datetime_convert())
		);
	} else {
		q("UPDATE `glink` SET `updated` = '%s' WHERE `cid` = %d AND `uid` = %d AND `gcid` = %d AND `zcid` = %d",
			dbesc(datetime_convert()),
			intval($cid),
			intval($uid),
			intval($gcid),
			intval($zcid)
		);
	}

	// For unknown reasons there are sometimes duplicates
	q("DELETE FROM `gcontact` WHERE `nurl` = '%s' AND `id` != %d AND
		NOT EXISTS (SELECT `gcid` FROM `glink` WHERE `gcid` = `gcontact`.`id`)",
		dbesc(normalise_link($profile_url)),
		intval($gcid)
	);

	return $gcid;
}

function poco_contact_from_body($body, $created, $cid, $uid) {
	preg_replace_callback("/\[share(.*?)\].*?\[\/share\]/ism",
		function ($match) use ($created, $cid, $uid){
			return(sub_poco_from_share($match, $created, $cid, $uid));
		}, $body);
}

function sub_poco_from_share($share, $created, $cid, $uid) {
        $profile = "";
        preg_match("/profile='(.*?)'/ism", $share[1], $matches);
        if ($matches[1] != "")
                $profile = $matches[1];

        preg_match('/profile="(.*?)"/ism', $share[1], $matches);
        if ($matches[1] != "")
                $profile = $matches[1];

	if ($profile == "")
		return;

	logger("prepare poco_check for profile ".$profile, LOGGER_DEBUG);
        poco_check($profile, "", "", "", "", "", "", "", "", $created, $cid, $uid);
}

function count_common_friends($uid,$cid) {

	$r = q("SELECT count(*) as `total`
		FROM `glink` INNER JOIN `gcontact` on `glink`.`gcid` = `gcontact`.`id`
		where `glink`.`cid` = %d and `glink`.`uid` = %d
		and `gcontact`.`nurl` in (select nurl from contact where uid = %d and self = 0 and blocked = 0 and hidden = 0 and id != %d ) ",
		intval($cid),
		intval($uid),
		intval($uid),
		intval($cid)
	);

//	logger("count_common_friends: $uid $cid {$r[0]['total']}"); 
	if(count($r))
		return $r[0]['total'];
	return 0;

}


function common_friends($uid,$cid,$start = 0,$limit=9999,$shuffle = false) {

	if($shuffle)
		$sql_extra = " order by rand() ";
	else
		$sql_extra = " order by `gcontact`.`name` asc ";

	$r = q("SELECT `gcontact`.*
		FROM `glink` INNER JOIN `gcontact` on `glink`.`gcid` = `gcontact`.`id`
		where `glink`.`cid` = %d and `glink`.`uid` = %d
		and `gcontact`.`nurl` in (select nurl from contact where uid = %d and self = 0 and blocked = 0 and hidden = 0 and id != %d ) 
		$sql_extra limit %d, %d",
		intval($cid),
		intval($uid),
		intval($uid),
		intval($cid),
		intval($start),
		intval($limit)
	);

	return $r;

}


function count_common_friends_zcid($uid,$zcid) {

	$r = q("SELECT count(*) as `total`
		FROM `glink` INNER JOIN `gcontact` on `glink`.`gcid` = `gcontact`.`id`
		where `glink`.`zcid` = %d
		and `gcontact`.`nurl` in (select nurl from contact where uid = %d and self = 0 and blocked = 0 and hidden = 0 ) ",
		intval($zcid),
		intval($uid)
	);

	if(count($r))
		return $r[0]['total'];
	return 0;

}

function common_friends_zcid($uid,$zcid,$start = 0, $limit = 9999,$shuffle = false) {

	if($shuffle)
		$sql_extra = " order by rand() ";
	else
		$sql_extra = " order by `gcontact`.`name` asc ";

	$r = q("SELECT `gcontact`.*
		FROM `glink` INNER JOIN `gcontact` on `glink`.`gcid` = `gcontact`.`id`
		where `glink`.`zcid` = %d
		and `gcontact`.`nurl` in (select nurl from contact where uid = %d and self = 0 and blocked = 0 and hidden = 0 ) 
		$sql_extra limit %d, %d",
		intval($zcid),
		intval($uid),
		intval($start),
		intval($limit)
	);

	return $r;

}


function count_all_friends($uid,$cid) {

	$r = q("SELECT count(*) as `total`
		FROM `glink` INNER JOIN `gcontact` on `glink`.`gcid` = `gcontact`.`id`
		where `glink`.`cid` = %d and `glink`.`uid` = %d ",
		intval($cid),
		intval($uid)
	);

	if(count($r))
		return $r[0]['total'];
	return 0;

}


function all_friends($uid,$cid,$start = 0, $limit = 80) {

	$r = q("SELECT `gcontact`.*
		FROM `glink` INNER JOIN `gcontact` on `glink`.`gcid` = `gcontact`.`id`
		where `glink`.`cid` = %d and `glink`.`uid` = %d
		order by `gcontact`.`name` asc LIMIT %d, %d ",
		intval($cid),
		intval($uid),
		intval($start),
		intval($limit)
	);

	return $r;
}



function suggestion_query($uid, $start = 0, $limit = 80) {

	if(! $uid)
		return array();

	$network = array(NETWORK_DFRN);

	if (get_config('system','diaspora_enabled'))
		$network[] = NETWORK_DIASPORA;

	if (!get_config('system','ostatus_disabled'))
		$network[] = NETWORK_OSTATUS;

	$sql_network = implode("', '", $network);
	//$sql_network = "'".$sql_network."', ''";
	$sql_network = "'".$sql_network."'";

	$r = q("SELECT count(glink.gcid) as `total`, gcontact.* from gcontact
		INNER JOIN glink on glink.gcid = gcontact.id
		where uid = %d and not gcontact.nurl in ( select nurl from contact where uid = %d )
		and not gcontact.name in ( select name from contact where uid = %d )
		and not gcontact.id in ( select gcid from gcign where uid = %d )
		AND `gcontact`.`updated` != '0000-00-00 00:00:00'
		AND `gcontact`.`network` IN (%s)
		group by glink.gcid order by gcontact.updated desc,total desc limit %d, %d ",
		intval($uid),
		intval($uid),
		intval($uid),
		intval($uid),
		$sql_network,
		intval($start),
		intval($limit)
	);

	if(count($r) && count($r) >= ($limit -1))
		return $r;

	$r2 = q("SELECT gcontact.* from gcontact
		INNER JOIN glink on glink.gcid = gcontact.id
		where glink.uid = 0 and glink.cid = 0 and glink.zcid = 0 and not gcontact.nurl in ( select nurl from contact where uid = %d )
		and not gcontact.name in ( select name from contact where uid = %d )
		and not gcontact.id in ( select gcid from gcign where uid = %d )
		AND `gcontact`.`updated` != '0000-00-00 00:00:00'
		AND `gcontact`.`network` IN (%s)
		order by rand() limit %d, %d ",
		intval($uid),
		intval($uid),
		intval($uid),
		$sql_network,
		intval($start),
		intval($limit)
	);

	$list = array();
	foreach ($r2 AS $suggestion)
		$list[$suggestion["nurl"]] = $suggestion;

	foreach ($r AS $suggestion)
		$list[$suggestion["nurl"]] = $suggestion;

	return $list;
}

function update_suggestions() {

	$a = get_app();

	$done = array();

	poco_load(0,0,0,$a->get_baseurl() . '/poco');

	$done[] = $a->get_baseurl() . '/poco';

	if(strlen(get_config('system','directory_submit_url'))) {
		$x = fetch_url('http://dir.friendica.com/pubsites');
		if($x) {
			$j = json_decode($x);
			if($j->entries) {
				foreach($j->entries as $entry) {
					$url = $entry->url . '/poco';
					if(! in_array($url,$done))
						poco_load(0,0,0,$entry->url . '/poco');
				}
			}
		}
	}

	$r = q("select distinct(poco) as poco from contact where network = '%s'",
		dbesc(NETWORK_DFRN)
	);

	if(count($r)) {
		foreach($r as $rr) {
			$base = substr($rr['poco'],0,strrpos($rr['poco'],'/'));
			if(! in_array($base,$done))
				poco_load(0,0,0,$base);
		}
	}
}
