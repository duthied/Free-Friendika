<?php


// Included here for completeness, but this is a very dangerous operation.
// It is the caller's responsibility to confirm the requestor's intent and
// authorisation to do this.

function user_remove($uid) {
	if(! $uid)
		return;
	$a = get_app();
	logger('Removing user: ' . $uid);

	$r = q("select * from user where uid = %d limit 1", intval($uid));

	call_hooks('remove_user',$r[0]);

	// save username (actually the nickname as it is guaranteed
	// unique), so it cannot be re-registered in the future.

	q("insert into userd ( username ) values ( '%s' )",
		$r[0]['nickname']
	);

	// don't delete yet, will be done later when contacts have deleted my stuff
	// q("DELETE FROM `contact` WHERE `uid` = %d", intval($uid));
	q("DELETE FROM `gcign` WHERE `uid` = %d", intval($uid));
	q("DELETE FROM `group` WHERE `uid` = %d", intval($uid));
	q("DELETE FROM `group_member` WHERE `uid` = %d", intval($uid));
	q("DELETE FROM `intro` WHERE `uid` = %d", intval($uid));
	q("DELETE FROM `event` WHERE `uid` = %d", intval($uid));
	q("DELETE FROM `item` WHERE `uid` = %d", intval($uid));
	q("DELETE FROM `item_id` WHERE `uid` = %d", intval($uid));
	q("DELETE FROM `mail` WHERE `uid` = %d", intval($uid));
	q("DELETE FROM `mailacct` WHERE `uid` = %d", intval($uid));
	q("DELETE FROM `manage` WHERE `uid` = %d", intval($uid));
	q("DELETE FROM `notify` WHERE `uid` = %d", intval($uid));
	q("DELETE FROM `photo` WHERE `uid` = %d", intval($uid));
	q("DELETE FROM `attach` WHERE `uid` = %d", intval($uid));
	q("DELETE FROM `profile` WHERE `uid` = %d", intval($uid));
	q("DELETE FROM `profile_check` WHERE `uid` = %d", intval($uid));
	q("DELETE FROM `pconfig` WHERE `uid` = %d", intval($uid));
	q("DELETE FROM `search` WHERE `uid` = %d", intval($uid));
	q("DELETE FROM `spam` WHERE `uid` = %d", intval($uid));
	// don't delete yet, will be done later when contacts have deleted my stuff
	// q("DELETE FROM `user` WHERE `uid` = %d", intval($uid));
	q("UPDATE `user` SET `account_removed` = 1, `account_expires_on` = UTC_TIMESTAMP() WHERE `uid` = %d", intval($uid));
	proc_run('php', "include/notifier.php", "removeme", $uid);

	// Send an update to the directory
	proc_run('php', "include/directory.php", $r[0]['url']);

	if($uid == local_user()) {
		unset($_SESSION['authenticated']);
		unset($_SESSION['uid']);
		goaway($a->get_baseurl());
	}
}


function contact_remove($id) {

	$r = q("select uid from contact where id = %d limit 1",
		intval($id)
	);
	if((! count($r)) || (! intval($r[0]['uid'])))
		return;

	$archive = get_pconfig($r[0]['uid'], 'system','archive_removed_contacts');
	if($archive) {
		q("update contact set `archive` = 1, `network` = 'none', `writable` = 0 where id = %d",
			intval($id)
		);
		return;
	}

	q("DELETE FROM `contact` WHERE `id` = %d",
		intval($id)
	);
	q("DELETE FROM `item` WHERE `contact-id` = %d ",
		intval($id)
	);
	q("DELETE FROM `photo` WHERE `contact-id` = %d ",
		intval($id)
	);
	q("DELETE FROM `mail` WHERE `contact-id` = %d ",
		intval($id)
	);
	q("DELETE FROM `event` WHERE `cid` = %d ",
		intval($id)
	);
	q("DELETE FROM `queue` WHERE `cid` = %d ",
		intval($id)
	);

}


// sends an unfriend message. Does not remove the contact

function terminate_friendship($user,$self,$contact) {


	$a = get_app();

	require_once('include/datetime.php');

	if($contact['network'] === NETWORK_OSTATUS) {

		$slap = replace_macros(get_markup_template('follow_slap.tpl'), array(
			'$name' => $user['username'],
			'$profile_page' => $a->get_baseurl() . '/profile/' . $user['nickname'],
			'$photo' => $self['photo'],
			'$thumb' => $self['thumb'],
			'$published' => datetime_convert('UTC','UTC', 'now', ATOM_TIME),
			'$item_id' => 'urn:X-dfrn:' . $a->get_hostname() . ':unfollow:' . get_guid(32),
			'$title' => '',
			'$type' => 'text',
			'$content' => t('stopped following'),
			'$nick' => $user['nickname'],
			'$verb' => 'http://ostatus.org/schema/1.0/unfollow', // ACTIVITY_UNFOLLOW,
			'$ostat_follow' => '' // '<as:verb>http://ostatus.org/schema/1.0/unfollow</as:verb>' . "\r\n"
		));

		if((x($contact,'notify')) && (strlen($contact['notify']))) {
			require_once('include/salmon.php');
			slapper($user,$contact['notify'],$slap);
		}
	}
	elseif($contact['network'] === NETWORK_DIASPORA) {
		require_once('include/diaspora.php');
		diaspora_unshare($user,$contact);
	}
	elseif($contact['network'] === NETWORK_DFRN) {
		require_once('include/items.php');
		dfrn_deliver($user,$contact,'placeholder', 1);
	}

}


// Contact has refused to recognise us as a friend. We will start a countdown.
// If they still don't recognise us in 32 days, the relationship is over,
// and we won't waste any more time trying to communicate with them.
// This provides for the possibility that their database is temporarily messed
// up or some other transient event and that there's a possibility we could recover from it.

if(! function_exists('mark_for_death')) {
function mark_for_death($contact) {

	if($contact['archive'])
		return;

	if($contact['term-date'] == '0000-00-00 00:00:00') {
		q("UPDATE `contact` SET `term-date` = '%s' WHERE `id` = %d",
				dbesc(datetime_convert()),
				intval($contact['id'])
		);
	}
	else {

		/// @todo 
		/// We really should send a notification to the owner after 2-3 weeks
		/// so they won't be surprised when the contact vanishes and can take
		/// remedial action if this was a serious mistake or glitch

		$expiry = $contact['term-date'] . ' + 32 days ';
		if(datetime_convert() > datetime_convert('UTC','UTC',$expiry)) {

			// relationship is really truly dead.
			// archive them rather than delete
			// though if the owner tries to unarchive them we'll start the whole process over again

			q("update contact set `archive` = 1 where id = %d",
				intval($contact['id'])
			);
			q("UPDATE `item` SET `private` = 2 WHERE `contact-id` = %d AND `uid` = %d", intval($contact['id']), intval($contact['uid']));

			//contact_remove($contact['id']);

		}
	}

}}

if(! function_exists('unmark_for_death')) {
function unmark_for_death($contact) {
	// It's a miracle. Our dead contact has inexplicably come back to life.
	q("UPDATE `contact` SET `term-date` = '%s' WHERE `id` = %d",
		dbesc('0000-00-00 00:00:00'),
		intval($contact['id'])
	);
}}

function get_contact_details_by_url($url, $uid = -1) {
	if ($uid == -1)
		$uid = local_user();

	$r = q("SELECT `id` AS `gid`, `url`, `name`, `nick`, `addr`, `photo`, `location`, `about`, `keywords`, `gender`, `community`, `network` FROM `gcontact` WHERE `nurl` = '%s' LIMIT 1",
		dbesc(normalise_link($url)));

	if ($r) {
		$profile = $r[0];

		if ((($profile["addr"] == "") OR ($profile["name"] == "")) AND
			in_array($profile["network"], array(NETWORK_DFRN, NETWORK_DIASPORA, NETWORK_OSTATUS)))
			proc_run('php',"include/update_gcontact.php", $profile["gid"]);
	}

	// Fetching further contact data from the contact table
	$r = q("SELECT `id`, `uid`, `url`, `network`, `name`, `nick`, `addr`, `location`, `about`, `keywords`, `gender`, `photo`, `addr`, `forum`, `prv`, `bd` FROM `contact` WHERE `nurl` = '%s' AND `uid` = %d AND `network` = '%s'",
		dbesc(normalise_link($url)), intval($uid), dbesc($profile["network"]));

	if (!count($r))
		$r = q("SELECT `id`, `uid`, `url`, `network`, `name`, `nick`, `addr`, `location`, `about`, `keywords`, `gender`, `photo`, `addr`, `forum`, `prv`, `bd` FROM `contact` WHERE `nurl` = '%s' AND `uid` = %d",
			dbesc(normalise_link($url)), intval($uid));

	if (!count($r))
		$r = q("SELECT `id`, `uid`, `url`, `network`, `name`, `nick`, `addr`, `location`, `about`, `keywords`, `gender`, `photo`, `addr`, `forum`, `prv`, `bd` FROM `contact` WHERE `nurl` = '%s' AND `uid` = 0",
			dbesc(normalise_link($url)));

	if ($r) {
		if (isset($r[0]["url"]) AND $r[0]["url"])
			$profile["url"] = $r[0]["url"];
		if (isset($r[0]["name"]) AND $r[0]["name"])
			$profile["name"] = $r[0]["name"];
		if (isset($r[0]["nick"]) AND $r[0]["nick"] AND ($profile["nick"] == ""))
			$profile["nick"] = $r[0]["nick"];
		if (isset($r[0]["addr"]) AND $r[0]["addr"] AND ($profile["addr"] == ""))
			$profile["addr"] = $r[0]["addr"];
		if (isset($r[0]["photo"]) AND $r[0]["photo"])
			$profile["photo"] = $r[0]["photo"];
		if (isset($r[0]["location"]) AND $r[0]["location"])
			$profile["location"] = $r[0]["location"];
		if (isset($r[0]["about"]) AND $r[0]["about"])
			$profile["about"] = $r[0]["about"];
		if (isset($r[0]["keywords"]) AND $r[0]["keywords"])
			$profile["keywords"] = $r[0]["keywords"];
		if (isset($r[0]["gender"]) AND $r[0]["gender"])
			$profile["gender"] = $r[0]["gender"];
		if (isset($r[0]["forum"]) OR isset($r[0]["prv"]))
			$profile["community"] = ($r[0]["forum"] OR $r[0]["prv"]);
		if (isset($r[0]["network"]) AND $r[0]["network"])
			$profile["network"] = $r[0]["network"];
		if (isset($r[0]["addr"]) AND $r[0]["addr"])
			$profile["addr"] = $r[0]["addr"];
		if (isset($r[0]["bd"]) AND $r[0]["bd"])
			$profile["bd"] = $r[0]["bd"];
		if ($r[0]["uid"] == 0)
			$profile["cid"] = 0;
		else
			$profile["cid"] = $r[0]["id"];
	} else
		$profile["cid"] = 0;

	if (($profile["cid"] == 0) AND ($profile["network"] == NETWORK_DIASPORA)) {
		$profile["location"] = "";
		$profile["about"] = "";
	}

	return($profile);
}

if(! function_exists('contact_photo_menu')){
function contact_photo_menu($contact, $uid = 0) {

	$a = get_app();

	$contact_url="";
	$pm_url="";
	$status_link="";
	$photos_link="";
	$posts_link="";
	$contact_drop_link = "";
	$poke_link="";

	if ($uid == 0)
		$uid = local_user();

	if ($contact["uid"] != $uid) {
		if ($uid == 0) {
			$profile_link = zrl($contact['url']);
			$menu = Array('profile' => array(t("View Profile"), $profile_link, true));

			return $menu;
		}

		$r = q("SELECT * FROM `contact` WHERE `nurl` = '%s' AND `network` = '%s' AND `uid` = %d",
			dbesc($contact["nurl"]), dbesc($contact["network"]), intval($uid));
		if ($r)
			return contact_photo_menu($r[0], $uid);
		else {
			$profile_link = zrl($contact['url']);
			$connlnk = 'follow/?url='.$contact['url'];
			$menu = Array(
				'profile' => array(t("View Profile"), $profile_link, true),
				'follow' => array(t("Connect/Follow"), $connlnk, true)
				);

			return $menu;
		}
	}

	$sparkle = false;
	if($contact['network'] === NETWORK_DFRN) {
		$sparkle = true;
		$profile_link = $a->get_baseurl() . '/redir/' . $contact['id'];
	}
	else
		$profile_link = $contact['url'];

	if($profile_link === 'mailbox')
		$profile_link = '';

	if($sparkle) {
		$status_link = $profile_link . "?url=status";
		$photos_link = $profile_link . "?url=photos";
		$profile_link = $profile_link . "?url=profile";
	}

	if (in_array($contact["network"], array(NETWORK_DFRN, NETWORK_DIASPORA)))
		$pm_url = $a->get_baseurl() . '/message/new/' . $contact['id'];

	if ($contact["network"] == NETWORK_DFRN)
		$poke_link = $a->get_baseurl() . '/poke/?f=&c=' . $contact['id'];

	$contact_url = $a->get_baseurl() . '/contacts/' . $contact['id'];
	$posts_link = $a->get_baseurl() . "/contacts/" . $contact['id'] . '/posts';
	$contact_drop_link = $a->get_baseurl() . "/contacts/" . $contact['id'] . '/drop?confirm=1';


	/**
	 * menu array:
	 * "name" => [ "Label", "link", (bool)Should the link opened in a new tab? ]
	 */
	$menu = Array(
		'status' => array(t("View Status"), $status_link, true),
		'profile' => array(t("View Profile"), $profile_link, true),
		'photos' => array(t("View Photos"), $photos_link,true),
		'network' => array(t("Network Posts"), $posts_link,false),
		'edit' => array(t("Edit Contact"), $contact_url, false),
		'drop' => array(t("Drop Contact"), $contact_drop_link, false),
		'pm' => array(t("Send PM"), $pm_url, false),
		'poke' => array(t("Poke"), $poke_link, false),
	);


	$args = array('contact' => $contact, 'menu' => &$menu);

	call_hooks('contact_photo_menu', $args);

	$menucondensed = array();

	foreach ($menu AS $menuname=>$menuitem)
		if ($menuitem[1] != "")
			$menucondensed[$menuname] = $menuitem;

	return $menucondensed;
}}


function random_profile() {
	$r = q("SELECT `url` FROM `gcontact` WHERE `network` = '%s'
				AND `last_contact` >= `last_failure`
				AND `updated` > UTC_TIMESTAMP - INTERVAL 1 MONTH
			ORDER BY rand() LIMIT 1",
		dbesc(NETWORK_DFRN));

	if(count($r))
		return dirname($r[0]['url']);
	return '';
}


function contacts_not_grouped($uid,$start = 0,$count = 0) {

	if(! $count) {
		$r = q("select count(*) as total from contact where uid = %d and self = 0 and id not in (select distinct(`contact-id`) from group_member where uid = %d) ",
			intval($uid),
			intval($uid)
		);

		return $r;


	}

	$r = q("select * from contact where uid = %d and self = 0 and id not in (select distinct(`contact-id`) from group_member where uid = %d) and blocked = 0 and pending = 0 limit %d, %d",
		intval($uid),
		intval($uid),
		intval($start),
		intval($count)
	);

	return $r;
}

function get_contact($url, $uid = 0) {
	require_once("include/Scrape.php");

	$data = array();
	$contactid = 0;

	// is it an address in the format user@server.tld?
	if (!strstr($url, "http") OR strstr($url, "@")) {
		$data = probe_url($url);
		$url = $data["url"];
		if ($url == "")
			return 0;
	}

	$contact = q("SELECT `id`, `avatar-date` FROM `contact` WHERE `nurl` = '%s' AND `uid` = %d",
			dbesc(normalise_link($url)),
			intval($uid));

	if (!$contact)
		$contact = q("SELECT `id`, `avatar-date` FROM `contact` WHERE `alias` IN ('%s', '%s') AND `uid` = %d",
				dbesc($url),
				dbesc(normalise_link($url)),
				intval($uid));

	if ($contact) {
		$contactid = $contact[0]["id"];

		// Update the contact every 7 days
		$update_photo = ($contact[0]['avatar-date'] < datetime_convert('','','now -7 days'));
		//$update_photo = ($contact[0]['avatar-date'] < datetime_convert('','','now -12 hours'));

		if (!$update_photo)
			return($contactid);
	} elseif ($uid != 0)
		return 0;

	if (!count($data))
		$data = probe_url($url);

	// Does this address belongs to a valid network?
	if (!in_array($data["network"], array(NETWORK_DFRN, NETWORK_OSTATUS, NETWORK_DIASPORA)))
		return 0;

	// tempory programming. Can be deleted after 2015-02-07
	if (($data["alias"] == "") AND (normalise_link($data["url"]) != normalise_link($url)))
		$data["alias"] = normalise_link($url);

	if ($contactid == 0) {
		q("INSERT INTO `contact` (`uid`, `created`, `url`, `nurl`, `addr`, `alias`, `notify`, `poll`,
					`name`, `nick`, `photo`, `network`, `pubkey`, `rel`, `priority`,
					`batch`, `request`, `confirm`, `poco`,
					`writable`, `blocked`, `readonly`, `pending`)
					VALUES (%d, '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', %d, %d, '%s', '%s', '%s', '%s', 1, 0, 0, 0)",
			intval($uid),
			dbesc(datetime_convert()),
			dbesc($data["url"]),
			dbesc(normalise_link($data["url"])),
			dbesc($data["addr"]),
			dbesc($data["alias"]),
			dbesc($data["notify"]),
			dbesc($data["poll"]),
			dbesc($data["name"]),
			dbesc($data["nick"]),
			dbesc($data["photo"]),
			dbesc($data["network"]),
			dbesc($data["pubkey"]),
			intval(CONTACT_IS_SHARING),
			intval($data["priority"]),
			dbesc($data["batch"]),
			dbesc($data["request"]),
			dbesc($data["confirm"]),
			dbesc($data["poco"])
		);

		$contact = q("SELECT `id` FROM `contact` WHERE `nurl` = '%s' AND `uid` = %d",
				dbesc(normalise_link($data["url"])),
				intval($uid));
		if (!$contact)
			return 0;

		$contactid = $contact[0]["id"];
	}

	require_once("Photo.php");

	$photos = import_profile_photo($data["photo"],$uid,$contactid);

	q("UPDATE `contact` SET `photo` = '%s', `thumb` = '%s', `micro` = '%s',
		`addr` = '%s', `alias` = '%s', `name` = '%s', `nick` = '%s',
		`name-date` = '%s', `uri-date` = '%s', `avatar-date` = '%s' WHERE `id` = %d",
		dbesc($photos[0]),
		dbesc($photos[1]),
		dbesc($photos[2]),
		dbesc($data["addr"]),
		dbesc($data["alias"]),
		dbesc($data["name"]),
		dbesc($data["nick"]),
		dbesc(datetime_convert()),
		dbesc(datetime_convert()),
		dbesc(datetime_convert()),
		intval($contactid)
	);

	return $contactid;
}

/**
 * @brief Returns posts from a given gcontact
 *
 * @param App $a argv application class
 * @param int $gcontact_id Global contact
 *
 * @return string posts in HTML
 */
function posts_from_gcontact($a, $gcontact_id) {

	require_once('include/conversation.php');

	// There are no posts with "uid = 0" with connector networks
	// This speeds up the query a lot
	$r = q("SELECT `network` FROM `gcontact` WHERE `id` = %d", dbesc($gcontact_id));
	if (in_array($r[0]["network"], array(NETWORK_DFRN, NETWORK_DIASPORA, NETWORK_OSTATUS, "")))
		$sql = "(`item`.`uid` = 0 OR  (`item`.`uid` = %d AND `item`.`private`))";
	else
		$sql = "`item`.`uid` = %d";

	if(get_config('system', 'old_pager')) {
		$r = q("SELECT COUNT(*) AS `total` FROM `item`
			WHERE `gcontact-id` = %d and $sql",
			intval($gcontact_id),
			intval(local_user()));

		$a->set_pager_total($r[0]['total']);
	}

	$r = q("SELECT `item`.`uri`, `item`.*, `item`.`id` AS `item_id`,
			`author-name` AS `name`, `owner-avatar` AS `photo`,
			`owner-link` AS `url`, `owner-avatar` AS `thumb`
		FROM `item` FORCE INDEX (`gcontactid_uid_created`)
		WHERE `gcontact-id` = %d AND $sql AND
			NOT `deleted` AND NOT `moderated` AND `visible`
		ORDER BY `item`.`created` DESC LIMIT %d, %d",
		intval($gcontact_id),
		intval(local_user()),
		intval($a->pager['start']),
		intval($a->pager['itemspage'])
	);

	$o = conversation($a,$r,'community',false);

	if(!get_config('system', 'old_pager')) {
		$o .= alt_pager($a,count($r));
	} else {
		$o .= paginate($a);
	}

	return $o;
}

/**
 * @brief set the gcontact-id in all item entries
 *
 * This job has to be started multiple times until all entries are set.
 * It isn't started in the update function since it would consume too much time and can be done in the background.
 */
function item_set_gcontact() {
	define ('POST_UPDATE_VERSION', 1192);

	// Was the script completed?
	if (get_config("system", "post_update_version") >= POST_UPDATE_VERSION)
		return;

	// Check if the first step is done (Setting "gcontact-id" in the item table)
	$r = q("SELECT `author-link`, `author-name`, `author-avatar`, `uid`, `network` FROM `item` WHERE `gcontact-id` = 0 LIMIT 1000");
	if (!$r) {
		// Are there unfinished entries in the thread table?
		$r = q("SELECT COUNT(*) AS `total` FROM `thread`
			INNER JOIN `item` ON `item`.`id` =`thread`.`iid`
			WHERE `thread`.`gcontact-id` = 0 AND
				(`thread`.`uid` IN (SELECT `uid` from `user`) OR `thread`.`uid` = 0)");

		if ($r AND ($r[0]["total"] == 0)) {
			set_config("system", "post_update_version", POST_UPDATE_VERSION);
			return false;
		}

		// Update the thread table from the item table
		q("UPDATE `thread` INNER JOIN `item` ON `item`.`id`=`thread`.`iid`
				SET `thread`.`gcontact-id` = `item`.`gcontact-id`
			WHERE `thread`.`gcontact-id` = 0 AND
				(`thread`.`uid` IN (SELECT `uid` from `user`) OR `thread`.`uid` = 0)");

		return false;
	}

	$item_arr = array();
	foreach ($r AS $item) {
		$index = $item["author-link"]."-".$item["uid"];
		$item_arr[$index] = array("author-link" => $item["author-link"],
						"uid" => $item["uid"],
						"network" => $item["network"]);
	}

	// Set the "gcontact-id" in the item table and add a new gcontact entry if needed
	foreach($item_arr AS $item) {
		$gcontact_id = get_gcontact_id(array("url" => $item['author-link'], "network" => $item['network'],
						"photo" => $item['author-avatar'], "name" => $item['author-name']));
		q("UPDATE `item` SET `gcontact-id` = %d WHERE `uid` = %d AND `author-link` = '%s' AND `gcontact-id` = 0",
			intval($gcontact_id), intval($item["uid"]), dbesc($item["author-link"]));
	}
	return true;
}

/**
 * @brief Returns posts from a given contact
 *
 * @param App $a argv application class
 * @param int $contact_id contact
 *
 * @return string posts in HTML
 */
function posts_from_contact($a, $contact_id) {

	require_once('include/conversation.php');

	$r = q("SELECT `url` FROM `contact` WHERE `id` = %d", intval($contact_id));
	if (!$r)
		return false;

	$contact = $r[0];

	if(get_config('system', 'old_pager')) {
		$r = q("SELECT COUNT(*) AS `total` FROM `item`
			WHERE `item`.`uid` = %d AND `author-link` IN ('%s', '%s')",
			intval(local_user()),
			dbesc(str_replace("https://", "http://", $contact["url"])),
			dbesc(str_replace("http://", "https://", $contact["url"])));

		$a->set_pager_total($r[0]['total']);
	}

	$r = q("SELECT `item`.`uri`, `item`.*, `item`.`id` AS `item_id`,
			`author-name` AS `name`, `owner-avatar` AS `photo`,
			`owner-link` AS `url`, `owner-avatar` AS `thumb`
		FROM `item` FORCE INDEX (`uid_contactid_created`)
		WHERE `item`.`uid` = %d AND `contact-id` = %d
			AND `author-link` IN ('%s', '%s')
			AND NOT `deleted` AND NOT `moderated` AND `visible`
		ORDER BY `item`.`created` DESC LIMIT %d, %d",
		intval(local_user()),
		intval($contact_id),
		dbesc(str_replace("https://", "http://", $contact["url"])),
		dbesc(str_replace("http://", "https://", $contact["url"])),
		intval($a->pager['start']),
		intval($a->pager['itemspage'])
	);

	$o .= conversation($a,$r,'community',false);

	if(!get_config('system', 'old_pager'))
		$o .= alt_pager($a,count($r));
	else
		$o .= paginate($a);

	return $o;
}
?>
