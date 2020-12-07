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

namespace Friendica\Module\Filer;

use Friendica\BaseModule;
use Friendica\Core\Renderer;
use Friendica\DI;
use Friendica\Model;
use Friendica\Util\XML;

/**
 * Shows a dialog for adding tags to a file
 */
class SaveTag extends BaseModule
{
	public static function init(array $parameters = [])
	{
		if (!local_user()) {
			info(DI::l10n()->t('You must be logged in to use this module'));
			DI::baseUrl()->redirect();
		}
	}

	public static function rawContent(array $parameters = [])
	{
		$a = DI::app();
		$logger = DI::logger();

		$term = XML::unescape(trim($_GET['term'] ?? ''));
		// @TODO: Replace with parameter from router
		$item_id = (($a->argc > 1) ? intval($a->argv[1]) : 0);

		$logger->info('filer', ['tag' => $term, 'item' => $item_id]);

		if ($item_id && strlen($term)) {
			// file item
			Model\FileTag::saveFile(local_user(), $item_id, $term);
			info(DI::l10n()->t('Filetag %s saved to item', $term));
		}

		// return filer dialog
		$filetags = DI::pConfig()->get(local_user(), 'system', 'filetags', '');
		$filetags = Model\FileTag::fileToArray($filetags);

		$tpl = Renderer::getMarkupTemplate("filer_dialog.tpl");
		echo Renderer::replaceMacros($tpl, [
			'$field' => ['term', DI::l10n()->t("Save to Folder:"), '', '', $filetags, DI::l10n()->t('- select -')],
			'$submit' => DI::l10n()->t('Save'),
		]);

		exit;
	}
}
