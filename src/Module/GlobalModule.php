<?php

/**
 * @file src/Module/GlobalModule.php
 */

namespace Friendica\Module;

use Friendica\BaseModule;
use Friendica\Core\Config;
use Friendica\Database\DBM;
use dba;

/**
 * Global module
 *
 * Displays global posts on the server
 *
 * @author heluecht@pirati.ca
 */
class GlobalModule extends BaseModule {
	public static function init()
	{
		if (!local_user()) {
			unset($_SESSION['theme']);
			unset($_SESSION['mobile-theme']);
		}
	}

	public static function content($update = 0)
	{
		$a = self::getApp();

		$o = '';

		if (Config::get('system','block_public') && !local_user() && !remote_user()) {
			notice(t('Public access denied.') . EOL);
			return;
		}

		if (!local_user() && !in_array(Config::get('system','community_page_style'), [CP_GLOBAL_COMMUNITY, CP_USERS_AND_GLOBAL])) {
			notice(t('Not available.') . EOL);
			return;
		}

		require_once 'include/bbcode.php';
		require_once 'include/security.php';
		require_once 'include/conversation.php';

		if (!$update) {
			nav_set_selected('global');
		}

		if (x($a->data,'search')) {
			$search = notags(trim($a->data['search']));
		} else {
			$search = (x($_GET,'search') ? notags(trim(rawurldecode($_GET['search']))) : '');
		}

		// Here is the way permissions work in this module...
		// Only public posts can be shown
		// OR your own posts if you are a logged in member

		$r = self::getPublicItems($a->pager['start'], $a->pager['itemspage']);

		if (!DBM::is_result($r)) {
			info(t('No results.') . EOL);
			return $o;
		}

		// we behave the same in message lists as the search module

		$o .= conversation($a, $r, 'community', $update);

		$o .= alt_pager($a, count($r));

		$t = get_markup_template("community.tpl");
		return replace_macros($t, array(
			'$content' => $o,
			'$header' => t("Global Timeline"),
			'$show_global_community_hint' => Config::get('system', 'show_global_community_hint'),
			'$global_community_hint' => t("This community stream shows all public posts received by this node. They may not reflect the opinions of this node’s users.")
		));
	}

	private static function getPublicItems($start, $itemspage)
	{
		$r = dba::p("SELECT ".item_fieldlists()." FROM `thread`
			INNER JOIN `item` ON `item`.`id` = `thread`.`iid` ".item_joins().
			"WHERE `thread`.`uid` = 0 AND `verb` = ?
			ORDER BY `thread`.`created` DESC LIMIT ".intval($start).", ".intval($itemspage),
			ACTIVITY_POST
		);

		return dba::inArray($r);
	}
}
