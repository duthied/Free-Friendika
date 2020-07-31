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

namespace Friendica\Module;

use Friendica\BaseModule;
use Friendica\Content\Pager;
use Friendica\Core\Renderer;
use Friendica\DI;
use Friendica\Model;
use Friendica\Network\HTTPException;

/**
 * This module shows all public friends of the selected contact
 */
class AllFriends extends BaseModule
{
	public static function content(array $parameters = [])
	{
		$app = DI::app();

		if (!local_user()) {
			throw new HTTPException\ForbiddenException();
		}

		$cid = 0;

		// @TODO: Replace with parameter from router
		if ($app->argc > 1) {
			$cid = intval($app->argv[1]);
		}

		if (!$cid) {
			throw new HTTPException\BadRequestException(DI::l10n()->t('Invalid contact.'));
		}

		$uid = $app->user['uid'];

		$contact = Model\Contact::getContactForUser($cid, local_user(), ['name', 'url', 'photo', 'uid', 'id']);

		if (empty($contact)) {
			throw new HTTPException\BadRequestException(DI::l10n()->t('Invalid contact.'));
		}

		DI::page()['aside'] = "";
		Model\Profile::load($app, "", Model\Contact::getByURL($contact["url"], false));

		$total = Model\GContact::countAllFriends(local_user(), $cid);

		$pager = new Pager(DI::l10n(), DI::args()->getQueryString());

		$friends = Model\GContact::allFriends(local_user(), $cid, $pager->getStart(), $pager->getItemsPerPage());
		if (empty($friends)) {
			return DI::l10n()->t('No friends to display.');
		}

		$id = 0;

		$entries = [];
		foreach ($friends as $friend) {
			$contact = Model\Contact::getByURLForUser($friend['url'], local_user());
			if (!empty($contact)) {
				$entries[] = Contact::getContactTemplateVars($contact, ++$id);
			}
		}

		$tab_str = Contact::getTabsHTML($app, $contact, 4);

		$tpl = Renderer::getMarkupTemplate('viewcontact_template.tpl');
		return Renderer::replaceMacros($tpl, [
			'$tab_str'  => $tab_str,
			'$contacts' => $entries,
			'$paginate' => $pager->renderFull($total),
		]);
	}
}
