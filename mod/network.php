<?php
/**
 * @copyright Copyright (C) 2020, Friendica
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

use Friendica\App;
use Friendica\Content\ForumManager;
use Friendica\Content\Nav;
use Friendica\Content\Pager;
use Friendica\Content\Widget;
use Friendica\Content\Text\HTML;
use Friendica\Core\ACL;
use Friendica\Core\Hook;
use Friendica\Core\Logger;
use Friendica\Core\Renderer;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Model\Group;
use Friendica\Model\Item;
use Friendica\Model\Post\Category;
use Friendica\Model\Profile;
use Friendica\Model\User;
use Friendica\Module\Contact as ModuleContact;
use Friendica\Module\Security\Login;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Strings;

function network_init(App $a)
{
	if (!local_user()) {
		notice(DI::l10n()->t('Permission denied.'));
		return;
	}

	$is_a_date_query = false;

	$group_id = (($a->argc > 1 && is_numeric($a->argv[1])) ? intval($a->argv[1]) : 0);

	$cid = 0;
	if (!empty($_GET['contactid'])) {
		$cid = $_GET['contactid'];
		$_GET['nets'] = '';
		$group_id = 0;
	}

	if ($a->argc > 1) {
		for ($x = 1; $x < $a->argc; $x ++) {
			if (DI::dtFormat()->isYearMonthDay($a->argv[$x])) {
				$is_a_date_query = true;
				break;
			}
		}
	}

	// convert query string to array. remove friendica args
	$query_array = [];
	parse_str(parse_url(DI::args()->getQueryString(), PHP_URL_QUERY), $query_array);

	// fetch last used network view and redirect if needed
	if (!$is_a_date_query) {
		$sel_nets = $_GET['nets'] ?? '';
		$sel_tabs = network_query_get_sel_tab($a);
		$sel_groups = network_query_get_sel_group($a);
		$last_sel_tabs = DI::pConfig()->get(local_user(), 'network.view', 'tab.selected');

		$remember_tab = ($sel_tabs[0] === 'active' && is_array($last_sel_tabs) && $last_sel_tabs[0] !== 'active');

		$net_baseurl = '/network';
		$net_args = [];

		if ($sel_groups !== false) {
			$net_baseurl .= '/' . $sel_groups;
		}

		if ($remember_tab) {
			// redirect if current selected tab is '/network' and
			// last selected tab is _not_ '/network?order=activity'.
			// and this isn't a date query

			$tab_args = [
				'order=activity', //all
				'order=post',     //postord
				'conv=1',         //conv
				'star=1',         //starred
			];

			$k = array_search('active', $last_sel_tabs);

			if ($k != 3) {
				// parse out tab queries
				$dest_qa = [];
				$dest_qs = $tab_args[$k];
				parse_str($dest_qs, $dest_qa);
				$net_args = array_merge($net_args, $dest_qa);
			} else {
				$remember_tab = false;
			}
		}

		if ($sel_nets) {
			$net_args['nets'] = $sel_nets;
		}

		if ($remember_tab) {
			$net_args = array_merge($query_array, $net_args);
			$net_queries = http_build_query($net_args);

			$redir_url = ($net_queries ? $net_baseurl . '?' . $net_queries : $net_baseurl);

			DI::baseUrl()->redirect($redir_url);
		}
	}

	if (empty(DI::page()['aside'])) {
		DI::page()['aside'] = '';
	}

	if (!empty($a->argv[1]) && in_array($a->argv[1], ['person', 'organisation', 'news', 'community'])) {
		$accounttype = $a->argv[1];
	} else {
		$accounttype = '';
	}

	DI::page()['aside'] .= Widget::accounts('network', $accounttype);
	DI::page()['aside'] .= Group::sidebarWidget('network/0', 'network', 'standard', $group_id);
	DI::page()['aside'] .= ForumManager::widget(local_user(), $cid);
	DI::page()['aside'] .= Widget::postedByYear('network', local_user(), false);
	DI::page()['aside'] .= Widget::networks('network', $_GET['nets'] ?? '');
	DI::page()['aside'] .= Widget\SavedSearches::getHTML(DI::args()->getQueryString());
	DI::page()['aside'] .= Widget::fileAs('network', $_GET['file'] ?? '');
}

/**
 * Return selected tab from query
 *
 * urls -> returns
 *        '/network'                => $no_active = 'active'
 *        '/network?order=activity' => $activity_active = 'active'
 *        '/network?order=post'     => $postord_active = 'active'
 *        '/network?conv=1',        => $conv_active = 'active'
 *        '/network?star=1',        => $starred_active = 'active'
 *
 * @param App $a
 * @return array ($no_active, $activity_active, $postord_active, $conv_active, $starred_active);
 */
function network_query_get_sel_tab(App $a)
{
	$no_active = '';
	$starred_active = '';
	$all_active = '';
	$conv_active = '';
	$postord_active = '';

	if (!empty($_GET['star'])) {
		$starred_active = 'active';
	}

	if (!empty($_GET['conv'])) {
		$conv_active = 'active';
	}

	if (($starred_active == '') && ($conv_active == '')) {
		$no_active = 'active';
	}

	if ($no_active == 'active' && !empty($_GET['order'])) {
		switch($_GET['order']) {
			case 'post' :     $postord_active = 'active'; $no_active=''; break;
			case 'activity' : $all_active     = 'active'; $no_active=''; break;
		}
	}

	return [$no_active, $all_active, $postord_active, $conv_active, $starred_active];
}

function network_query_get_sel_group(App $a)
{
	$group = false;

	if ($a->argc >= 2 && is_numeric($a->argv[1])) {
		$group = $a->argv[1];
	}

	return $group;
}

/**
 * Sets the pager data and returns SQL
 *
 * @param App     $a      The global App
 * @param Pager   $pager
 * @return string SQL with the appropriate LIMIT clause
 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
 */
function networkPager(App $a, Pager $pager)
{
	if (DI::mode()->isMobile()) {
		$itemspage_network = DI::pConfig()->get(local_user(), 'system', 'itemspage_mobile_network',
			DI::config()->get('system', 'itemspage_network_mobile'));
	} else {
		$itemspage_network = DI::pConfig()->get(local_user(), 'system', 'itemspage_network',
			DI::config()->get('system', 'itemspage_network'));
	}

	//  now that we have the user settings, see if the theme forces
	//  a maximum item number which is lower then the user choice
	if (($a->force_max_items > 0) && ($a->force_max_items < $itemspage_network)) {
		$itemspage_network = $a->force_max_items;
	}

	$pager->setItemsPerPage($itemspage_network);
}

/**
 * Sets items as seen
 *
 * @param array $condition The array with the SQL condition
 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
 */
function networkSetSeen($condition)
{
	if (empty($condition)) {
		return;
	}

	$unseen = Item::exists($condition);

	if ($unseen) {
		Item::update(['unseen' => false], $condition);
	}
}

/**
 * Create the conversation HTML
 *
 * @param App     $a      The global App
 * @param array   $items  Items of the conversation
 * @param Pager   $pager
 * @param string  $mode   Display mode for the conversation
 * @param integer $update Used for the automatic reloading
 * @param string  $ordering
 * @return string HTML of the conversation
 * @throws ImagickException
 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
 */
function networkConversation(App $a, $items, Pager $pager, $mode, $update, $ordering = '')
{
	// Set this so that the conversation function can find out contact info for our wall-wall items
	$a->page_contact = $a->contact;

	if (!is_array($items)) {
		Logger::info('Expecting items to be an array.', ['items' => $items]);
		$items = [];
	}

	$o = conversation($a, $items, $mode, $update, false, $ordering, local_user());

	if (!$update) {
		if (DI::pConfig()->get(local_user(), 'system', 'infinite_scroll')) {
			$o .= HTML::scrollLoader();
		} else {
			$o .= $pager->renderMinimal(count($items));
		}
	}

	return $o;
}

function network_content(App $a, $update = 0, $parent = 0)
{
	if (!local_user()) {
		return Login::form();
	}

	/// @TODO Is this really necessary? $a is already available to hooks
	$arr = ['query' => DI::args()->getQueryString()];
	Hook::callAll('network_content_init', $arr);

	if (DI::pConfig()->get(local_user(), 'system', 'infinite_scroll') && ($_GET['mode'] ?? '') != 'minimal') {
		$tpl = Renderer::getMarkupTemplate('infinite_scroll_head.tpl');
		$o = Renderer::replaceMacros($tpl, ['$reload_uri' => DI::args()->getQueryString()]);
	} else {
		$o = '';
	}

	switch ($a->argv[1] ?? '') {
		case 'person':
			$account = User::ACCOUNT_TYPE_PERSON;
			break;
		case 'organisation':
			$account = User::ACCOUNT_TYPE_ORGANISATION;
			break;
		case 'news':
			$account = User::ACCOUNT_TYPE_NEWS;
			break;
		case 'community':
			$account = User::ACCOUNT_TYPE_COMMUNITY;
			break;
		default:
			$account = null;
		break;
	}

	if (!empty($_GET['file'])) {
		$o .= networkFlatView($a, $update, $account);
	} else {
		$o .= networkThreadedView($a, $update, $parent, $account);
	}

	if (!$update && ($o === '')) {
		notice(DI::l10n()->t("No items found"));
	}

	return $o;
}

/**
 * Get the network content in flat view
 *
 * @param App     $a      The global App
 * @param integer $update Used for the automatic reloading
 * @return string HTML of the network content in flat view
 * @throws ImagickException
 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
 * @global Pager  $pager
 */
function networkFlatView(App $a, $update, $account)
{
	global $pager;
	// Rawmode is used for fetching new content at the end of the page
	$rawmode = (isset($_GET['mode']) && ($_GET['mode'] == 'raw'));

	$o = '';

	$file = $_GET['file'] ?? '';

	if (!$update && !$rawmode) {
		$tabs = network_tabs($a);
		$o .= $tabs;

		Nav::setSelected('network');

		$x = [
			'is_owner' => true,
			'allow_location' => $a->user['allow_location'],
			'default_location' => $a->user['default-location'],
			'nickname' => $a->user['nickname'],
			'lockstate' => (is_array($a->user) &&
			(strlen($a->user['allow_cid']) || strlen($a->user['allow_gid']) ||
			strlen($a->user['deny_cid']) || strlen($a->user['deny_gid'])) ? 'lock' : 'unlock'),
			'default_perms' => ACL::getDefaultUserPermissions($a->user),
			'acl' => ACL::getFullSelectorHTML(DI::page(), $a->user, true),
			'bang' => '',
			'visitor' => 'block',
			'profile_uid' => local_user(),
			'content' => '',
		];

		$o .= status_editor($a, $x);

		if (!DI::config()->get('theme', 'hide_eventlist')) {
			$o .= Profile::getBirthdays();
			$o .= Profile::getEventsReminderHTML();
		}
	}

	$pager = new Pager(DI::l10n(), DI::args()->getQueryString());

	networkPager($a, $pager);

	if (strlen($file)) {
		$item_params = ['order' => ['uri-id' => true]];
		$term_condition = ['name' => $file, 'type' => Category::FILE, 'uid' => local_user()];
		$term_params = ['order' => ['uri-id' => true], 'limit' => [$pager->getStart(), $pager->getItemsPerPage()]];
		$result = DBA::select('category-view', ['uri-id'], $term_condition, $term_params);

		$posts = [];
		while ($term = DBA::fetch($result)) {
			$posts[] = $term['uri-id'];
		}
		DBA::close($result);

		if (count($posts) == 0) {
			return '';
		}
		$item_condition = ['uid' => local_user(), 'uri-id' => $posts];
	} else {
		$item_params = ['order' => ['id' => true]];
		$item_condition = ['uid' => local_user()];
		$item_params['limit'] = [$pager->getStart(), $pager->getItemsPerPage()];

		networkSetSeen(['unseen' => true, 'uid' => local_user()]);
	}

	if (!empty($account)) {
		$item_condition['contact-type'] = $account;
	}

	$result = Item::selectForUser(local_user(), [], $item_condition, $item_params);
	$items = Item::inArray($result);
	$o .= networkConversation($a, $items, $pager, 'network-new', $update);

	return $o;
}

/**
 * Get the network content in threaded view
 *
 * @param  App     $a      The global App
 * @param  integer $update Used for the automatic reloading
 * @param  integer $parent
 * @return string HTML of the network content in flat view
 * @throws ImagickException
 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
 * @global Pager   $pager
 */
function networkThreadedView(App $a, $update, $parent, $account)
{
	/// @TODO this will have to be converted to a static property of the converted Module\Network class
	global $pager;

	// Rawmode is used for fetching new content at the end of the page
	$rawmode = (isset($_GET['mode']) AND ($_GET['mode'] == 'raw'));

	$last_received = isset($_GET['last_received']) ? DateTimeFormat::utc($_GET['last_received']) : '';
	$last_commented = isset($_GET['last_commented']) ? DateTimeFormat::utc($_GET['last_commented']) : '';
	$last_created = isset($_GET['last_created']) ? DateTimeFormat::utc($_GET['last_created']) : '';
	$last_uriid = isset($_GET['last_uriid']) ? intval($_GET['last_uriid']) : 0;

	$datequery = $datequery2 = '';

	$gid = 0;

	$default_permissions = [];

	if ($a->argc > 1) {
		for ($x = 1; $x < $a->argc; $x ++) {
			if (DI::dtFormat()->isYearMonthDay($a->argv[$x])) {
				if ($datequery) {
					$datequery2 = Strings::escapeHtml($a->argv[$x]);
				} else {
					$datequery = Strings::escapeHtml($a->argv[$x]);
					$_GET['order'] = 'post';
				}
			} elseif (intval($a->argv[$x])) {
				$gid = intval($a->argv[$x]);
				$default_permissions['allow_gid'] = [$gid];
			}
		}
	}

	$o = '';

	$cid   = intval($_GET['contactid'] ?? 0);
	$star  = intval($_GET['star']      ?? 0);
	$conv  = intval($_GET['conv']      ?? 0);
	$order = Strings::escapeTags(($_GET['order'] ?? '') ?: 'activity');
	$nets  =        $_GET['nets']      ?? '';

	$allowedCids = [];
	if ($cid) {
		$allowedCids[] = (int) $cid;
	} elseif ($nets) {
		$condition = [
			'uid'     => local_user(),
			'network' => $nets,
			'self'    => false,
			'blocked' => false,
			'pending' => false,
			'archive' => false,
			'rel'     => [Contact::SHARING, Contact::FRIEND],
		];
		$contactStmt = DBA::select('contact', ['id'], $condition);
		while ($contact = DBA::fetch($contactStmt)) {
			$allowedCids[] = (int) $contact['id'];
		}
		DBA::close($contactStmt);
	}

	if (count($allowedCids)) {
		$default_permissions['allow_cid'] = $allowedCids;
	}

	if (!$update && !$rawmode) {
		$tabs = network_tabs($a);
		$o .= $tabs;

		Nav::setSelected('network');

		$content = '';

		if ($cid) {
			// If $cid belongs to a communitity forum or a privat goup,.add a mention to the status editor
			$condition = ["`id` = ? AND (`forum` OR `prv`)", $cid];
			$contact = DBA::selectFirst('contact', ['addr'], $condition);
			if (!empty($contact['addr'])) {
				$content = '!' . $contact['addr'];
			}
		}

		$x = [
			'is_owner' => true,
			'allow_location' => $a->user['allow_location'],
			'default_location' => $a->user['default-location'],
			'nickname' => $a->user['nickname'],
			'lockstate' => ($gid || $cid || $nets || (is_array($a->user) &&
			(strlen($a->user['allow_cid']) || strlen($a->user['allow_gid']) ||
			strlen($a->user['deny_cid']) || strlen($a->user['deny_gid']))) ? 'lock' : 'unlock'),
			'default_perms' => ACL::getDefaultUserPermissions($a->user),
			'acl' => ACL::getFullSelectorHTML(DI::page(), $a->user, true, $default_permissions),
			'bang' => (($gid || $cid || $nets) ? '!' : ''),
			'visitor' => 'block',
			'profile_uid' => local_user(),
			'content' => $content,
		];

		$o .= status_editor($a, $x);
	}

	$conditionFields = ['uid' => local_user()];
	$conditionStrings = [];

	if (!empty($account)) {
		$conditionFields['contact-type'] = $account;
	}

	if ($star) {
		$conditionFields['starred'] = true;
	}
	if ($conv) {
		$conditionFields['mention'] = true;
	}
	if ($nets) {
		$conditionFields['network'] = $nets;
	}

	if ($datequery) {
		$conditionStrings = DBA::mergeConditions($conditionStrings, ["`received` <= ? ", DateTimeFormat::convert($datequery, 'UTC', date_default_timezone_get())]);
	}
	if ($datequery2) {
		$conditionStrings = DBA::mergeConditions($conditionStrings, ["`received` >= ? ", DateTimeFormat::convert($datequery2, 'UTC', date_default_timezone_get())]);
	}

	if ($gid) {
		$group = DBA::selectFirst('group', ['name'], ['id' => $gid, 'uid' => local_user()]);
		if (!DBA::isResult($group)) {
			if ($update) {
				exit();
			}
			notice(DI::l10n()->t('No such group'));
			DI::baseUrl()->redirect('network/0');
			// NOTREACHED
		}

		$conditionStrings = DBA::mergeConditions($conditionStrings, ["`contact-id` IN (SELECT `contact-id` FROM `group_member` WHERE `gid` = ?)", $gid]);

		$o = Renderer::replaceMacros(Renderer::getMarkupTemplate('section_title.tpl'), [
			'$title' => DI::l10n()->t('Group: %s', $group['name'])
		]) . $o;
	} elseif ($cid) {
		$contact = Contact::getById($cid);
		if (DBA::isResult($contact)) {
			$conditionFields['contact-id'] = $cid;

			$o = Renderer::replaceMacros(Renderer::getMarkupTemplate('viewcontact_template.tpl'), [
				'contacts' => [ModuleContact::getContactTemplateVars($contact)],
				'id' => 'network',
			]) . $o;
		} else {
			notice(DI::l10n()->t('Invalid contact.'));
			DI::baseUrl()->redirect('network');
			// NOTREACHED
		}
	} elseif (!$update && !DI::config()->get('theme', 'hide_eventlist')) {
		$o .= Profile::getBirthdays();
		$o .= Profile::getEventsReminderHTML();
	}

	// Normal conversation view
	if ($order === 'post') {
		$ordering = '`received`';
		$order_mode = 'received';
	} else {
		$ordering = '`commented`';
		$order_mode = 'commented';
	}

	$pager = new Pager(DI::l10n(), DI::args()->getQueryString());

	networkPager($a, $pager);

	if (DI::pConfig()->get(local_user(), 'system', 'infinite_scroll')) {
		$pager->setPage(1);
	}

	// Currently only the order modes "received" and "commented" are in use
	switch ($order_mode) {
		case 'received':
			if ($last_received != '') {
				$conditionStrings = DBA::mergeConditions($conditionStrings, ["`received` < ?", $last_received]);
			}
			break;
		case 'commented':
			if ($last_commented != '') {
				$conditionStrings = DBA::mergeConditions($conditionStrings, ["`commented` < ?", $last_commented]);
			}
			break;
		case 'created':
			if ($last_created != '') {
				$conditionStrings = DBA::mergeConditions($conditionStrings, ["`created` < ?", $last_created]);
			}
			break;
		case 'uriid':
			if ($last_uriid > 0) {
				$conditionStrings = DBA::mergeConditions($conditionStrings, ["`uri-id` < ?", $last_uriid]);
			}
			break;
	}

	// Fetch a page full of parent items for this page
	if ($update) {
		if (!empty($parent)) {
			// Load only a single thread
			$conditionFields['parent'] = $parent;
		} elseif ($order === 'post') {
			// Only load new toplevel posts
			$conditionFields['unseen'] = true;
			$conditionFields['gravity'] = GRAVITY_PARENT;
		} else {
			// Load all unseen items
			$conditionFields['unseen'] = true;
		}

		$params = ['order' => [$order_mode => true], 'limit' => 100];
		$table = 'network-item-view';
	} else {
		$params = ['order' => [$order_mode => true], 'limit' => [$pager->getStart(), $pager->getItemsPerPage()]];
		$table = 'network-thread-view';
	}
	$r = DBA::selectToArray($table, [], DBA::mergeConditions($conditionFields, $conditionStrings), $params);

	return $o . network_display_post($a, $pager, (!$gid && !$cid && !$star), $update, $ordering, $r);
}

function network_display_post($a, $pager, $mark_all, $update, $ordering, $items)
{
	$parents_str = '';

	if (DBA::isResult($items)) {
		$parents_arr = [];

		foreach ($items as $item) {
			if (!in_array($item['parent'], $parents_arr) && ($item['parent'] > 0)) {
				$parents_arr[] = $item['parent'];
			}
		}
		$parents_str = implode(', ', $parents_arr);
	}

	$pager->setQueryString(DI::args()->getQueryString());

	// We aren't going to try and figure out at the item, group, and page
	// level which items you've seen and which you haven't. If you're looking
	// at the top level network page just mark everything seen.

	if ($mark_all) {
		$condition = ['unseen' => true, 'uid' => local_user()];
		networkSetSeen($condition);
	} elseif ($parents_str) {
		$condition = ["`uid` = ? AND `unseen` AND `parent` IN (" . DBA::escape($parents_str) . ")", local_user()];
		networkSetSeen($condition);
	}

	return networkConversation($a, $items, $pager, 'network', $update, $ordering);
}

/**
 * Get the network tabs menu
 *
 * @param App $a The global App
 * @return string Html of the networktab
 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
 */
function network_tabs(App $a)
{
	// item filter tabs
	/// @TODO fix this logic, reduce duplication
	/// DI::page()['content'] .= '<div class="tabs-wrapper">';
	list($no_active, $all_active, $post_active, $conv_active, $starred_active) = network_query_get_sel_tab($a);

	// if no tabs are selected, defaults to activitys
	if ($no_active == 'active') {
		$all_active = 'active';
	}

	$cmd = DI::args()->getCommand();

	$def_param = [];
	if (!empty($_GET['contactid'])) {
		$def_param['contactid'] = $_GET['contactid'];
	}

	// tabs
	$tabs = [
		[
			'label'	=> DI::l10n()->t('Latest Activity'),
			'url'	=> $cmd . '?' . http_build_query(array_merge($def_param, ['order' => 'activity'])),
			'sel'	=> $all_active,
			'title'	=> DI::l10n()->t('Sort by latest activity'),
			'id'	=> 'activity-order-tab',
			'accesskey' => 'e',
		],
		[
			'label'	=> DI::l10n()->t('Latest Posts'),
			'url'	=> $cmd . '?' . http_build_query(array_merge($def_param, ['order' => 'post'])),
			'sel'	=> $post_active,
			'title'	=> DI::l10n()->t('Sort by post received date'),
			'id'	=> 'post-order-tab',
			'accesskey' => 't',
		],
	];

	$tabs[] = [
		'label'	=> DI::l10n()->t('Personal'),
		'url'	=> $cmd . '?' . http_build_query(array_merge($def_param, ['conv' => true])),
		'sel'	=> $conv_active,
		'title'	=> DI::l10n()->t('Posts that mention or involve you'),
		'id'	=> 'personal-tab',
		'accesskey' => 'r',
	];

	$tabs[] = [
		'label'	=> DI::l10n()->t('Starred'),
		'url'	=> $cmd . '?' . http_build_query(array_merge($def_param, ['star' => true])),
		'sel'	=> $starred_active,
		'title'	=> DI::l10n()->t('Favourite Posts'),
		'id'	=> 'starred-posts-tab',
		'accesskey' => 'm',
	];

	// save selected tab, but only if not in file mode
	if (empty($_GET['file'])) {
		DI::pConfig()->set(local_user(), 'network.view', 'tab.selected', [
			$all_active, $post_active, $conv_active, $starred_active
		]);
	}

	$arr = ['tabs' => $tabs];
	Hook::callAll('network_tabs', $arr);

	$tpl = Renderer::getMarkupTemplate('common_tabs.tpl');

	return Renderer::replaceMacros($tpl, ['$tabs' => $arr['tabs']]);

	// --- end item filter tabs
}
