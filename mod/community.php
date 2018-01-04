<?php

use Friendica\App;
use Friendica\Core\Config;
use Friendica\Core\PConfig;
use Friendica\Database\DBM;

function community_init(App $a) {
	if (!local_user()) {
		unset($_SESSION['theme']);
		unset($_SESSION['mobile-theme']);
	}
}

function community_content(App $a, $update = 0) {
	$o = '';

	if (Config::get('system','block_public') && !local_user() && !remote_user()) {
		notice(t('Public access denied.') . EOL);
		return;
	}

	$page_style = Config::get('system','community_page_style');

	if ($a->argc > 1) {
		$content = $a->argv[1];
	} else {
		// When only the global community is allowed, we use this as default
		$content = $page_style == CP_GLOBAL_COMMUNITY ? 'global' : 'local';
	}

	if (!in_array($content, ['local', 'global'])) {
		notice(t('Community option not available.') . EOL);
		return;
	}

	// Check if we are allowed to display the content to visitors
	if (!local_user()) {
		$available = $page_style == CP_USERS_AND_GLOBAL;

		if (!$available) {
			$available = ($page_style == CP_USERS_ON_SERVER) && ($content == 'local');
		}

		if (!$available) {
			$available = ($page_style == CP_GLOBAL_COMMUNITY) && ($content == 'global');
		}

		if (!$available) {
			notice(t('Not available.') . EOL);
			return;
		}
	}

	require_once 'include/bbcode.php';
	require_once 'include/security.php';
	require_once 'include/conversation.php';

	if (!$update) {
		$tabs = [];

		if (local_user() || in_array($page_style, [CP_USERS_AND_GLOBAL, CP_USERS_ON_SERVER])) {
			$tabs[] = array('label'=>t('Community'),
					'url' => 'community/local',
					'sel' => $content == 'local' ? 'active' : '',
					'title' => t('Posts from local users on this server'),
					'id' => 'community-local-tab',
					'accesskey' => 'l');
		}

		if (local_user() || in_array($page_style, [CP_USERS_AND_GLOBAL, CP_GLOBAL_COMMUNITY])) {
			$tabs[] = array('label' => t('Global Timeline'),
					'url' => 'community/global',
					'sel' => $content == 'global' ? 'active' : '',
					'title' => t('Posts from users of the federated network'),
					'id'    => 'community-global-tab',
					'accesskey' => 'g');
		}

		$tab_tpl = get_markup_template('common_tabs.tpl');
		$o .= replace_macros($tab_tpl, array('$tabs' => $tabs));

		nav_set_selected('community');
	}

	if (Config::get('system', 'comment_public')) {
		// check if we serve a mobile device and get the user settings
		// accordingly
		if ($a->is_mobile) {
			$itemspage_network = PConfig::get(local_user(),'system','itemspage_mobile_network', 20);
		} else {
			$itemspage_network = PConfig::get(local_user(),'system','itemspage_network', 40);
		}

		// now that we have the user settings, see if the theme forces
		// a maximum item number which is lower then the user choice
		if (($a->force_max_items > 0) && ($a->force_max_items < $itemspage_network)) {
			$itemspage_network = $a->force_max_items;
		}

		$a->set_pager_itemspage($itemspage_network);
	}

	$r = community_getitems($a->pager['start'], $a->pager['itemspage'], $content);

	if (!DBM::is_result($r)) {
		info(t('No results.') . EOL);
		return $o;
	}

	$maxpostperauthor = Config::get('system','max_author_posts_community_page');

	if (($maxpostperauthor != 0) && ($content == 'local')) {
		$count = 1;
		$previousauthor = "";
		$numposts = 0;
		$s = array();

		do {
			foreach ($r AS $row=>$item) {
				if ($previousauthor == $item["author-link"]) {
					++$numposts;
				} else {
					$numposts = 0;
				}
				$previousauthor = $item["author-link"];

				if (($numposts < $maxpostperauthor) && (sizeof($s) < $a->pager['itemspage'])) {
					$s[] = $item;
				}
			}
			if (sizeof($s) < $a->pager['itemspage']) {
				$r = community_getitems($a->pager['start'] + ($count * $a->pager['itemspage']), $a->pager['itemspage'], $content);
			}
		} while ((sizeof($s) < $a->pager['itemspage']) && (++$count < 50) && (sizeof($r) > 0));
	} else {
		$s = $r;
	}

	$o .= conversation($a, $s, 'community', $update);

	if (!$update) {
		$o .= alt_pager($a, count($r));
	}

	$t = get_markup_template("community.tpl");
	return replace_macros($t, array(
		'$content' => $o,
		'$header' => '',
		'$show_global_community_hint' => ($content == 'global') && Config::get('system', 'show_global_community_hint'),
		'$global_community_hint' => t("This community stream shows all public posts received by this node. They may not reflect the opinions of this node’s users.")
	));
}

function community_getitems($start, $itemspage, $content) {
	if ($content == 'local') {
		$r = dba::p("SELECT ".item_fieldlists()." FROM `thread`
			INNER JOIN `user` ON `user`.`uid` = `thread`.`uid` AND NOT `user`.`hidewall`
			INNER JOIN `item` ON `item`.`id` = `thread`.`iid`
			AND `item`.`allow_cid` = ''  AND `item`.`allow_gid` = ''
			AND `item`.`deny_cid`  = '' AND `item`.`deny_gid`  = ''".
			item_joins()." AND `contact`.`self`
			WHERE `thread`.`visible` AND NOT `thread`.`deleted` AND NOT `thread`.`moderated`
			AND NOT `thread`.`private` AND `thread`.`wall`
			ORDER BY `thread`.`received` DESC LIMIT ".intval($start).", ".intval($itemspage)
		);
		return dba::inArray($r);
	} elseif ($content == 'global') {
		$r = dba::p("SELECT ".item_fieldlists()." FROM `thread`
			INNER JOIN `item` ON `item`.`id` = `thread`.`iid` ".item_joins().
			"WHERE `thread`.`uid` = 0 AND `verb` = ?
			ORDER BY `thread`.`created` DESC LIMIT ".intval($start).", ".intval($itemspage),
			ACTIVITY_POST
		);
		return dba::inArray($r);
	}

	// Should never happen
	return array();
}
