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
		notice(DI::l10n()->t('Permission denied.'));
		return;
	}

	$_SESSION['return_path'] = DI::args()->getCommand();

	DI::page()['aside'] .= Widget::findPeople();
	DI::page()['aside'] .= Widget::follow();


	$contacts = Contact::getSuggestions(local_user());
	if (!DBA::isResult($contacts)) {
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

	foreach ($contacts as $contact) {
		$entry = [
			'url'          => Contact::magicLink($contact['url']),
			'itemurl'      => $contact['addr'] ?: $contact['url'],
			'name'         => $contact['name'],
			'thumb'        => Contact::getThumb($contact),
			'img_hover'    => $contact['url'],
			'details'      => $contact['location'],
			'tags'         => $contact['keywords'],
			'about'        => $contact['about'],
			'account_type' => Contact::getAccountType($contact),
			'network'      => ContactSelector::networkToName($contact['network'], $contact['url']),
			'photo_menu'   => Contact::photoMenu($contact),
			'id'           => ++$id,
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
