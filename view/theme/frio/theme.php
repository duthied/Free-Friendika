<?php
/*
 * Name: frio
 * Description: Bootstrap V3 theme. The theme is currently under construction, so it is far from finished. For further information have a look at the <a href="https://github.com/friendica/friendica/tree/develop/view/theme/frio/README.md">ReadMe</a>.
 * Version: V.0.8.5
 * Author: Rabuzarus <https://friendica.kommune4.de/profile/rabuzarus>
 *
 */

use Friendica\App;
use Friendica\Content\Text\Plaintext;
use Friendica\Content\Widget;
use Friendica\Core\Hook;
use Friendica\Core\Logger;
use Friendica\Core\Renderer;
use Friendica\Core\Session;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model;
use Friendica\Model\Contact;
use Friendica\Module;
use Friendica\Util\Strings;

const FRIO_SCHEME_ACCENT_BLUE   = '#1e87c2';
const FRIO_SCHEME_ACCENT_RED    = '#b50404';
const FRIO_SCHEME_ACCENT_PURPLE = '#a54bad';
const FRIO_SCHEME_ACCENT_GREEN  = '#218f39';
const FRIO_SCHEME_ACCENT_PINK   = '#d900a9';

function frio_init(App $a)
{
	global $frio;
	$frio = 'view/theme/frio';

	// disable the events module link in the profile tab
	$a->setThemeInfoValue('events_in_profile', false);
	$a->setThemeInfoValue('videowidth', 622);

	Renderer::setActiveTemplateEngine('smarty3');

	// if the device is a mobile device set js is_mobile
	// variable so the js scripts can use this information
	if (DI::mode()->isMobile() || DI::mode()->isMobile()) {
		DI::page()['htmlhead'] .= <<< EOT
			<script type="text/javascript">
				var is_mobile = 1;
			</script>
EOT;
	}
}

function frio_install()
{
	Hook::register('prepare_body_final', 'view/theme/frio/theme.php', 'frio_item_photo_links');
	Hook::register('item_photo_menu', 'view/theme/frio/theme.php', 'frio_item_photo_menu');
	Hook::register('contact_photo_menu', 'view/theme/frio/theme.php', 'frio_contact_photo_menu');
	Hook::register('nav_info', 'view/theme/frio/theme.php', 'frio_remote_nav');
	Hook::register('display_item', 'view/theme/frio/theme.php', 'frio_display_item');

	Logger::info('installed theme frio');
}

/**
 * Replace friendica photo links hook
 *
 *  This function does replace the links to photos
 *  of other friendica users. Original the photos are
 *  linked to the photo page. Now they will linked directly
 *  to the photo file. This function is nessesary to use colorbox
 *  in the network stream
 *
 * @param App $a Unused but required by hook definition
 * @param array $body_info The item and its html output
 */
function frio_item_photo_links(App $a, &$body_info)
{
	$occurence = 0;
	$p = Plaintext::getBoundariesPosition($body_info['html'], '<a', '>');
	while ($p !== false && ($occurence++ < 500)) {
		$link = substr($body_info['html'], $p['start'], $p['end'] - $p['start']);
		$matches = [];

		preg_match('/\/photos\/[\w]+\/image\/([\w]+)/', $link, $matches);
		if ($matches) {
			// Replace the link for the photo's page with a direct link to the photo itself
			$newlink = str_replace($matches[0], "/photo/{$matches[1]}", $link);

			// Add a "quiet" parameter to any redir links to prevent the "XX welcomes YY" info boxes
			$newlink = preg_replace('/href="([^"]+)\/redir\/([^"]+)&url=([^"]+)"/', 'href="$1/redir/$2&quiet=1&url=$3"', $newlink);

			// Having any arguments to the link for Colorbox causes it to fetch base64 code instead of the image
			$newlink = preg_replace('/\/[?&]zrl=([^&"]+)/', '', $newlink);

			$body_info['html'] = str_replace($link, $newlink, $body_info['html']);
		}

		$p = Plaintext::getBoundariesPosition($body_info['html'], '<a', '>', $occurence);
	}
}

/**
 * Replace links of the item_photo_menu hook
 *
 *  This function replaces the original poke and the message links
 *  to call the addToModal javascript function so this pages can
 *  be loaded in a bootstrap modal
 *
 * @param App $a Unused but required by the hook definition
 * @param array $arr Contains item data and the original photo_menu
 */
function frio_item_photo_menu(App $a, &$arr)
{
	foreach ($arr['menu'] as $k => $v) {
		if (strpos($v, '/poke') === 0 || strpos($v, 'message/new/') === 0) {
			$v = 'javascript:addToModal(\'' . $v . '\'); return false;';
			$arr['menu'][$k] = $v;
		}
	}
}

/**
 * Replace links of the contact_photo_menu
 *
 *  This function replaces the original poke and the message links
 *  to call the addToModal javascript function so this pages can
 *  be loaded in a bootstrap modal
 *  Additionally the profile, status and photo page links  will be changed
 *  to don't open in a new tab if the contact is a friendica contact.
 *
 * @param App $a The app data
 * @param array $args Contains contact data and the original photo_menu
 */
function frio_contact_photo_menu(App $a, &$args)
{
	$cid = $args['contact']['id'];

	if (!empty($args['menu']['poke'])) {
		$pokelink = $args['menu']['poke'][1];
	} else {
		$pokelink = '';
	}

	if (!empty($args['menu']['poke'])) {
		$pmlink = $args['menu']['pm'][1];
	} else {
		$pmlink = '';
	}

	// Set the the indicator for opening the status, profile and photo pages
	// in a new tab to false if the contact a dfrn (friendica) contact
	// We do this because we can go back on foreign friendica pages throuhg
	// friendicas "magic-link" which indicates a friendica user on froreign
	// friendica servers as remote user or visitor
	//
	// The value for opening in a new tab is e.g. when
	// $args['menu']['status'][2] is true. If the value of the [2] key is true
	// and if it's a friendica contact we set it to false
	foreach ($args['menu'] as $k => $v) {
		if ($k === 'status' || $k === 'profile' || $k === 'photos') {
			$v[2] = (($args['contact']['network'] === 'dfrn') ? false : true);
			$args['menu'][$k][2] = $v[2];
		}
	}

	// Add to pm and poke links a new key with the value 'modal'.
	// Later we can make conditions in the corresponing templates (e.g.
	// contact_template.tpl)
	if (strpos($pokelink, $cid . '/poke') !== false) {
		$args['menu']['poke'][3] = 'modal';
	}

	if (strpos($pmlink, 'message/new/' . $cid) !== false) {
		$args['menu']['pm'][3] = 'modal';
	}
}

/**
 * Construct remote nav menu
 *
 *  It creates a remote baseurl form $_SESSION for remote users and friendica
 *  visitors. This url will be added to some of the nav links. With this behaviour
 *  the user will come back to her/his own pages on his/her friendica server.
 *  Not all possible links are available (notifications, administrator, manage,
 *  notes aren't available because we have no way the check remote permissions)..
 *  Some links will point to the local pages because the user would expect
 *  local page (these pages are: search, community, help, apps, directory).
 *
 * @param App   $a        The App class
 * @param array $nav_info The original nav info array: nav, banner, userinfo, sitelocation
 * @throws Exception
 */
function frio_remote_nav(App $a, array &$nav_info)
{
	// get the homelink from $_XSESSION
	$homelink = Model\Profile::getMyURL();
	if (!$homelink) {
		$homelink = Session::get('visitor_home', '');
	}

	// since $userinfo isn't available for the hook we write it to the nav array
	// this isn't optimal because the contact query will be done now twice
	$fields = ['id', 'url', 'avatar', 'micro', 'name', 'nick', 'baseurl', 'updated'];
	if ($a->isLoggedIn()) {
		$remoteUser = Contact::selectFirst($fields, ['uid' => $a->getLoggedInUserId(), 'self' => true]);
	} elseif (!local_user() && remote_user()) {
		$remoteUser = Contact::getById(remote_user(), $fields);
		$nav_info['nav']['remote'] = DI::l10n()->t('Guest');
	} elseif (Model\Profile::getMyURL()) {
		$remoteUser = Contact::getByURL($homelink, null, $fields);
		$nav_info['nav']['remote'] = DI::l10n()->t('Visitor');
	} else {
		$remoteUser = null;
	}

	if (DBA::isResult($remoteUser)) {
		$nav_info['userinfo'] = [
			'icon' => Contact::getMicro($remoteUser),
			'name' => $remoteUser['name'],
		];
		$server_url = $remoteUser['baseurl'];
	}

	if (!local_user() && !empty($server_url) && !is_null($remoteUser)) {
		// user menu
		$nav_info['nav']['usermenu'][] = [$server_url . '/profile/' . $remoteUser['nick'], DI::l10n()->t('Status'), '', DI::l10n()->t('Your posts and conversations')];
		$nav_info['nav']['usermenu'][] = [$server_url . '/profile/' . $remoteUser['nick'] . '/profile', DI::l10n()->t('Profile'), '', DI::l10n()->t('Your profile page')];
		$nav_info['nav']['usermenu'][] = [$server_url . '/photos/' . $remoteUser['nick'], DI::l10n()->t('Photos'), '', DI::l10n()->t('Your photos')];
		$nav_info['nav']['usermenu'][] = [$server_url . '/profile/' . $remoteUser['nick'] . '/media', DI::l10n()->t('Media'), '', DI::l10n()->t('Your postings with media')];
		$nav_info['nav']['usermenu'][] = [$server_url . '/events/', DI::l10n()->t('Events'), '', DI::l10n()->t('Your events')];

		// navbar links
		$nav_info['nav']['network'] = [$server_url . '/network', DI::l10n()->t('Network'), '', DI::l10n()->t('Conversations from your friends')];
		$nav_info['nav']['events'] = [$server_url . '/events', DI::l10n()->t('Events'), '', DI::l10n()->t('Events and Calendar')];
		$nav_info['nav']['messages'] = [$server_url . '/message', DI::l10n()->t('Messages'), '', DI::l10n()->t('Private mail')];
		$nav_info['nav']['settings'] = [$server_url . '/settings', DI::l10n()->t('Settings'), '', DI::l10n()->t('Account settings')];
		$nav_info['nav']['contacts'] = [$server_url . '/contact', DI::l10n()->t('Contacts'), '', DI::l10n()->t('Manage/edit friends and contacts')];
		$nav_info['nav']['sitename'] = DI::config()->get('config', 'sitename');
	}
}

function frio_display_item(App $a, &$arr)
{
	// Add follow to the item menu
	$followThread = [];
	if (
		local_user()
		&& in_array($arr['item']['uid'], [0, local_user()])
		&& $arr['item']['gravity'] == GRAVITY_PARENT
		&& !$arr['item']['self']
		&& !$arr['item']['mention']
	) {
		$followThread = [
			'menu'   => 'follow_thread',
			'title'  => DI::l10n()->t('Follow Thread'),
			'action' => 'doFollowThread(' . $arr['item']['id'] . ');',
			'href'   => '#'
		];
	}
	$arr['output']['follow_thread'] = $followThread;
}
