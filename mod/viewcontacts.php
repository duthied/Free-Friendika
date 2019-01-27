<?php
/**
 * @file mod/viewcontacts.php
 */

use Friendica\App;
use Friendica\Content\ContactSelector;
use Friendica\Content\Nav;
use Friendica\Content\Pager;
use Friendica\Core\Config;
use Friendica\Core\L10n;
use Friendica\Core\Protocol;
use Friendica\Core\Renderer;
use Friendica\Core\System;
use Friendica\Database\DBA;
use Friendica\Model\Contact;
use Friendica\Model\Profile;
use Friendica\Util\Proxy as ProxyUtils;

function viewcontacts_init(App $a)
{
	if (Config::get('system', 'block_public') && !local_user() && !remote_user()) {
		System::httpExit(403, ["title" => L10n::t('Access denied.')]);
	}

	if ($a->argc < 2) {
		System::httpExit(403, ["title" => L10n::t('Access denied.')]);
	}

	Nav::setSelected('home');

	$user = DBA::selectFirst('user', [], ['nickname' => $a->argv[1], 'blocked' => false]);
	if (!DBA::isResult($user)) {
		System::httpExit(404, ["title" => L10n::t('Page not found.')]);
	}

	$a->data['user'] = $user;
	$a->profile_uid  = $user['uid'];

	Profile::load($a, $a->argv[1]);
}

function viewcontacts_content(App $a)
{
	if (Config::get('system', 'block_public') && !local_user() && !remote_user()) {
		notice(L10n::t('Public access denied.') . EOL);
		return;
	}

	$is_owner = $a->profile['profile_uid'] == local_user();

	// tabs
	$o = Profile::getTabs($a, $is_owner, $a->data['user']['nickname']);

	if (!count($a->profile) || $a->profile['hide-friends']) {
		notice(L10n::t('Permission denied.') . EOL);
		return $o;
	}

	$condition = [
		'uid'     => $a->profile['uid'],
		'blocked' => false,
		'pending' => false,
		'hidden'  => false,
		'archive' => false,
		'network' => [Protocol::ACTIVITYPUB, Protocol::DFRN, Protocol::DIASPORA, Protocol::OSTATUS]
	];

	$total = DBA::count('contact', $condition);

	$pager = new Pager($a->query_string);

	$params = ['order' => ['name' => false], 'limit' => [$pager->getStart(), $pager->getItemsPerPage()]];

	$contacts_stmt = DBA::select('contact', [], $condition, $params);

	if (!DBA::isResult($contacts_stmt)) {
		info(L10n::t('No contacts.') . EOL);
		return $o;
	}

	$contacts = [];

	while ($contact = DBA::fetch($contacts_stmt)) {
		/// @TODO This triggers an E_NOTICE if 'self' is not there
		if ($contact['self']) {
			continue;
		}

		$contact_details = Contact::getDetailsByURL($contact['url'], $a->profile['uid'], $contact);

		$contacts[] = [
			'id'           => $contact['id'],
			'img_hover'    => L10n::t('Visit %s\'s profile [%s]', $contact_details['name'], $contact['url']),
			'photo_menu'   => Contact::photoMenu($contact),
			'thumb'        => ProxyUtils::proxifyUrl($contact_details['thumb'], false, ProxyUtils::SIZE_THUMB),
			'name'         => substr($contact_details['name'], 0, 20),
			'username'     => $contact_details['name'],
			'details'      => $contact_details['location'],
			'tags'         => $contact_details['keywords'],
			'about'        => $contact_details['about'],
			'account_type' => Contact::getAccountType($contact_details),
			'url'          => Contact::magicLink($contact['url']),
			'sparkle'      => '',
			'itemurl'      => (($contact_details['addr'] != "") ? $contact_details['addr'] : $contact['url']),
			'network'      => ContactSelector::networkToName($contact['network'], $contact['url']),
		];
	}

	DBA::close($contacts_stmt);

	$tpl = Renderer::getMarkupTemplate("viewcontact_template.tpl");
	$o .= Renderer::replaceMacros($tpl, [
		'$title'    => L10n::t('Contacts'),
		'$contacts' => $contacts,
		'$paginate' => $pager->renderFull($total),
	]);

	return $o;
}
