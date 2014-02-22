<?php


function network_init(&$a) {
	if(! local_user()) {
		notice( t('Permission denied.') . EOL);
		return;
	}

	$is_a_date_query = false;

	if($a->argc > 1) {
		for($x = 1; $x < $a->argc; $x ++) {
			if(is_a_date_arg($a->argv[$x])) {
				$is_a_date_query = true;
				break;
			}
		}
	}

    // convert query string to array and remove first element (which is friendica args)
    $query_array = array();
    parse_str($a->query_string, $query_array);
    array_shift($query_array);

	// fetch last used network view and redirect if needed
	if(! $is_a_date_query) {
		$sel_tabs = network_query_get_sel_tab($a);
		$sel_nets = network_query_get_sel_net();
		$sel_groups = network_query_get_sel_group($a);
		$last_sel_tabs = get_pconfig(local_user(), 'network.view','tab.selected');
		$last_sel_nets = get_pconfig(local_user(), 'network.view', 'net.selected');
		$last_sel_groups = get_pconfig(local_user(), 'network.view', 'group.selected');

		$remember_tab = ($sel_tabs[0] === 'active' && is_array($last_sel_tabs) && $last_sel_tabs[0] !== 'active');
		$remember_net = ($sel_nets === false && $last_sel_nets && $last_sel_nets !== 'all');
		$remember_group = ($sel_groups === false && $last_sel_groups && $last_sel_groups != 0);

		$net_baseurl = '/network';
		$net_args = array();

		if($remember_group) {
			$net_baseurl .= '/' . $last_sel_groups; // Note that the group number must come before the "/new" tab selection
		}
		else if($sel_groups !== false) {
			$net_baseurl .= '/' . $sel_groups;
		}

		if($remember_tab) {
			// redirect if current selected tab is '/network' and
			// last selected tab is _not_ '/network?f=&order=comment'. 
			// and this isn't a date query

			$tab_baseurls = array(
				'',		//all
				'',		//postord
				'',		//conv
				'/new',	//new
				'',		//starred
				'',		//bookmarked
				'',		//spam
			);
			$tab_args = array(
				'f=&order=comment',	//all
				'f=&order=post',		//postord
				'f=&conv=1',			//conv
				'',					//new
				'f=&star=1',			//starred
				'f=&bmark=1',			//bookmarked
				'f=&spam=1',			//spam
			);

			$k = array_search('active', $last_sel_tabs);

			$net_baseurl .= $tab_baseurls[$k];

            // parse out tab queries
            $dest_qa = array();
            $dest_qs = $tab_args[$k];
            parse_str( $dest_qs, $dest_qa);
            $net_args = array_merge($net_args, $dest_qa);
		}
		else if($sel_tabs[4] === 'active') {
			// The '/new' tab is selected
			$net_baseurl .= '/new';
		}

		if($remember_net) {
			$net_args['nets'] = $last_sel_nets;
		}

		if($remember_tab || $remember_net || $remember_group) {
            $net_args = array_merge($query_array, $net_args);
            $net_queries = build_querystring($net_args);

            // groups filter is in form of "network/nnn". Add it to $dest_url, if it's possible
            //if ($a->argc==2 && is_numeric($a->argv[1]) && strpos($net_baseurl, "/",1)===false){
            //    $net_baseurl .= "/".$a->argv[1];
            //}

			$redir_url = ($net_queries ? $net_baseurl."?".$net_queries : $net_baseurl);
			goaway($a->get_baseurl() . $redir_url);
		}
	}

/*	$sel_tabs = network_query_get_sel_tab($a);
	$last_sel_tabs = get_pconfig(local_user(), 'network.view','tab.selected');
	if (is_array($last_sel_tabs)){
		$tab_urls = array(
			'/network?f=&order=comment',//all
			'/network?f=&order=post',		//postord
			'/network?f=&conv=1',			//conv
			'/network/new',					//new
			'/network?f=&star=1',			//starred
			'/network?f=&bmark=1',			//bookmarked
			'/network?f=&spam=1',			//spam
		);

		// redirect if current selected tab is 'no_active' and
		// last selected tab is _not_ 'all_active'.
		// and this isn't a date query

		if ($sel_tabs[0] == 'active' && $last_sel_tabs[0]!='active' && (! $is_a_date_query)) {
			$k = array_search('active', $last_sel_tabs);

            // merge tab querystring with request querystring
            $dest_qa = array();
            list($dest_url,$dest_qs) = explode("?", $tab_urls[$k]);
            parse_str( $dest_qs, $dest_qa);
            $dest_qa = array_merge($query_array, $dest_qa);
            $dest_qs = build_querystring($dest_qa);

            // groups filter is in form of "network/nnn". Add it to $dest_url, if it's possible
            if ($a->argc==2 && is_numeric($a->argv[1]) && strpos($dest_url, "/",1)===false){
                $dest_url .= "/".$a->argv[1];
            }

			goaway($a->get_baseurl() . $dest_url."?".$dest_qs);
		}
	}*/

	if(x($_GET['nets']) && $_GET['nets'] === 'all')
		unset($_GET['nets']);

	$group_id = (($a->argc > 1 && is_numeric($a->argv[1])) ? intval($a->argv[1]) : 0);

	set_pconfig(local_user(), 'network.view', 'group.selected', $group_id);

	require_once('include/group.php');
	require_once('include/contact_widgets.php');
	require_once('include/items.php');

	if(! x($a->page,'aside'))
		$a->page['aside'] = '';

	$search = ((x($_GET,'search')) ? escape_tags($_GET['search']) : '');

	if(x($_GET,'save')) {
		$r = q("select * from `search` where `uid` = %d and `term` = '%s' limit 1",
			intval(local_user()),
			dbesc($search)
		);
		if(! count($r)) {
			q("insert into `search` ( `uid`,`term` ) values ( %d, '%s') ",
				intval(local_user()),
				dbesc($search)
			);
		}
	}
	if(x($_GET,'remove')) {
		q("delete from `search` where `uid` = %d and `term` = '%s'",
			intval(local_user()),
			dbesc($search)
		);
	}

	// search terms header
	if(x($_GET,'search')) {
		$a->page['content'] .= '<h2>' . t('Search Results For:') . ' '  . $search . '</h2>';
	}

	$a->page['aside'] .= (feature_enabled(local_user(),'groups') ? group_side('network/0','network',true,$group_id) : '');
	$a->page['aside'] .= posted_date_widget($a->get_baseurl() . '/network',local_user(),false);	
	$a->page['aside'] .= networks_widget($a->get_baseurl(true) . '/network',(x($_GET, 'nets') ? $_GET['nets'] : ''));
	$a->page['aside'] .= saved_searches($search);
	$a->page['aside'] .= fileas_widget($a->get_baseurl(true) . '/network',(x($_GET, 'file') ? $_GET['file'] : ''));

}

function saved_searches($search) {

	if(! feature_enabled(local_user(),'savedsearch'))
		return '';

	$a = get_app();

	$srchurl = '/network?f='
		. ((x($_GET,'cid'))   ? '&cid='   . $_GET['cid']   : '')
		. ((x($_GET,'star'))  ? '&star='  . $_GET['star']  : '')
		. ((x($_GET,'bmark')) ? '&bmark=' . $_GET['bmark'] : '')
		. ((x($_GET,'conv'))  ? '&conv='  . $_GET['conv']  : '')
		. ((x($_GET,'nets'))  ? '&nets='  . $_GET['nets']  : '')
		. ((x($_GET,'cmin'))  ? '&cmin='  . $_GET['cmin']  : '')
		. ((x($_GET,'cmax'))  ? '&cmax='  . $_GET['cmax']  : '')
		. ((x($_GET,'file'))  ? '&file='  . $_GET['file']  : '');
	;

	$o = '';

	$r = q("select `id`,`term` from `search` WHERE `uid` = %d",
		intval(local_user())
	);

	$saved = array();

	if(count($r)) {
		foreach($r as $rr) {
			$saved[] = array(
				'id'            => $rr['id'],
				'term'			=> $rr['term'],
				'encodedterm' 	=> urlencode($rr['term']),
				'delete'		=> t('Remove term'),
				'selected'		=> ($search==$rr['term']),
			);
		}
	}


	$tpl = get_markup_template("saved_searches_aside.tpl");
	$o = replace_macros($tpl, array(
		'$title'	 => t('Saved Searches'),
		'$add'		 => t('add'),
		'$searchbox' => search($search,'netsearch-box',$srchurl,true),
		'$saved' 	 => $saved,
	));

	return $o;

}

/**
 * Return selected tab from query
 * 
 * urls -> returns
 * 		'/network'					=> $no_active = 'active'
 * 		'/network?f=&order=comment'	=> $comment_active = 'active'
 * 		'/network?f=&order=post'	=> $postord_active = 'active'
 * 		'/network?f=&conv=1',		=> $conv_active = 'active'
 * 		'/network/new',				=> $new_active = 'active'
 * 		'/network?f=&star=1',		=> $starred_active = 'active'
 * 		'/network?f=&bmark=1',		=> $bookmarked_active = 'active'
 * 		'/network?f=&spam=1',		=> $spam_active = 'active'
 * 
 * @return Array ( $no_active, $comment_active, $postord_active, $conv_active, $new_active, $starred_active, $bookmarked_active, $spam_active );
 */
function network_query_get_sel_tab($a) {
	$no_active='';
	$starred_active = '';
	$new_active = '';
	$bookmarked_active = '';
	$all_active = '';
	$search_active = '';
	$conv_active = '';
	$spam_active = '';
	$postord_active = '';

	if(($a->argc > 1 && $a->argv[1] === 'new')
		|| ($a->argc > 2 && $a->argv[2] === 'new')) {
			$new_active = 'active';
	}

	if(x($_GET,'search')) {
		$search_active = 'active';
	}

	if(x($_GET,'star')) {
		$starred_active = 'active';
	}

	if(x($_GET,'bmark')) {
		$bookmarked_active = 'active';
	}

	if(x($_GET,'conv')) {
		$conv_active = 'active';
	}

	if(x($_GET,'spam')) {
		$spam_active = 'active';
	}



	if (($new_active == '')
		&& ($starred_active == '')
		&& ($bookmarked_active == '')
		&& ($conv_active == '')
		&& ($search_active == '')
		&& ($spam_active == '')) {
			$no_active = 'active';
	}

	if ($no_active=='active' && x($_GET,'order')) {
		switch($_GET['order']){
		 case 'post': $postord_active = 'active'; $no_active=''; break;
		 case 'comment' : $all_active = 'active'; $no_active=''; break;
		}
	}

	return array($no_active, $all_active, $postord_active, $conv_active, $new_active, $starred_active, $bookmarked_active, $spam_active);
}

/**
 * Return selected network from query
 */
function network_query_get_sel_net() {
	$network = false;

	if(x($_GET,'nets')) {
		$network = $_GET['nets'];
	}

	return $network;
}

function network_query_get_sel_group($a) {
	$group = false;

	if($a->argc >= 2 && is_numeric($a->argv[1])) {
		$group = $a->argv[1];
	}

	return $group;
}


function network_content(&$a, $update = 0) {

	require_once('include/conversation.php');

	if(! local_user()) {
		$_SESSION['return_url'] = $a->query_string;
    	return login(false);
	}

	$arr = array('query' => $a->query_string);

	call_hooks('network_content_init', $arr);


	$datequery = $datequery2 = '';

	$group = 0;

	$nouveau = false;

	if($a->argc > 1) {
		for($x = 1; $x < $a->argc; $x ++) {
			if(is_a_date_arg($a->argv[$x])) {
				if($datequery)
					$datequery2 = escape_tags($a->argv[$x]);
				else {
					$datequery = escape_tags($a->argv[$x]);
					$_GET['order'] = 'post';
				}
			}
			elseif($a->argv[$x] === 'new') {
				$nouveau = true;
			}
			elseif(intval($a->argv[$x])) {
				$group = intval($a->argv[$x]);
				$def_acl = array('allow_gid' => '<' . $group . '>');
			}
		}
	}

	$o = '';

	// item filter tabs
	// TODO: fix this logic, reduce duplication
	//$a->page['content'] .= '<div class="tabs-wrapper">';

	list($no_active, $all_active, $postord_active, $conv_active, $new_active, $starred_active, $bookmarked_active, $spam_active) = network_query_get_sel_tab($a);
	// if no tabs are selected, defaults to comments
	if ($no_active=='active') $all_active='active';
	//echo "<pre>"; var_dump($no_active, $all_active, $postord_active, $conv_active, $new_active, $starred_active, $bookmarked_active, $spam_active); killme();

	$cmd = (($datequery) ? '' : $a->cmd);
	$len_naked_cmd = strlen(str_replace('/new','',$cmd));

	// tabs
	$tabs = array(
		array(
			'label' => t('Commented Order'),
			'url'=>$a->get_baseurl(true) . '/' . str_replace('/new', '', $cmd) . '?f=&order=comment' . ((x($_GET,'cid')) ? '&cid=' . $_GET['cid'] : ''), 
			'sel'=>$all_active,
			'title'=> t('Sort by Comment Date'),
		),
		array(
			'label' => t('Posted Order'),
			'url'=>$a->get_baseurl(true) . '/' . str_replace('/new', '', $cmd) . '?f=&order=post' . ((x($_GET,'cid')) ? '&cid=' . $_GET['cid'] : ''), 
			'sel'=>$postord_active,
			'title' => t('Sort by Post Date'),
		),

/*		array(
			'label' => t('Personal'),
			'url' => $a->get_baseurl(true) . '/' . str_replace('/new', '', $cmd) . ((x($_GET,'cid')) ? '/?f=&cid=' . $_GET['cid'] : '') . '&conv=1',
			'sel' => $conv_active,
			'title' => t('Posts that mention or involve you'),
		),*/
/*		array(
			'label' => t('New'),
			'url' => $a->get_baseurl(true) . '/' . str_replace('/new', '', $cmd) . ($len_naked_cmd ? '/' : '') . 'new' . ((x($_GET,'cid')) ? '/?f=&cid=' . $_GET['cid'] : ''),
			'sel' => $new_active,
			'title' => t('Activity Stream - by date'),
		),*/
/*		array(
			'label' => t('Starred'),
			'url'=>$a->get_baseurl(true) . '/' . str_replace('/new', '', $cmd) . ((x($_GET,'cid')) ? '/?f=&cid=' . $_GET['cid'] : '') . '&star=1',
			'sel'=>$starred_active,
			'title' => t('Favourite Posts'),
		),*/
/*		array(
			'label' => t('Shared Links'),
			'url'=>$a->get_baseurl(true) . '/' . str_replace('/new', '', $cmd) . ((x($_GET,'cid')) ? '/?f=&cid=' . $_GET['cid'] : '') . '&bmark=1',
			'sel'=>$bookmarked_active,
			'title'=> t('Interesting Links'),
		),	*/
//		array(
//			'label' => t('Spam'),
//			'url'=>$a->get_baseurl(true) . '/network?f=&spam=1'
//			'sel'=> $spam_active,
//			'title' => t('Posts flagged as SPAM'),
//		),

	);

	if(feature_enabled(local_user(),'personal_tab')) {
		$tabs[] = array(
			'label' => t('Personal'),
			'url' => $a->get_baseurl(true) . '/' . str_replace('/new', '', $cmd) . ((x($_GET,'cid')) ? '/?f=&cid=' . $_GET['cid'] : '') . '&conv=1',
			'sel' => $conv_active,
			'title' => t('Posts that mention or involve you'),
		);
	}

	if(feature_enabled(local_user(),'new_tab')) {
		$tabs[] = array(
			'label' => t('New'),
			'url' => $a->get_baseurl(true) . '/' . str_replace('/new', '', $cmd) . ($len_naked_cmd ? '/' : '') . 'new' . ((x($_GET,'cid')) ? '/?f=&cid=' . $_GET['cid'] : ''),
			'sel' => $new_active,
			'title' => t('Activity Stream - by date'),
		);
	}

	if(feature_enabled(local_user(),'link_tab')) {
		$tabs[] = array(
			'label' => t('Shared Links'),
			'url'=>$a->get_baseurl(true) . '/' . str_replace('/new', '', $cmd) . ((x($_GET,'cid')) ? '/?f=&cid=' . $_GET['cid'] : '') . '&bmark=1',
			'sel'=>$bookmarked_active,
			'title'=> t('Interesting Links'),
		);
	}

	if(feature_enabled(local_user(),'star_posts')) {
		$tabs[] = array(
			'label' => t('Starred'),
			'url'=>$a->get_baseurl(true) . '/' . str_replace('/new', '', $cmd) . ((x($_GET,'cid')) ? '/?f=&cid=' . $_GET['cid'] : '') . '&star=1',
			'sel'=>$starred_active,
			'title' => t('Favourite Posts'),
		);
	}

	// Not yet implemented

/*	if(feature_enabled(local_user(),'spam_filter'))  {
		$tabs[] = array(
			'label' => t('Spam'),
			'url'=>$a->get_baseurl(true) . '/network?f=&spam=1',
			'sel'=> $spam_active,
			'title' => t('Posts flagged as SPAM'),
		);
	}*/

	// save selected tab, but only if not in search or file mode
	if(!x($_GET,'search') && !x($_GET,'file')) {
		set_pconfig( local_user(), 'network.view','tab.selected',array($all_active, $postord_active, $conv_active, $new_active, $starred_active, $bookmarked_active, $spam_active) );
	}

	$arr = array('tabs' => $tabs);
	call_hooks('network_tabs', $arr);

	$o .= replace_macros(get_markup_template('common_tabs.tpl'), array('$tabs'=> $arr['tabs']));

	// --- end item filter tabs

	$contact_id = $a->cid;

	require_once('include/acl_selectors.php');

	$cid = ((x($_GET,'cid')) ? intval($_GET['cid']) : 0);
	$star = ((x($_GET,'star')) ? intval($_GET['star']) : 0);
	$bmark = ((x($_GET,'bmark')) ? intval($_GET['bmark']) : 0);
	$order = ((x($_GET,'order')) ? notags($_GET['order']) : 'comment');
	$liked = ((x($_GET,'liked')) ? intval($_GET['liked']) : 0);
	$conv = ((x($_GET,'conv')) ? intval($_GET['conv']) : 0);
	$spam = ((x($_GET,'spam')) ? intval($_GET['spam']) : 0);
	$nets = ((x($_GET,'nets')) ? $_GET['nets'] : '');
	$cmin = ((x($_GET,'cmin')) ? intval($_GET['cmin']) : 0);
	$cmax = ((x($_GET,'cmax')) ? intval($_GET['cmax']) : 99);
	$file = ((x($_GET,'file')) ? $_GET['file'] : '');



	if(x($_GET,'search') || x($_GET,'file'))
		$nouveau = true;
	if($cid)
		$def_acl = array('allow_cid' => '<' . intval($cid) . '>');

	if($nets) {
		$r = q("select id from contact where uid = %d and network = '%s' and self = 0",
			intval(local_user()),
			dbesc($nets)
		);

		$str = '';
		if(count($r))
			foreach($r as $rr)
				$str .= '<' . $rr['id'] . '>';
		if(strlen($str))
			$def_acl = array('allow_cid' => $str);
	}
	set_pconfig(local_user(), 'network.view', 'net.selected', ($nets ? $nets : 'all'));

	if(! $update) {
		if($group) {
			if(($t = group_public_members($group)) && (! get_pconfig(local_user(),'system','nowarn_insecure'))) {
				notice( sprintf( tt('Warning: This group contains %s member from an insecure network.',
									'Warning: This group contains %s members from an insecure network.',
									$t), $t ) . EOL);
				notice( t('Private messages to this group are at risk of public disclosure.') . EOL);
			}
		}

		nav_set_selected('network');

		$celeb = ((($a->user['page-flags'] == PAGE_SOAPBOX) || ($a->user['page-flags'] == PAGE_COMMUNITY)) ? true : false);

		$x = array(
			'is_owner' => true,
			'allow_location' => $a->user['allow_location'],
			'default_location' => $a->user['default-location'],
			'nickname' => $a->user['nickname'],
			'lockstate' => ((($group) || ($cid) || ($nets) || (is_array($a->user) && ((strlen($a->user['allow_cid'])) || (strlen($a->user['allow_gid'])) || (strlen($a->user['deny_cid'])) || (strlen($a->user['deny_gid']))))) ? 'lock' : 'unlock'),
			'default_perms' => get_acl_permissions($a->user),
			'acl' => populate_acl((($group || $cid || $nets) ? $def_acl : $a->user), $celeb),
			'bang' => (($group || $cid || $nets) ? '!' : ''),
			'visitor' => 'block',
			'profile_uid' => local_user(),
			'acl_data' => construct_acl_data($a, $a->user), // For non-Javascript ACL selector
		);

		$o .= status_editor($a,$x);

	}

	// We don't have to deal with ACLs on this page. You're looking at everything
	// that belongs to you, hence you can see all of it. We will filter by group if
	// desired. 

	$sql_options  = (($star) ? " and starred = 1 " : '');
	$sql_options .= (($bmark) ? " and bookmark = 1 " : '');

	//$sql_nets = (($nets) ? sprintf(" and `contact`.`network` = '%s' ", dbesc($nets)) : '');
	$sql_nets = (($nets) ? sprintf(" and `item`.`network` = '%s' ", dbesc($nets)) : '');

	$sql_extra = " AND `item`.`parent` IN ( SELECT `parent` FROM `item` WHERE `id` = `parent` $sql_options ) ";

	if($group) {
		$r = q("SELECT `name`, `id` FROM `group` WHERE `id` = %d AND `uid` = %d LIMIT 1",
			intval($group),
			intval($_SESSION['uid'])
		);
		if(! count($r)) {
			if($update)
				killme();
			notice( t('No such group') . EOL );
			goaway($a->get_baseurl(true) . '/network/0');
			// NOTREACHED
		}

		$contacts = expand_groups(array($group));
		if((is_array($contacts)) && count($contacts)) {
			$contact_str = implode(',',$contacts);
		}
		else {
				$contact_str = ' 0 ';
				info( t('Group is empty'));
		}

		$sql_table = "`item` INNER JOIN (SELECT DISTINCT(`parent`) FROM `item` WHERE 1 $sql_options AND (`contact-id` IN ($contact_str) OR `allow_gid` like '".protect_sprintf('%<'.intval($group).'>%')."') and deleted = 0 ORDER BY `created` DESC) AS `temp1` ON item.parent = `temp1`.parent ";
		$sql_extra = "";
		//$sql_extra = " AND `item`.`parent` IN ( SELECT DISTINCT(`parent`) FROM `item` WHERE 1 $sql_options AND ( `contact-id` IN ( $contact_str ) OR `allow_gid` like '" . protect_sprintf('%<' . intval($group) . '>%') . "' ) and deleted = 0 ) ";
		$o = '<h2>' . t('Group: ') . $r[0]['name'] . '</h2>' . $o;
	}
	elseif($cid) {

		$r = q("SELECT `id`,`name`,`network`,`writable`,`nurl` FROM `contact` WHERE `id` = %d 
				AND `blocked` = 0 AND `pending` = 0 LIMIT 1",
			intval($cid)
		);
		if(count($r)) {
			$sql_table = "`item` INNER JOIN (SELECT DISTINCT(`parent`) FROM `item` WHERE 1 $sql_options AND `contact-id` = ".intval($cid)." and deleted = 0 ORDER BY `item`.`received` DESC) AS `temp1` ON item.parent = `temp1`.parent ";
			$sql_extra = "";
			//$sql_extra = " AND `item`.`parent` IN ( SELECT DISTINCT(`parent`) FROM `item` WHERE 1 $sql_options AND `contact-id` = " . intval($cid) . " and deleted = 0 ) ";
			$o = '<h2>' . t('Contact: ') . $r[0]['name'] . '</h2>' . $o;
			if($r[0]['network'] === NETWORK_OSTATUS && $r[0]['writable'] && (! get_pconfig(local_user(),'system','nowarn_insecure'))) {
				notice( t('Private messages to this person are at risk of public disclosure.') . EOL);
			}

		}
		else {
			notice( t('Invalid contact.') . EOL);
			goaway($a->get_baseurl(true) . '/network');
			// NOTREACHED
		}
	}

	if((! $group) && (! $cid) && (! $update) && (! get_config('theme','hide_eventlist'))) {
		$o .= get_birthdays();
		$o .= get_events();
	}

	$sql_extra3 = '';

	if($datequery) {
		$sql_extra3 .= protect_sprintf(sprintf(" AND item.created <= '%s' ", dbesc(datetime_convert(date_default_timezone_get(),'',$datequery))));
	}
	if($datequery2) {
		$sql_extra3 .= protect_sprintf(sprintf(" AND item.created >= '%s' ", dbesc(datetime_convert(date_default_timezone_get(),'',$datequery2))));
	}

	$sql_extra2 = (($nouveau) ? '' : " AND `item`.`parent` = `item`.`id` ");
	$sql_extra3 = (($nouveau) ? '' : $sql_extra3);
	//$sql_order = "`item`.`received`";
	$sql_order = "";
	$order_mode = "received";

	if ($sql_table == "")
		$sql_table = "`item`";

	if(x($_GET,'search')) {
		$search = escape_tags($_GET['search']);

		if(strpos($search,'#') === 0) {
                	$tag = true;
			$search = substr($search,1);
		}

		if (get_config('system','only_tag_search'))
			$tag = true;

		if($tag) {
			//$sql_extra = sprintf(" AND `term`.`term` = '%s' AND `term`.`otype` = %d AND `term`.`type` = %d ",
			//		dbesc(protect_sprintf($search)), intval(TERM_OBJ_POST), intval(TERM_HASHTAG));
			//$sql_table = "`term` LEFT JOIN `item` ON `item`.`id` = `term`.`oid` AND `item`.`uid` = `term`.`uid` ";

			//$sql_order = "`term`.`tid`";

			$sql_extra = "";

			$sql_table = sprintf("`item` INNER JOIN (SELECT `oid` FROM `term` WHERE `term` = '%s' AND `otype` = %d AND `type` = %d AND `uid` = %d ORDER BY `tid` DESC) AS `term` ON `item`.`id` = `term`.`oid` ",
					dbesc(protect_sprintf($search)), intval(TERM_OBJ_POST), intval(TERM_HASHTAG), intval(local_user()));

			$sql_order = "`item`.`received`";
			$order_mode = "received";
		} else {
			if (get_config('system','use_fulltext_engine'))
				$sql_extra = sprintf(" AND MATCH (`item`.`body`, `item`.`title`) AGAINST ('%s' in boolean mode) ", dbesc(protect_sprintf($search)));
			else
				$sql_extra = sprintf(" AND `item`.`body` REGEXP '%s' ", dbesc(protect_sprintf(preg_quote($search))));

			$sql_order = "`item`.`received`";
			$order_mode = "received";
		}
	}
	if(strlen($file)) {
		$sql_extra .= file_tag_file_query('item',unxmlify($file));
	}

	if($conv) {
		$myurl = $a->get_baseurl() . '/profile/'. $a->user['nickname'];
		$myurl = substr($myurl,strpos($myurl,'://')+3);
		$myurl = str_replace('www.','',$myurl);
		$diasp_url = str_replace('/profile/','/u/',$myurl);

		$sql_extra .= sprintf(" AND `item`.`parent` IN (SELECT distinct(`parent`) from item where `author-link` IN ('https://%s', 'http://%s') OR `mention`)",
			dbesc(protect_sprintf($myurl)),
			dbesc(protect_sprintf($myurl))
		);
	}

	if($update) {

		// only setup pagination on initial page view
		$pager_sql = '';

	}
	else {
		if( (! get_config('alt_pager', 'global')) && (! get_pconfig(local_user(),'system','alt_pager')) ) {
		        $r = q("SELECT COUNT(*) AS `total`
			        FROM $sql_table LEFT JOIN `contact` ON `contact`.`id` = `item`.`contact-id`
			        WHERE `item`.`uid` = %d AND `item`.`visible` = 1 AND `item`.`deleted` = 0
			        AND `contact`.`blocked` = 0 AND `contact`.`pending` = 0
			        $sql_extra2 $sql_extra3
			        $sql_extra $sql_nets ",
			        intval($_SESSION['uid'])
		        );

		        if(count($r)) {
			        $a->set_pager_total($r[0]['total']);
		        }
		}

		//  check if we serve a mobile device and get the user settings 
		//  accordingly
		if ($a->is_mobile) { 
		    $itemspage_network = get_pconfig(local_user(),'system','itemspage_mobile_network');
		    $itemspage_network = ((intval($itemspage_network)) ? $itemspage_network : 20);
		} else { 
		    $itemspage_network = get_pconfig(local_user(),'system','itemspage_network');
		    $itemspage_network = ((intval($itemspage_network)) ? $itemspage_network : 40);
		}
		//  now that we have the user settings, see if the theme forces 
		//  a maximum item number which is lower then the user choice
		if(($a->force_max_items > 0) && ($a->force_max_items < $itemspage_network))
			$itemspage_network = $a->force_max_items;

		$a->set_pager_itemspage($itemspage_network);
		$pager_sql = sprintf(" LIMIT %d, %d ",intval($a->pager['start']), intval($a->pager['itemspage']));
	}

	$simple_update = (($update) ? " and `item`.`unseen` = 1 " : '');

	if($nouveau) {
		// "New Item View" - show all items unthreaded in reverse created date order

		$items = q("SELECT `item`.*, `item`.`id` AS `item_id`, `item`.`network` AS `item_network`,
			`contact`.`name`, `contact`.`photo`, `contact`.`url`, `contact`.`rel`, `contact`.`writable`,
			`contact`.`network`, `contact`.`thumb`, `contact`.`dfrn-id`, `contact`.`self`,
			`contact`.`id` AS `cid`, `contact`.`uid` AS `contact-uid`
			FROM $sql_table LEFT JOIN `contact` ON `contact`.`id` = `item`.`contact-id`
			WHERE `item`.`uid` = %d AND `item`.`visible` = 1 
			AND `item`.`deleted` = 0 and `item`.`moderated` = 0
			$simple_update
			AND `contact`.`blocked` = 0 AND `contact`.`pending` = 0
			$sql_extra $sql_nets
			ORDER BY `item`.`received` DESC $pager_sql ",
			intval($_SESSION['uid'])
		);

		$update_unseen = ' WHERE uid = ' . intval($_SESSION['uid']) . " AND unseen = 1 $sql_extra $sql_nets";
	}
	else {

		// Normal conversation view


		if($order === 'post') {
			$ordering = "`created`";
			if ($sql_order == "")
				$order_mode = "created";
		} else {
			$ordering = "`commented`";
			if ($sql_order == "")
				$order_mode = "commented";
		}

		if ($sql_order == "")
			$sql_order = "`item`.$ordering";

		if (($_GET["offset"] != ""))
			$sql_extra3 .= sprintf(" AND $sql_order <= '%s'", dbesc($_GET["offset"]));

		// Fetch a page full of parent items for this page

		if($update) {
			$r = q("SELECT `item`.`parent` AS `item_id`, `item`.`network` AS `item_network`, `contact`.`uid` AS `contact_uid`
				FROM $sql_table LEFT JOIN `contact` ON `contact`.`id` = `item`.`contact-id`
				WHERE `item`.`uid` = %d AND `item`.`visible` = 1 AND
				(`item`.`deleted` = 0 OR item.verb = '" . ACTIVITY_LIKE ."' OR item.verb = '" . ACTIVITY_DISLIKE . "')
				and `item`.`moderated` = 0 and `item`.`unseen` = 1
				AND `contact`.`blocked` = 0 AND `contact`.`pending` = 0
				$sql_extra3 $sql_extra $sql_nets ",
				intval(local_user())
			);
		}
		else {
			$r = q("SELECT `item`.`id` AS `item_id`, `item`.`network` AS `item_network`, `contact`.`uid` AS `contact_uid`
				FROM $sql_table LEFT JOIN `contact` ON `contact`.`id` = `item`.`contact-id`
				WHERE `item`.`uid` = %d AND `item`.`visible` = 1 AND `item`.`deleted` = 0
				AND `item`.`moderated` = 0 AND `contact`.`blocked` = 0 AND `contact`.`pending` = 0
				AND `item`.`parent` = `item`.`id`
				$sql_extra3 $sql_extra $sql_nets
				ORDER BY $sql_order DESC $pager_sql ",
				intval(local_user())
			);
		}

		// Then fetch all the children of the parents that are on this page

		$parents_arr = array();
		$parents_str = '';
		$date_offset = "";

		if(count($r)) {
			foreach($r as $rr)
				if(! in_array($rr['item_id'],$parents_arr))
					$parents_arr[] = $rr['item_id'];

			//$parents_str = implode(', ', $parents_arr);

			// splitted into separate queries to avoid the problem with very long threads
			// so always the last X comments are loaded
			// This problem can occur expecially with imported facebook posts
			$max_comments = get_config("system", "max_comments");
			if ($max_comments == 0)
				$max_comments = 1000;

			$items = array();

			foreach ($parents_arr AS $parents_str) {

				$thread_items = q("SELECT `item`.*, `item`.`id` AS `item_id`, `item`.`network` AS `item_network`,
					`contact`.`name`, `contact`.`photo`, `contact`.`url`, `contact`.`alias`, `contact`.`rel`, `contact`.`writable`,
					`contact`.`network`, `contact`.`thumb`, `contact`.`dfrn-id`, `contact`.`self`,
					`contact`.`id` AS `cid`, `contact`.`uid` AS `contact-uid`
					FROM $sql_table LEFT JOIN `contact` ON `contact`.`id` = `item`.`contact-id`
					WHERE `item`.`uid` = %d AND `item`.`visible` = 1 AND `item`.`deleted` = 0
					AND `item`.`moderated` = 0
					AND `contact`.`blocked` = 0 AND `contact`.`pending` = 0
					AND `item`.`parent` IN ( %s )
					$sql_extra ORDER BY `item`.`commented` DESC LIMIT %d",
					intval(local_user()),
					dbesc($parents_str),
					intval($max_comments + 1)
				);
				$items = array_merge($items, $thread_items);
			}
			$items = conv_sort($items,$ordering);

		} else {
			$items = array();
		}

		if ($_GET["offset"] == "")
			$date_offset = $items[0][$order_mode];
		else
			$date_offset = $_GET["offset"];

		$a->page_offset = $date_offset;

		if($parents_str)
			$update_unseen = ' WHERE uid = ' . intval(local_user()) . ' AND unseen = 1 AND parent IN ( ' . dbesc($parents_str) . ' )';
	}

	// We aren't going to try and figure out at the item, group, and page
	// level which items you've seen and which you haven't. If you're looking
	// at the top level network page just mark everything seen. 


// The $update_unseen is a bit unreliable if you have stuff coming into your stream from a new contact - 
// and other feeds that bring in stuff from the past. One can't find it all. 
// I'm reviving this block to mark everything seen on page 1 of the network as a temporary measure.
// The correct solution is to implement a network notifications box just like the system notifications popup
// with the ability in the popup to "mark all seen".
// Several people are complaining because there are unseen messages they can't find and as time goes
// on they just get buried deeper. It has happened to me a couple of times also.

	if((! $group) && (! $cid) && (! $star)) {
		$r = q("UPDATE `item` SET `unseen` = 0
			WHERE `unseen` = 1 AND `uid` = %d",
			intval(local_user())
		);
	}
	else {
		if($update_unseen)
			$r = q("UPDATE `item` SET `unseen` = 0 $update_unseen");
	}

	// Set this so that the conversation function can find out contact info for our wall-wall items
	$a->page_contact = $a->contact;

	$mode = (($nouveau) ? 'network-new' : 'network');

	$o .= conversation($a,$items,$mode,$update);

	if(!$update) {
		if( get_config('alt_pager', 'global') || get_pconfig(local_user(),'system','alt_pager') ) {
		        $o .= alt_pager($a,count($items));
		}
		else {
		        $o .= paginate($a);
		}
	}

	return $o;
}

