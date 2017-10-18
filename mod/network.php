<?php

use Friendica\App;
use Friendica\Core\System;
use Friendica\Core\Config;
use Friendica\Core\PConfig;

require_once 'include/conversation.php';
require_once 'include/group.php';
require_once 'include/contact_widgets.php';
require_once 'include/items.php';
require_once 'include/ForumManager.php';
require_once 'include/acl_selectors.php';

function network_init(App $a) {
	if (!local_user()) {
		notice(t('Permission denied.') . EOL);
		return;
	}

	$search = (x($_GET, 'search') ? escape_tags($_GET['search']) : '');

	if (($search != '') && !empty($_GET['submit'])) {
		goaway('search?search='.urlencode($search));
	}

	if (x($_GET, 'save')) {
		$exists = dba::exists('search', array('uid' => local_user(), 'term' => $search));
		if (!$exists) {
			dba::insert('search', array('uid' => local_user(), 'term' => $search));
		}
	}
	if (x($_GET, 'remove')) {
		dba::delete('search', array('uid' => local_user(), 'term' => $search));
	}

	$is_a_date_query = false;
	if (x($_GET, 'cid') && intval($_GET['cid']) != 0) {
		$cid = $_GET['cid'];
		$_GET['nets'] = 'all';

	}

	if ($a->argc > 1) {
		for ($x = 1; $x < $a->argc; $x ++) {
			if (is_a_date_arg($a->argv[$x])) {
				$is_a_date_query = true;
				break;
			}
		}
	}

	// convert query string to array. remove friendica args
	$query_array = array();
	$query_string = str_replace($a->cmd."?", "", $a->query_string);
	parse_str($query_string, $query_array);
	array_shift($query_array);

	// fetch last used network view and redirect if needed
	if (!$is_a_date_query) {
		$sel_tabs = network_query_get_sel_tab($a);
		$sel_nets = network_query_get_sel_net();
		$sel_groups = network_query_get_sel_group($a);
		$last_sel_tabs = PConfig::get(local_user(), 'network.view','tab.selected');
		$last_sel_nets = PConfig::get(local_user(), 'network.view', 'net.selected');
		$last_sel_groups = PConfig::get(local_user(), 'network.view', 'group.selected');

		$remember_tab = ($sel_tabs[0] === 'active' && is_array($last_sel_tabs) && $last_sel_tabs[0] !== 'active');
		$remember_net = ($sel_nets === false && $last_sel_nets && $last_sel_nets !== 'all');
		$remember_group = ($sel_groups === false && $last_sel_groups && $last_sel_groups != 0);

		$net_baseurl = '/network';
		$net_args = array();

		if ($remember_group) {
			$net_baseurl .= '/' . $last_sel_groups; // Note that the group number must come before the "/new" tab selection
		} elseif ($sel_groups !== false) {
			$net_baseurl .= '/' . $sel_groups;
		}

		if ($remember_tab) {
			// redirect if current selected tab is '/network' and
			// last selected tab is _not_ '/network?f=&order=comment'.
			// and this isn't a date query

			$tab_baseurls = array(
				'',		//all
				'',		//postord
				'',		//conv
				'/new',		//new
				'',		//starred
				'',		//bookmarked
				'',		//spam
			);
			$tab_args = array(
				'f=&order=comment',	//all
				'f=&order=post',	//postord
				'f=&conv=1',		//conv
				'',			//new
				'f=&star=1',		//starred
				'f=&bmark=1',		//bookmarked
				'f=&spam=1',		//spam
			);

			$k = array_search('active', $last_sel_tabs);

			if ($k != 3) {
				$net_baseurl .= $tab_baseurls[$k];

				// parse out tab queries
				$dest_qa = array();
				$dest_qs = $tab_args[$k];
				parse_str($dest_qs, $dest_qa);
				$net_args = array_merge($net_args, $dest_qa);
			} else {
				$remember_tab = false;
			}
		} elseif ($sel_tabs[4] === 'active') {
			// The '/new' tab is selected
			$remember_group = false;
		}

		if ($remember_net) {
			$net_args['nets'] = $last_sel_nets;
		} elseif ($sel_nets!==false) {
			$net_args['nets'] = $sel_nets;
		}

		if ($remember_tab || $remember_net || $remember_group) {
			$net_args = array_merge($query_array, $net_args);
			$net_queries = build_querystring($net_args);

			$redir_url = ($net_queries ? $net_baseurl."?".$net_queries : $net_baseurl);

			goaway(System::baseUrl() . $redir_url);
		}
	}

	// If nets is set to all, unset it
	if (x($_GET, 'nets') && $_GET['nets'] === 'all') {
		unset($_GET['nets']);
	}

	$group_id = (($a->argc > 1 && is_numeric($a->argv[1])) ? intval($a->argv[1]) : 0);

	PConfig::set(local_user(), 'network.view', 'group.selected', $group_id);

	if (!x($a->page, 'aside')) {
		$a->page['aside'] = '';
	}

	$a->page['aside'] .= (feature_enabled(local_user(),'groups') ? group_side('network/0','network','standard',$group_id) : '');
	$a->page['aside'] .= (feature_enabled(local_user(),'forumlist_widget') ? ForumManager::widget(local_user(),$cid) : '');
	$a->page['aside'] .= posted_date_widget('network',local_user(),false);
	$a->page['aside'] .= networks_widget('network',(x($_GET, 'nets') ? $_GET['nets'] : ''));
	$a->page['aside'] .= saved_searches($search);
	$a->page['aside'] .= fileas_widget('network',(x($_GET, 'file') ? $_GET['file'] : ''));
}

function saved_searches($search) {

	if (!feature_enabled(local_user(),'savedsearch')) {
		return '';
	}

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

	$terms = dba::select('search', array('id', 'term'), array('uid' => local_user()));
	$saved = array();

	while ($rr = dba::fetch($terms)) {
		$saved[] = array(
			'id'          => $rr['id'],
			'term'        => $rr['term'],
			'encodedterm' => urlencode($rr['term']),
			'delete'      => t('Remove term'),
			'selected'    => ($search==$rr['term']),
		);
	}

	$tpl = get_markup_template("saved_searches_aside.tpl");
	$o = replace_macros($tpl, array(
		'$title'     => t('Saved Searches'),
		'$add'       => t('add'),
		'$searchbox' => search($search,'netsearch-box',$srchurl,true),
		'$saved'     => $saved,
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
 * @return Array ($no_active, $comment_active, $postord_active, $conv_active, $new_active, $starred_active, $bookmarked_active, $spam_active);
 */
function network_query_get_sel_tab(App $a) {
	$no_active='';
	$starred_active = '';
	$new_active = '';
	$bookmarked_active = '';
	$all_active = '';
	$conv_active = '';
	$spam_active = '';
	$postord_active = '';

	if (($a->argc > 1 && $a->argv[1] === 'new')
		|| ($a->argc > 2 && $a->argv[2] === 'new')) {
			$new_active = 'active';
	}

	if (x($_GET,'star')) {
		$starred_active = 'active';
	}

	if (x($_GET,'bmark')) {
		$bookmarked_active = 'active';
	}

	if (x($_GET,'conv')) {
		$conv_active = 'active';
	}

	if (x($_GET,'spam')) {
		$spam_active = 'active';
	}



	if (($new_active == '')
		&& ($starred_active == '')
		&& ($bookmarked_active == '')
		&& ($conv_active == '')
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
 * @brief Return selected network from query
 * @return string Name of the selected network
 */
function network_query_get_sel_net() {
	$network = false;

	if (x($_GET,'nets')) {
		$network = $_GET['nets'];
	}

	return $network;
}

function network_query_get_sel_group(App $a) {
	$group = false;

	if ($a->argc >= 2 && is_numeric($a->argv[1])) {
		$group = $a->argv[1];
	}

	return $group;
}

/**
 * @brief Sets the pager data and returns SQL
 *
 * @param App $a The global App
 * @param integer $update Used for the automatic reloading
 * @return string SQL with the appropriate LIMIT clause
 */
function networkPager($a, $update) {
	if ($update) {
		// only setup pagination on initial page view
		return ' LIMIT 100';

	}

	//  check if we serve a mobile device and get the user settings
	//  accordingly
	if ($a->is_mobile) {
		$itemspage_network = PConfig::get(local_user(),'system','itemspage_mobile_network');
		$itemspage_network = ((intval($itemspage_network)) ? $itemspage_network : 20);
	} else {
		$itemspage_network = PConfig::get(local_user(),'system','itemspage_network');
		$itemspage_network = ((intval($itemspage_network)) ? $itemspage_network : 40);
	}

	//  now that we have the user settings, see if the theme forces
	//  a maximum item number which is lower then the user choice
	if (($a->force_max_items > 0) && ($a->force_max_items < $itemspage_network)) {
		$itemspage_network = $a->force_max_items;
	}

	$a->set_pager_itemspage($itemspage_network);

	return sprintf(" LIMIT %d, %d ",intval($a->pager['start']), intval($a->pager['itemspage']));
}

/**
 * @brief Sets items as seen
 *
 * @param array $condition The array with the SQL condition
 */
function networkSetSeen($condition) {
	if (empty($condition)) {
		return;
	}

	$unseen = dba::exists('item', $condition);

	if ($unseen) {
		$r = dba::update('item', array('unseen' => false), $condition);
	}
}

/**
 * @brief Create the conversation HTML
 *
 * @param App $a The global App
 * @param array $items Items of the conversation
 * @param string $mode Display mode for the conversation
 * @param integer $update Used for the automatic reloading
 * @return string HTML of the conversation
 */
function networkConversation($a, $items, $mode, $update) {
	// Set this so that the conversation function can find out contact info for our wall-wall items
	$a->page_contact = $a->contact;

	$o .= conversation($a, $items, $mode, $update);

	if (!$update) {
		if (PConfig::get(local_user(), 'system', 'infinite_scroll')) {
			$o .= scroll_loader();
		} else {
			$o .= alt_pager($a, count($items));
		}
	}

	return $o;
}

function network_content(App $a, $update = 0) {
	if (!local_user()) {
		$_SESSION['return_url'] = $a->query_string;
		return login(false);
	}

	/// @TODO Is this really necessary? $a is already available to hooks
	$arr = array('query' => $a->query_string);
	call_hooks('network_content_init', $arr);

	$nouveau = false;

	if ($a->argc > 1) {
		for ($x = 1; $x < $a->argc; $x ++) {
			if ($a->argv[$x] === 'new') {
				$nouveau = true;
			}
		}
	}

	if (x($_GET,'file')) {
		$nouveau = true;
	}

	if ($nouveau) {
		$o = networkFlatView($a, $update);
	} else {
		$o = networkThreadedView($a, $update);
	}

	return $o;
}

/**
 * @brief Get the network content in flat view
 *
 * @param App $a The global App
 * @param integer $update Used for the automatic reloading
 * @return string HTML of the network content in flat view
 */
function networkFlatView(App $a, $update = 0) {

	// Rawmode is used for fetching new content at the end of the page
	$rawmode = (isset($_GET["mode"]) AND ($_GET["mode"] == "raw"));

	if (isset($_GET["last_id"])) {
		$last_id = intval($_GET["last_id"]);
	} else {
		$last_id = 0;
	}

	$o = '';

	$file = ((x($_GET,'file')) ? $_GET['file'] : '');

	PConfig::set(local_user(), 'network.view', 'net.selected', 'all');

	if (!$update && !$rawmode) {
		$tabs = network_tabs($a);
		$o .= $tabs;

		nav_set_selected('network');

		$x = array(
			'is_owner' => true,
			'allow_location' => $a->user['allow_location'],
			'default_location' => $a->user['default-location'],
			'nickname' => $a->user['nickname'],
			'lockstate'=> (((is_array($a->user) &&
					((strlen($a->user['allow_cid'])) || (strlen($a->user['allow_gid'])) ||
					(strlen($a->user['deny_cid'])) || (strlen($a->user['deny_gid']))))) ? 'lock' : 'unlock'),
			'default_perms'	=> get_acl_permissions($a->user),
			'acl'	=> populate_acl($a->user, true),
			'bang'	=> '',
			'visitor' => 'block',
			'profile_uid' => local_user(),
			'acl_data' => construct_acl_data($a, $a->user), // For non-Javascript ACL selector
			'content' => '',
		);

		$o .= status_editor($a,$x);

		if (!Config::get('theme','hide_eventlist')) {
			$o .= get_birthdays();
			$o .= get_events();
		}
	}

	if (strlen($file)) {
		$sql_post_table .= sprintf("INNER JOIN (SELECT `oid` FROM `term` WHERE `term` = '%s' AND `otype` = %d AND `type` = %d AND `uid` = %d ORDER BY `tid` DESC) AS `term` ON `item`.`id` = `term`.`oid` ",
				dbesc(protect_sprintf($file)), intval(TERM_OBJ_POST), intval(TERM_FILE), intval(local_user()));
	} else {
		$sql_post_table = " INNER JOIN `thread` ON `thread`.`iid` = `item`.`parent`";
	}

	$pager_sql = networkPager($a, $update);

	// show all items unthreaded in reverse created date order
	$items = q("SELECT %s FROM `item` $sql_post_table %s
		WHERE %s AND `item`.`uid` = %d
		ORDER BY `item`.`id` DESC $pager_sql ",
		item_fieldlists(), item_joins(), item_condition(),
		intval($_SESSION['uid'])
	);

	$condition = array('unseen' => true, 'uid' => local_user());
	networkSetSeen($condition);

	$mode = 'network-new';
	$o .= networkConversation($a, $items, $mode, $update);

	return $o;
}

/**
 * @brief Get the network content in threaded view
 *
 * @param App $a The global App
 * @param integer $update Used for the automatic reloading
 * @return string HTML of the network content in flat view
 */
function networkThreadedView(App $a, $update = 0) {

	// Rawmode is used for fetching new content at the end of the page
	$rawmode = (isset($_GET["mode"]) AND ($_GET["mode"] == "raw"));

	if (isset($_GET["last_received"]) && isset($_GET["last_commented"]) && isset($_GET["last_created"]) && isset($_GET["last_id"])) {
		$last_received = dbm::date($_GET["last_received"]);
		$last_commented = dbm::date($_GET["last_commented"]);
		$last_created = dbm::date($_GET["last_created"]);
		$last_id = intval($_GET["last_id"]);
	} else {
		$last_received = '';
		$last_commented = '';
		$last_created = '';
		$last_id = 0;
	}

	$datequery = $datequery2 = '';

	$group = 0;

	if ($a->argc > 1) {
		for ($x = 1; $x < $a->argc; $x ++) {
			if (is_a_date_arg($a->argv[$x])) {
				if ($datequery) {
					$datequery2 = escape_tags($a->argv[$x]);
				} else {
					$datequery = escape_tags($a->argv[$x]);
					$_GET['order'] = 'post';
				}
			} elseif (intval($a->argv[$x])) {
				$group = intval($a->argv[$x]);
				$def_acl = array('allow_gid' => '<' . $group . '>');
			}
		}
	}

	$o = '';

	$cid = ((x($_GET,'cid')) ? intval($_GET['cid']) : 0);
	$star = ((x($_GET,'star')) ? intval($_GET['star']) : 0);
	$bmark = ((x($_GET,'bmark')) ? intval($_GET['bmark']) : 0);
	$order = ((x($_GET,'order')) ? notags($_GET['order']) : 'comment');
	$conv = ((x($_GET,'conv')) ? intval($_GET['conv']) : 0);
	$nets = ((x($_GET,'nets')) ? $_GET['nets'] : '');

	if ($cid) {
		$def_acl = array('allow_cid' => '<' . intval($cid) . '>');
	}

	if ($nets) {
		$r = dba::select('contact', array('id'), array('uid' => local_user(), 'network' => $nets), array('self' => false));

		$str = '';
		while ($rr = dba::fetch($r)) {
			$str .= '<' . $rr['id'] . '>';
		}
		if (strlen($str)) {
			$def_acl = array('allow_cid' => $str);
		}
	}
	PConfig::set(local_user(), 'network.view', 'net.selected', ($nets ? $nets : 'all'));

	if (!$update && !$rawmode) {
		$tabs = network_tabs($a);
		$o .= $tabs;

		if ($group) {
			if (($t = group_public_members($group)) && !PConfig::get(local_user(),'system','nowarn_insecure')) {
				notice(sprintf(tt("Warning: This group contains %s member from a network that doesn't allow non public messages.",
						"Warning: This group contains %s members from a network that doesn't allow non public messages.",
						$t), $t).EOL);
				notice(t("Messages in this group won't be send to these receivers.").EOL);
			}
		}

		nav_set_selected('network');

		$content = "";

		if ($cid) {
			// If $cid belongs to a communitity forum or a privat goup,.add a mention to the status editor
			$condition = array("`id` = ? AND (`forum` OR `prv`)", $cid);
			$contact = dba::select('contact', array('addr', 'nick'), $condition, array('limit' => 1));
			if (dbm::is_result($contact)) {
				if ($contact["addr"] != '') {
					$content = "@".$contact["addr"];
				} else {
					$content = "@".$contact["nick"]."+".$cid;
				}
			}
		}

		$x = array(
			'is_owner' => true,
			'allow_location' => $a->user['allow_location'],
			'default_location' => $a->user['default-location'],
			'nickname' => $a->user['nickname'],
			'lockstate'=> ((($group) || ($cid) || ($nets) || (is_array($a->user) &&
					((strlen($a->user['allow_cid'])) || (strlen($a->user['allow_gid'])) ||
					(strlen($a->user['deny_cid'])) || (strlen($a->user['deny_gid']))))) ? 'lock' : 'unlock'),
			'default_perms'	=> get_acl_permissions($a->user),
			'acl'	=> populate_acl((($group || $cid || $nets) ? $def_acl : $a->user), true),
			'bang'	=> (($group || $cid || $nets) ? '!' : ''),
			'visitor' => 'block',
			'profile_uid' => local_user(),
			'acl_data' => construct_acl_data($a, $a->user), // For non-Javascript ACL selector
			'content' => $content,
		);

		$o .= status_editor($a,$x);

	}

	// We don't have to deal with ACLs on this page. You're looking at everything
	// that belongs to you, hence you can see all of it. We will filter by group if
	// desired.

	$sql_post_table = "";
	$sql_options  = (($star) ? " AND `thread`.`starred` " : '');
	$sql_options .= (($bmark) ? " AND `thread`.`bookmark` " : '');
	$sql_extra = $sql_options;
	$sql_extra2 = "";
	$sql_extra3 = "";
	$sql_table = "`thread`";
	$sql_parent = "`iid`";

	if ($update) {
		$sql_table = "`item`";
		$sql_parent = "`parent`";
		$sql_post_table = " INNER JOIN `thread` ON `thread`.`iid` = `item`.`parent`";
	}

	$sql_nets = (($nets) ? sprintf(" and $sql_table.`network` = '%s' ", dbesc($nets)) : '');

	if ($group) {
		$r = dba::select('group', array('name'), array('id' => $group, 'uid' => $_SESSION['uid']), array('limit' => 1));
		if (!dbm::is_result($r)) {
			if ($update)
				killme();
			notice(t('No such group') . EOL);
			goaway('network/0');
			// NOTREACHED
		}

		$contacts = expand_groups(array($group));

		if ((is_array($contacts)) && count($contacts)) {
			$contact_str_self = "";

			$contact_str = implode(',',$contacts);
			$self = dba::select('contact', array('id'), array('uid' => $_SESSION['uid'], 'self' => true), array('limit' => 1));
			if (dbm::is_result($self)) {
				$contact_str_self = $self["id"];
			}

			$sql_post_table .= " INNER JOIN `item` AS `temp1` ON `temp1`.`id` = ".$sql_table.".".$sql_parent;
			$sql_extra3 .= " AND (`thread`.`contact-id` IN ($contact_str) ";
			$sql_extra3 .= " OR (`thread`.`contact-id` = '$contact_str_self' AND `temp1`.`allow_gid` LIKE '".protect_sprintf('%<'.intval($group).'>%')."' AND `temp1`.`private`))";
		} else {
			$sql_extra3 .= " AND false ";
			info(t('Group is empty'));
		}

		$o = replace_macros(get_markup_template("section_title.tpl"),array(
			'$title' => sprintf(t('Group: %s'), $r['name'])
		)) . $o;

	} elseif ($cid) {
		$fields = array('id', 'name', 'network', 'writable', 'nurl',
				'forum', 'prv', 'contact-type', 'addr', 'thumb', 'location');
		$condition = array("`id` = ? AND (NOT `blocked` OR `pending`)", $cid);
		$r = dba::select('contact', $fields, $condition, array('limit' => 1));
		if (dbm::is_result($r)) {
			$sql_extra = " AND ".$sql_table.".`contact-id` = ".intval($cid);

			$entries[0] = array(
				'id' => 'network',
				'name' => htmlentities($r['name']),
				'itemurl' => (($r['addr']) ? ($r['addr']) : ($r['nurl'])),
				'thumb' => proxy_url($r['thumb'], false, PROXY_SIZE_THUMB),
				'details' => $r['location'],
			);

			$entries[0]["account_type"] = account_type($r);

			$o = replace_macros(get_markup_template("viewcontact_template.tpl"),array(
				'contacts' => $entries,
				'id' => 'network',
			)) . $o;

			if ($r['network'] === NETWORK_OSTATUS && $r['writable'] && !PConfig::get(local_user(),'system','nowarn_insecure')) {
				notice(t('Private messages to this person are at risk of public disclosure.') . EOL);
			}

		} else {
			notice(t('Invalid contact.') . EOL);
			goaway('network');
			// NOTREACHED
		}
	}

	if (!$group && !$cid && !$update && !Config::get('theme','hide_eventlist')) {
		$o .= get_birthdays();
		$o .= get_events();
	}

	if ($datequery) {
		$sql_extra3 .= protect_sprintf(sprintf(" AND $sql_table.created <= '%s' ", dbesc(datetime_convert(date_default_timezone_get(),'',$datequery))));
	}
	if ($datequery2) {
		$sql_extra3 .= protect_sprintf(sprintf(" AND $sql_table.created >= '%s' ", dbesc(datetime_convert(date_default_timezone_get(),'',$datequery2))));
	}

	$sql_order = "";
	$order_mode = "received";

	if (strlen($file)) {
		$sql_post_table .= sprintf("INNER JOIN (SELECT `oid` FROM `term` WHERE `term` = '%s' AND `otype` = %d AND `type` = %d AND `uid` = %d ORDER BY `tid` DESC) AS `term` ON `item`.`id` = `term`.`oid` ",
				dbesc(protect_sprintf($file)), intval(TERM_OBJ_POST), intval(TERM_FILE), intval(local_user()));
		$sql_order = "`item`.`id`";
		$order_mode = "id";
	}

	if ($conv) {
		$sql_extra3 .= " AND $sql_table.`mention`";
	}

	// Normal conversation view
	if ($order === 'post') {
		$ordering = "`created`";
		if ($sql_order == "") {
			$order_mode = "created";
		}
	} else {
		$ordering = "`commented`";
		if ($sql_order == "") {
			$order_mode = "commented";
		}
	}

	if ($sql_order == "") {
		$sql_order = "$sql_table.$ordering";
	}

	if (($_GET["offset"] != "")) {
		$sql_extra3 .= sprintf(" AND $sql_order <= '%s'", dbesc($_GET["offset"]));
	}

	$pager_sql = networkPager($a, $update);

	switch ($order_mode) {
		case 'received':
			if ($last_received != '') {
				$sql_extra3 .= sprintf(" AND $sql_table.`received` < '%s'", dbesc($last_received));
				$a->set_pager_page(1);
				$pager_sql = sprintf(" LIMIT %d, %d ",intval($a->pager['start']), intval($a->pager['itemspage']));
			}
			break;
		case 'commented':
			if ($last_commented != '') {
				$sql_extra3 .= sprintf(" AND $sql_table.`commented` < '%s'", dbesc($last_commented));
				$a->set_pager_page(1);
				$pager_sql = sprintf(" LIMIT %d, %d ",intval($a->pager['start']), intval($a->pager['itemspage']));
			}
			break;
		case 'created':
			if ($last_created != '') {
				$sql_extra3 .= sprintf(" AND $sql_table.`created` < '%s'", dbesc($last_created));
				$a->set_pager_page(1);
				$pager_sql = sprintf(" LIMIT %d, %d ",intval($a->pager['start']), intval($a->pager['itemspage']));
			}
			break;
		case 'id':
			if (($last_id > 0) && ($sql_table == "`thread`")) {
				$sql_extra3 .= sprintf(" AND $sql_table.`iid` < '%s'", dbesc($last_id));
				$a->set_pager_page(1);
				$pager_sql = sprintf(" LIMIT %d, %d ",intval($a->pager['start']), intval($a->pager['itemspage']));
			}
			break;
	}

	// Fetch a page full of parent items for this page
	if ($update) {
		if (Config::get("system", "like_no_comment")) {
			$sql_extra4 = " AND `item`.`verb` = '".ACTIVITY_POST."'";
		} else {
			$sql_extra4 = "";
		}

		$r = q("SELECT `item`.`parent` AS `item_id`, `item`.`network` AS `item_network`, `contact`.`uid` AS `contact_uid`
			FROM $sql_table $sql_post_table INNER JOIN `contact` ON `contact`.`id` = `item`.`contact-id`
			AND (NOT `contact`.`blocked` OR `contact`.`pending`)
			WHERE `item`.`uid` = %d AND `item`.`visible` AND NOT `item`.`deleted` $sql_extra4
			AND NOT `item`.`moderated` AND `item`.`unseen`
			$sql_extra3 $sql_extra $sql_nets
			ORDER BY `item_id` DESC LIMIT 100",
			intval(local_user())
		);
	} else {
		$r = q("SELECT `thread`.`iid` AS `item_id`, `thread`.`network` AS `item_network`, `contact`.`uid` AS `contact_uid`
			FROM $sql_table $sql_post_table STRAIGHT_JOIN `contact` ON `contact`.`id` = `thread`.`contact-id`
			AND (NOT `contact`.`blocked` OR `contact`.`pending`)
			WHERE `thread`.`uid` = %d AND `thread`.`visible` AND NOT `thread`.`deleted`
			AND NOT `thread`.`moderated`
			$sql_extra2 $sql_extra3 $sql_extra $sql_nets
			ORDER BY $sql_order DESC $pager_sql",
			intval(local_user())
		);
	}

	// Then fetch all the children of the parents that are on this page

	$parents_arr = array();
	$parents_str = '';
	$date_offset = "";

	if (dbm::is_result($r)) {
		foreach ($r as $rr) {
			if (!in_array($rr['item_id'],$parents_arr)) {
				$parents_arr[] = $rr['item_id'];
			}
		}

		$parents_str = implode(", ", $parents_arr);

		// splitted into separate queries to avoid the problem with very long threads
		// so always the last X comments are loaded
		// This problem can occur expecially with imported facebook posts
		$max_comments = Config::get("system", "max_comments");
		if ($max_comments == 0) {
			$max_comments = 100;
		}

		$items = array();

		foreach ($parents_arr AS $parents) {
			$thread_items = dba::p(item_query()." AND `item`.`uid` = ?
				AND `item`.`parent` = ?
				ORDER BY `item`.`commented` DESC LIMIT ".intval($max_comments + 1),
				local_user(),
				$parents
			);

			if (dbm::is_result($thread_items)) {
				$items = array_merge($items, dba::inArray($thread_items));
			}
		}
		$items = conv_sort($items,$ordering);
	} else {
		$items = array();
	}

	if ($_GET["offset"] == "") {
		$date_offset = $items[0][$order_mode];
	} else {
		$date_offset = $_GET["offset"];
	}

	$a->page_offset = $date_offset;

	// We aren't going to try and figure out at the item, group, and page
	// level which items you've seen and which you haven't. If you're looking
	// at the top level network page just mark everything seen.

	if (!$group && !$cid && !$star) {
		$condition = array('unseen' => true, 'uid' => local_user());
		networkSetSeen($condition);
	} elseif ($parents_str) {
		$condition = array("`uid` = ? AND `unseen` AND `parent` IN (" . dbesc($parents_str) . ")", local_user());
		networkSetSeen($condition);
	}


	$mode = 'network';
	$o .= networkConversation($a, $items, $mode, $update);

	return $o;
}

/**
 * @brief Get the network tabs menu
 *
 * @param App $a The global App
 * @return string Html of the networktab
 */
function network_tabs(App $a) {
	// item filter tabs
	/// @TODO fix this logic, reduce duplication
	/// $a->page['content'] .= '<div class="tabs-wrapper">';

	list($no_active, $all_active, $postord_active, $conv_active, $new_active, $starred_active, $bookmarked_active, $spam_active) = network_query_get_sel_tab($a);
	// if no tabs are selected, defaults to comments
	if ($no_active=='active') $all_active='active';

	$cmd = (($datequery) ? '' : $a->cmd);
	$len_naked_cmd = strlen(str_replace('/new','',$cmd));

	// tabs
	$tabs = array(
		array(
			'label'	=> t('Commented Order'),
			'url'	=> str_replace('/new', '', $cmd) . '?f=&order=comment' . ((x($_GET,'cid')) ? '&cid=' . $_GET['cid'] : ''),
			'sel'	=> $all_active,
			'title'	=> t('Sort by Comment Date'),
			'id'	=> 'commented-order-tab',
			'accesskey' => "e",
		),
		array(
			'label'	=> t('Posted Order'),
			'url'	=> str_replace('/new', '', $cmd) . '?f=&order=post' . ((x($_GET,'cid')) ? '&cid=' . $_GET['cid'] : ''),
			'sel'	=> $postord_active,
			'title'	=> t('Sort by Post Date'),
			'id'	=> 'posted-order-tab',
			'accesskey' => "t",
		),
	);

	if (feature_enabled(local_user(),'personal_tab')) {
		$tabs[] = array(
			'label'	=> t('Personal'),
			'url'	=> str_replace('/new', '', $cmd) . ((x($_GET,'cid')) ? '/?f=&cid=' . $_GET['cid'] : '/?f=') . '&conv=1',
			'sel'	=> $conv_active,
			'title'	=> t('Posts that mention or involve you'),
			'id'	=> 'personal-tab',
			'accesskey' => "r",
		);
	}

	if (feature_enabled(local_user(),'new_tab')) {
		$tabs[] = array(
			'label'	=> t('New'),
			'url'	=> 'network/new' . ((x($_GET,'cid')) ? '/?f=&cid=' . $_GET['cid'] : ''),
			'sel'	=> $new_active,
			'title'	=> t('Activity Stream - by date'),
			'id'	=> 'activitiy-by-date-tab',
			'accesskey' => "w",
		);
	}

	if (feature_enabled(local_user(),'link_tab')) {
		$tabs[] = array(
			'label'	=> t('Shared Links'),
			'url'	=> str_replace('/new', '', $cmd) . ((x($_GET,'cid')) ? '/?f=&cid=' . $_GET['cid'] : '/?f=') . '&bmark=1',
			'sel'	=> $bookmarked_active,
			'title'	=> t('Interesting Links'),
			'id'	=> 'shared-links-tab',
			'accesskey' => "b",
		);
	}

	if (feature_enabled(local_user(),'star_posts')) {
		$tabs[] = array(
			'label'	=> t('Starred'),
			'url'	=> str_replace('/new', '', $cmd) . ((x($_GET,'cid')) ? '/?f=&cid=' . $_GET['cid'] : '/?f=') . '&star=1',
			'sel'	=> $starred_active,
			'title'	=> t('Favourite Posts'),
			'id'	=> 'starred-posts-tab',
			'accesskey' => "m",
		);
	}

	// save selected tab, but only if not in file mode
	if (!x($_GET,'file')) {
		PConfig::set(local_user(), 'network.view','tab.selected',array($all_active, $postord_active, $conv_active, $new_active, $starred_active, $bookmarked_active, $spam_active));
	}

	$arr = array('tabs' => $tabs);
	call_hooks('network_tabs', $arr);

	$tpl = get_markup_template('common_tabs.tpl');

	return replace_macros($tpl, array('$tabs' => $arr['tabs']));

	// --- end item filter tabs
}
