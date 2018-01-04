<?php

/**
 * @file src/Module/GlobalModule.php
 */

namespace Friendica\Module;

use Friendica\BaseModule;
use Friendica\Core\Config;
use Friendica\Core\PConfig;
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

		$r = self::getPublicItems($a->pager['start'], $a->pager['itemspage']);

		if (!DBM::is_result($r)) {
			info(t('No results.') . EOL);
			return $o;
		}

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
