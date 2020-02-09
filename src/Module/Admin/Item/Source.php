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

namespace Friendica\Module\Admin\Item;

use Friendica\Core\Renderer;
use Friendica\DI;
use Friendica\Model;
use Friendica\Module\BaseAdmin;

class Source extends BaseAdmin

{
	public static function content(array $parameters = [])
	{
		parent::content($parameters);

		$a = DI::app();

		$guid = null;
		// @TODO: Replace with parameter from router
		if (!empty($a->argv[3])) {
			$guid = $a->argv[3];
		}

		$guid = $_REQUEST['guid'] ?? $guid;

		$source = '';
		$item_uri = '';
		$item_id = '';
		$terms = [];
		if (!empty($guid)) {
			$item = Model\Item::selectFirst(['id', 'guid', 'uri'], ['guid' => $guid]);

			$conversation = Model\Conversation::getByItemUri($item['uri']);

			$item_id = $item['id'];
			$item_uri = $item['uri'];
			$source = $conversation['source'];
			$terms = Model\Term::tagArrayFromItemId($item['id'], [Model\Term::HASHTAG, Model\Term::MENTION, Model\Term::IMPLICIT_MENTION]);
		}

		$tpl = Renderer::getMarkupTemplate('admin/item/source.tpl');
		$o = Renderer::replaceMacros($tpl, [
			'$guid'          => ['guid', DI::l10n()->t('Item Guid'), $guid, ''],
			'$source'        => $source,
			'$item_uri'      => $item_uri,
			'$item_id'       => $item_id,
			'$terms'         => $terms,
		]);

		return $o;
	}
}
