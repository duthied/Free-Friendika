<?php
/**
 * @copyright Copyright (C) 2010-2021, the Friendica project
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
use Friendica\Content\Widget;
use Friendica\Core\Renderer;
use Friendica\Core\Search;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Model\Profile;
use Friendica\Module\Contact as ModuleContact;

/**
 * Controller for /match.
 *
 * It takes keywords from your profile and queries the directory server for
 * matching keywords from other profiles.
 *
 * @param App $a App
 *
 * @return string
 * @throws ImagickException
 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
 * @throws Exception
 */
function match_content(App $a)
{
	if (!local_user()) {
		return '';
	}

	DI::page()['aside'] .= Widget::findPeople();
	DI::page()['aside'] .= Widget::follow();

	$_SESSION['return_path'] = DI::args()->getCommand();

	$profile = Profile::getByUID(local_user());

	if (!DBA::isResult($profile)) {
		return '';
	}
	if (!$profile['pub_keywords'] && (!$profile['prv_keywords'])) {
		notice(DI::l10n()->t('No keywords to match. Please add keywords to your profile.'));
		return '';
	}

	$params = [];
	$tags = trim($profile['pub_keywords'] . ' ' . $profile['prv_keywords']);

	$params['s'] = $tags;
	$params['n'] = 100;

	if (strlen(DI::config()->get('system', 'directory'))) {
		$host = Search::getGlobalDirectory();
	} else {
		$host = DI::baseUrl();
	}

	$msearch_json = DI::httpRequest()->post($host . '/msearch', $params)->getBody();

	$msearch = json_decode($msearch_json);

	$start = $_GET['start'] ?? 0;
	$entries = [];
	$paginate = '';

	if (!empty($msearch->results)) {
		for ($i = $start;count($entries) < 10 && $i < $msearch->total; $i++) {
			$profile = $msearch->results[$i];

			// Already known contact
			if (!$profile || Contact::getIdForURL($profile->url, local_user())) {
				continue;
			}

			$contact = Contact::getByURLForUser($profile->url, local_user());
			if (!empty($contact)) {
				$entries[] = ModuleContact::getContactTemplateVars($contact);
			}
		}

		$data = [
			'class' => 'pager',
			'first' => [
				'url'   => 'match',
				'text'  => DI::l10n()->t('first'),
				'class' => 'previous' . ($start == 0 ? 'disabled' : '')
			],
			'next'  => [
				'url'   => 'match?start=' . $i,
				'text'  => DI::l10n()->t('next'),
				'class' =>  'next' . ($i >= $msearch->total ? ' disabled' : '')
			]
		];

		$tpl = Renderer::getMarkupTemplate('paginate.tpl');
		$paginate = Renderer::replaceMacros($tpl, ['pager' => $data]);
	}

	if (empty($entries)) {
		info(DI::l10n()->t('No matches'));
	}

	$tpl = Renderer::getMarkupTemplate('viewcontact_template.tpl');
	$o = Renderer::replaceMacros($tpl, [
		'$title'    => DI::l10n()->t('Profile Match'),
		'$contacts' => $entries,
		'$paginate' => $paginate
	]);

	return $o;
}
