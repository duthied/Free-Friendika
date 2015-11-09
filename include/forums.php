<?php

/**
 * @file include/forums.php
 * @brief functions related to forum functionality * 
 */


/**
 * @brief function to list all forums a user is connected with
 * 
 * @param int $uid of the profile owner
 * @param boolean $showhidden
 *	show frorums which are not hidden
 * @param boolean $lastitem
 *	sort by lastitem
 * @param boolean $showprivate
 *	show private groups
 * 
 * @returns array
 *	'url'	=> forum url
 *	'name'	=> forum name
 *	'id'	=> number of the key from the array
 *	'micro' => contact photo in format micro
 */
function get_forumlist($uid, $showhidden = true, $lastitem, $showprivate = false) {

	$forumlist = array();

	$order = (($showhidden) ? '' : " AND `hidden` = 0 ");
	$order .= (($lastitem) ? ' ORDER BY `last-item` ASC ' : ' ORDER BY `name` ASC ');
	$select = "`forum` = 1";
	if ($showprivate) {
		$select = "( `forum` = 1 OR `prv` = 1 )";
	}

	$contacts = q("SELECT `contact`.`id`, `contact`.`url`, `contact`.`name`, `contact`.`micro` FROM contact 
			WHERE `network`= 'dfrn' AND $select AND `uid` = %d
			AND `blocked` = 0 AND `hidden` = 0 AND `pending` = 0 AND `archive` = 0
			$order ",
			intval($uid)
	);

	foreach($contacts as $contact) {
		$forumlist[] = array(
			'url'	=> $contact['url'],
			'name'	=> $contact['name'],
			'id'	=> $contact['id'],
			'micro' => $contact['micro'],
		);
	}
	return($forumlist);
}


/*
 * @brief forumlist widget
 * 
 * Sidebar widget to show subcribed friendica forums. If activated
 * in the settings, it appears at the notwork page sidebar
 * 
 * @param App $a
 * @return string
 */
function widget_forumlist($a) {

	if(! intval(feature_enabled(local_user(),'forumlist')))
		return;

	$o = '';

	//sort by last updated item
	$lastitem = true;

	$contacts = get_forumlist($a->user['uid'],true,$lastitem, true);
	$total = count($contacts);
	$visible_forums = 10;

	if(count($contacts)) {

		$id = 0;

		foreach($contacts as $contact) {

			$entry = array(
				'url' => $a->get_baseurl() . '/network?f=&cid=' . $contact['id'],
				'external_url' => $a->get_baseurl() . '/redir/' . $contact['id'],
				'name' => $contact['name'],
				'micro' => proxy_url($contact['micro'], false, PROXY_SIZE_MICRO),
				'id' => ++$id,
			);
			$entries[] = $entry;
		}

		$tpl = get_markup_template('widget_forumlist.tpl');

		$o .= replace_macros($tpl,array(
			'$title'	=> t("Forums"),
			'$forums'	=> $entries,
			'$link_desc'	=> t('External link to forum'),
			'$total'	=> $total,
			'$visible_forums' => $visible_forums,
			'$showmore'	=> t('show more'),
		));
	}

	return $o;
}

/*
 * @brief format forumlist as contact block
 * 
 * This function is used to show the forumlist in
 * the advanced profile.
 * 
 * @param int $uid
 * @return string
 * 
 */
function forumlist_profile_advanced($uid) {

	$profile = intval(feature_enabled($uid,'forumlist_profile'));
	if(! $profile)
		return;

	$o = '';

	// place holder in case somebody wants configurability
	$show_total = 9999;

	//don't sort by last updated item
	$lastitem = false;

	$contacts = get_forumlist($uid,false,$lastitem,false);

	$total_shown = 0;

	foreach($contacts as $contact) {
		$forumlist .= micropro($contact,false,'forumlist-profile-advanced');
		$total_shown ++;
		if($total_shown == $show_total)
			break;
	}

	if(count($contacts) > 0)
		$o .= $forumlist;
		return $o;
}