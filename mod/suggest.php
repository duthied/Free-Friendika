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
use Friendica\Content\ContactSelector;
use Friendica\Content\Widget;
use Friendica\Core\Renderer;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Model\GContact;
use Friendica\Util\Proxy as ProxyUtils;

function suggest_init(App $a)
{
	if (! local_user()) {
		return;
	}
}

function suggest_post(App $a)
{
	if (!empty($_POST['ignore']) && !empty($_POST['confirm'])) {
		DBA::insert('gcign', ['uid' => local_user(), 'gcid' => $_POST['ignore']]);
		notice(DI::l10n()->t('Contact suggestion successfully ignored.'));
	}

	DI::baseUrl()->redirect('suggest');
}

function suggest_content(App $a)
{
	$o = '';

	if (! local_user()) {
		notice(DI::l10n()->t('Permission denied.') . EOL);
		return;
	}

	$_SESSION['return_path'] = DI::args()->getCommand();

	DI::page()['aside'] .= Widget::findPeople();
	DI::page()['aside'] .= Widget::follow();


	$r = GContact::suggestionQuery(local_user());

	if (! DBA::isResult($r)) {
		$o .= DI::l10n()->t('No suggestions available. If this is a new site, please try again in 24 hours.');
		return $o;
	}


	if (!empty($_GET['ignore'])) {
		// <form> can't take arguments in its "action" parameter
		// so add any arguments as hidden inputs
		$query = explode_querystring(DI::args()->getQueryString());
		$inputs = [];
		foreach ($query['args'] as $arg) {
			if (strpos($arg, 'confirm=') === false) {
				$arg_parts = explode('=', $arg);
				$inputs[] = ['name' => $arg_parts[0], 'value' => $arg_parts[1]];
			}
		}

		return Renderer::replaceMacros(Renderer::getMarkupTemplate('confirm.tpl'), [
			'$method' => 'post',
			'$message' => DI::l10n()->t('Do you really want to delete this suggestion?'),
			'$extra_inputs' => $inputs,
			'$confirm' => DI::l10n()->t('Yes'),
			'$confirm_url' => $query['base'],
			'$confirm_name' => 'confirm',
			'$cancel' => DI::l10n()->t('Cancel'),
		]);
	}

	$id = 0;
	$entries = [];

	foreach ($r as $rr) {
		$connlnk = DI::baseUrl() . '/follow/?url=' . (($rr['connect']) ? $rr['connect'] : $rr['url']);
		$ignlnk = DI::baseUrl() . '/suggest?ignore=' . $rr['id'];
		$photo_menu = [
			'profile' => [DI::l10n()->t("View Profile"), Contact::magicLink($rr["url"])],
			'follow' => [DI::l10n()->t("Connect/Follow"), $connlnk],
			'hide' => [DI::l10n()->t('Ignore/Hide'), $ignlnk]
		];

		$contact_details = Contact::getDetailsByURL($rr["url"], local_user(), $rr);

		$entry = [
			'url' => Contact::magicLink($rr['url']),
			'itemurl' => (($contact_details['addr'] != "") ? $contact_details['addr'] : $rr['url']),
			'img_hover' => $rr['url'],
			'name' => $contact_details['name'],
			'thumb' => ProxyUtils::proxifyUrl($contact_details['thumb'], false, ProxyUtils::SIZE_THUMB),
			'details'       => $contact_details['location'],
			'tags'          => $contact_details['keywords'],
			'about'         => $contact_details['about'],
			'account_type'  => Contact::getAccountType($contact_details),
			'ignlnk' => $ignlnk,
			'ignid' => $rr['id'],
			'conntxt' => DI::l10n()->t('Connect'),
			'connlnk' => $connlnk,
			'photo_menu' => $photo_menu,
			'ignore' => DI::l10n()->t('Ignore/Hide'),
			'network' => ContactSelector::networkToName($rr['network'], $rr['url']),
			'id' => ++$id,
		];
		$entries[] = $entry;
	}

	$tpl = Renderer::getMarkupTemplate('viewcontact_template.tpl');

	$o .= Renderer::replaceMacros($tpl,[
		'$title' => DI::l10n()->t('Friend Suggestions'),
		'$contacts' => $entries,
	]);

	return $o;
}
