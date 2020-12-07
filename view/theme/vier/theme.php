<?php
/**
 * Name: Vier
 * Version: 1.2
 * Author: Fabio <http://kirgroup.com/profile/fabrixxm>
 * Author: Ike <http://pirati.ca/profile/heluecht>
 * Author: Beanow <https://fc.oscp.info/profile/beanow>
 * Maintainer: Ike <http://pirati.ca/profile/heluecht>
 * Description: "Vier" is a very compact and modern theme. It uses the font awesome font library: http://fortawesome.github.com/Font-Awesome/
 */

use Friendica\App;
use Friendica\Content\ForumManager;
use Friendica\Core\Addon;
use Friendica\Core\Renderer;
use Friendica\Core\Search;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Model\GContact;
use Friendica\Util\Proxy as ProxyUtils;
use Friendica\Util\Strings;

function vier_init(App $a)
{
	$a->theme_events_in_profile = false;

	Renderer::setActiveTemplateEngine('smarty3');

	if (!empty($a->argv[0]) && ($a->argv[0] . ($a->argv[1] ?? '')) === ('profile' . ($a->user['nickname'] ?? '')) || $a->argv[0] === 'network' && local_user()) {
		vier_community_info();

		DI::page()['htmlhead'] .= "<link rel='stylesheet' type='text/css' href='view/theme/vier/wide.css' media='screen and (min-width: 1300px)'/>\n";
	}

	if (DI::mode()->isMobile() || DI::mode()->isMobile()) {
		DI::page()['htmlhead'] .= '<meta name=viewport content="width=device-width, initial-scale=1">'."\n";
		DI::page()['htmlhead'] .= '<link rel="stylesheet" type="text/css" href="view/theme/vier/mobile.css" media="screen"/>'."\n";
	}
	/// @todo deactivated since it doesn't work with desktop browsers at the moment
	//DI::page()['htmlhead'] .= '<link rel="stylesheet" type="text/css" href="view/theme/vier/mobile.css" media="screen and (max-width: 1000px)"/>'."\n";

	DI::page()['htmlhead'] .= <<< EOT
<link rel='stylesheet' type='text/css' href='view/theme/vier/narrow.css' media='screen and (max-width: 1100px)' />
<script type="text/javascript">
function showThread(id) {
	$("#collapsed-comments-" + id).show()
	$("#collapsed-comments-" + id + " .collapsed-comments").show()
}
function hideThread(id) {
	$("#collapsed-comments-" + id).hide()
	$("#collapsed-comments-" + id + " .collapsed-comments").hide()
}

function cmtBbOpen(id) {
	$("#comment-edit-bb-" + id).show();
}
function cmtBbClose(id) {
	$("#comment-edit-bb-" + id).hide();
}
</script>
EOT;

	if (DI::mode()->isMobile() || DI::mode()->isMobile()) {
		DI::page()['htmlhead'] .= <<< EOT
<script>
	$(document).ready(function() {
		$(".mobile-aside-toggle a").click(function(e){
			e.preventDefault();
			$("aside").toggleClass("show");
		});
		$(".tabs").click(function(e){
			$(this).toggleClass("show");
		});
	});
</script>
EOT;
	}

	// Hide the left menu bar
	/// @TODO maybe move this static array out where it should belong?
	if (empty(DI::page()['aside']) && in_array($a->argv[0], ["community", "events", "help", "delegation", "notifications",
			"probe", "webfinger", "login", "invite", "credits"])) {
		DI::page()['htmlhead'] .= "<link rel='stylesheet' href='view/theme/vier/hide.css' />";
	}
}

function get_vier_config($key, $default = false, $admin = false)
{
	if (local_user() && !$admin) {
		$result = DI::pConfig()->get(local_user(), "vier", $key);
		if (!is_null($result)) {
			return $result;
		}
	}

	$result = DI::config()->get("vier", $key);
	if (!is_null($result)) {
		return $result;
	}

	return $default;
}

function vier_community_info()
{
	$a = DI::app();

	$show_pages      = get_vier_config("show_pages", 1);
	$show_profiles   = get_vier_config("show_profiles", 1);
	$show_helpers    = get_vier_config("show_helpers", 1);
	$show_services   = get_vier_config("show_services", 1);
	$show_friends    = get_vier_config("show_friends", 1);
	$show_lastusers  = get_vier_config("show_lastusers", 1);

	// get_baseurl
	$url = DI::baseUrl();
	$aside['$url'] = $url;

	// comunity_profiles
	if ($show_profiles) {
		$r = GContact::suggestionQuery(local_user(), 0, 9);

		$tpl = Renderer::getMarkupTemplate('ch_directory_item.tpl');
		if (DBA::isResult($r)) {
			$aside['$comunity_profiles_title'] = DI::l10n()->t('Community Profiles');
			$aside['$comunity_profiles_items'] = [];

			foreach ($r as $rr) {
				$entry = Renderer::replaceMacros($tpl, [
					'$id' => $rr['id'],
					'$profile_link' => 'follow/?url='.urlencode($rr['url']),
					'$photo' => ProxyUtils::proxifyUrl($rr['photo'], false, ProxyUtils::SIZE_MICRO),
					'$alt_text' => $rr['name'],
				]);
				$aside['$comunity_profiles_items'][] = $entry;
			}
		}
	}

	// last 9 users
	if ($show_lastusers) {
		$condition = ['blocked' => false];
		if (!DI::config()->get('system', 'publish_all')) {
			$condition['publish'] = true;
		}

		$tpl = Renderer::getMarkupTemplate('ch_directory_item.tpl');

		$profiles = DBA::selectToArray('owner-view', [], $condition, ['order' => ['register_date' => true], 'limit' => [0, 9]]);

		if (DBA::isResult($profiles)) {
			$aside['$lastusers_title'] = DI::l10n()->t('Last users');
			$aside['$lastusers_items'] = [];

			foreach ($profiles as $profile) {
				$profile_link = 'profile/' . ((strlen($profile['nickname'])) ? $profile['nickname'] : $profile['uid']);
				$entry = Renderer::replaceMacros($tpl, [
					'$id' => $profile['id'],
					'$profile_link' => $profile_link,
					'$photo' => DI::baseUrl()->remove($profile['thumb']),
					'$alt_text' => $profile['name']]);
				$aside['$lastusers_items'][] = $entry;
			}
		}
	}

	//right_aside FIND FRIENDS
	if ($show_friends && local_user()) {
		$nv = [];
		$nv['findpeople'] = DI::l10n()->t('Find People');
		$nv['desc'] = DI::l10n()->t('Enter name or interest');
		$nv['label'] = DI::l10n()->t('Connect/Follow');
		$nv['hint'] = DI::l10n()->t('Examples: Robert Morgenstein, Fishing');
		$nv['findthem'] = DI::l10n()->t('Find');
		$nv['suggest'] = DI::l10n()->t('Friend Suggestions');
		$nv['similar'] = DI::l10n()->t('Similar Interests');
		$nv['random'] = DI::l10n()->t('Random Profile');
		$nv['inv'] = DI::l10n()->t('Invite Friends');
		$nv['directory'] = DI::l10n()->t('Global Directory');
		$nv['global_dir'] = Search::getGlobalDirectory();
		$nv['local_directory'] = DI::l10n()->t('Local Directory');

		$aside['$nv'] = $nv;
	}

	//Community_Pages at right_aside
	if ($show_pages && local_user()) {
		$cid = $_GET['cid'] ?? null;

		//sort by last updated item
		$lastitem = true;

		$contacts = ForumManager::getList($a->user['uid'], $lastitem, true, true);
		$total = count($contacts);
		$visible_forums = 10;

		if (count($contacts)) {
			$id = 0;

			foreach ($contacts as $contact) {
				$selected = (($cid == $contact['id']) ? ' forum-selected' : '');

				$entry = [
					'url'          => 'network?contactid=' . $contact['id'],
					'external_url' => Contact::magicLink($contact['url']),
					'name'         => $contact['name'],
					'cid'          => $contact['id'],
					'selected'     => $selected,
					'micro'        => DI::baseUrl()->remove(ProxyUtils::proxifyUrl($contact['micro'], false, ProxyUtils::SIZE_MICRO)),
					'id'           => ++$id,
				];
				$entries[] = $entry;
			}


			$tpl = Renderer::getMarkupTemplate('widget_forumlist_right.tpl');

			$page = Renderer::replaceMacros(
				$tpl,
				[
					'$title'          => DI::l10n()->t('Forums'),
					'$forums'         => $entries,
					'$link_desc'      => DI::l10n()->t('External link to forum'),
					'$total'          => $total,
					'$visible_forums' => $visible_forums,
					'$showmore'       => DI::l10n()->t('show more')]
			);

			$aside['$page'] = $page;
		}
	}
	// END Community Page

	// helpers
	if ($show_helpers) {
		$r = [];

		$helperlist = DI::config()->get("vier", "helperlist");

		$helpers = explode(",", $helperlist);

		if ($helpers) {
			$query = "";
			foreach ($helpers as $index => $helper) {
				if ($query != "") {
					$query .= ",";
				}

				$query .= "'".DBA::escape(Strings::normaliseLink(trim($helper)))."'";
			}

			$r = q("SELECT `url`, `name` FROM `gcontact` WHERE `nurl` IN (%s)", $query);
		}

		foreach ($r as $index => $helper) {
			$r[$index]["url"] = Contact::magicLink($helper["url"]);
		}

		$r[] = ["url" => "help/Quick-Start-guide", "name" => DI::l10n()->t("Quick Start")];

		$tpl = Renderer::getMarkupTemplate('ch_helpers.tpl');

		if ($r) {
			$helpers = [];
			$helpers['title'] = ["", DI::l10n()->t('Help'), "", ""];

			$aside['$helpers_items'] = [];

			foreach ($r as $rr) {
				$entry = Renderer::replaceMacros($tpl, [
					'$url' => $rr['url'],
					'$title' => $rr['name'],
				]);
				$aside['$helpers_items'][] = $entry;
			}

			$aside['$helpers'] = $helpers;
		}
	}
	// end helpers

	// connectable services
	if ($show_services) {
		/// @TODO This whole thing is hard-coded, better rewrite to Intercepting Filter Pattern (future-todo)
		$r = [];

		if (Addon::isEnabled("buffer")) {
			$r[] = ["photo" => "images/buffer.png", "name" => "Buffer"];
		}

		if (Addon::isEnabled("blogger")) {
			$r[] = ["photo" => "images/blogger.png", "name" => "Blogger"];
		}

		if (Addon::isEnabled("dwpost")) {
			$r[] = ["photo" => "images/dreamwidth.png", "name" => "Dreamwidth"];
		}

		if (Addon::isEnabled("ifttt")) {
			$r[] = ["photo" => "addon/ifttt/ifttt.png", "name" => "IFTTT"];
		}

		if (Addon::isEnabled("statusnet")) {
			$r[] = ["photo" => "images/gnusocial.png", "name" => "GNU Social"];
		}

		/// @TODO old-lost code (and below)?
		//if (Addon::isEnabled("ijpost")) {
		//	$r[] = array("photo" => "images/", "name" => "");
		//}

		if (Addon::isEnabled("libertree")) {
			$r[] = ["photo" => "images/libertree.png", "name" => "Libertree"];
		}

		//if (Addon::isEnabled("ljpost")) {
		//	$r[] = array("photo" => "images/", "name" => "");
		//}

		if (Addon::isEnabled("pumpio")) {
			$r[] = ["photo" => "images/pumpio.png", "name" => "pump.io"];
		}

		if (Addon::isEnabled("tumblr")) {
			$r[] = ["photo" => "images/tumblr.png", "name" => "Tumblr"];
		}

		if (Addon::isEnabled("twitter")) {
			$r[] = ["photo" => "images/twitter.png", "name" => "Twitter"];
		}

		if (Addon::isEnabled("wppost")) {
			$r[] = ["photo" => "images/wordpress.png", "name" => "Wordpress"];
		}

		if (function_exists("imap_open") && !DI::config()->get("system", "imap_disabled") && !DI::config()->get("system", "dfrn_only")) {
			$r[] = ["photo" => "images/mail.png", "name" => "E-Mail"];
		}

		$tpl = Renderer::getMarkupTemplate('ch_connectors.tpl');

		if (DBA::isResult($r)) {
			$con_services = [];
			$con_services['title'] = ["", DI::l10n()->t('Connect Services'), "", ""];
			$aside['$con_services'] = $con_services;

			foreach ($r as $rr) {
				$entry = Renderer::replaceMacros($tpl, [
					'$url' => $url,
					'$photo' => $rr['photo'],
					'$alt_text' => $rr['name'],
				]);
				$aside['$connector_items'][] = $entry;
			}
		}
	}
	//end connectable services

	//print right_aside
	$tpl = Renderer::getMarkupTemplate('communityhome.tpl');
	DI::page()['right_aside'] = Renderer::replaceMacros($tpl, $aside);
}

/**
 * @param int|null $uid
 * @return null
 * @see \Friendica\Core\Theme::getBackgroundColor()
 * @TODO Implement this function
 */
function vier_get_background_color(int $uid = null)
{
	return null;
}

/**
 * @param int|null $uid
 * @return null
 * @see \Friendica\Core\Theme::getThemeColor()
 * @TODO Implement this function
 */
function vier_get_theme_color(int $uid = null)
{
	return null;
}
