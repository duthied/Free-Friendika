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
use Friendica\Content\Text\BBCode;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Item;
use Friendica\Model\Term;
use Friendica\Util\Strings;

function tagrm_post(App $a)
{
	if (!local_user()) {
		DI::baseUrl()->redirect($_SESSION['photo_return']);
	}

	if (!empty($_POST['submit']) && ($_POST['submit'] === DI::l10n()->t('Cancel'))) {
		DI::baseUrl()->redirect($_SESSION['photo_return']);
	}

	$tags = [];
	foreach ($_POST['tag'] ?? [] as $tag) {
		$tags[] = hex2bin(Strings::escapeTags(trim($tag)));
	}

	$item_id = $_POST['item'] ?? 0;
	update_tags($item_id, $tags);
	info(DI::l10n()->t('Tag(s) removed') . EOL);

	DI::baseUrl()->redirect($_SESSION['photo_return']);
	// NOTREACHED
}

/**
 * Updates tags from an item
 *
 * @param $item_id
 * @param $tags array
 * @throws Exception
 */
function update_tags($item_id, $tags){
	if (empty($item_id) || empty($tags)){
		return;
	}

	$item = Item::selectFirst(['tag'], ['id' => $item_id, 'uid' => local_user()]);
	if (!DBA::isResult($item)) {
		return;
	}

	$old_tags = explode(',', $item['tag']);

	foreach ($tags as $new_tag) {
		foreach ($old_tags as $index => $old_tag) {
			if (strcmp($old_tag, $new_tag) == 0) {
				unset($old_tags[$index]);
				break;
			}
		}
	}

	$tag_str = implode(',', $old_tags);
	Term::insertFromTagFieldByItemId($item_id, $tag_str);
}

function tagrm_content(App $a)
{
	$o = '';

	if (!local_user()) {
		DI::baseUrl()->redirect($_SESSION['photo_return']);
		// NOTREACHED
	}

	if ($a->argc == 3) {
		update_tags($a->argv[1], [Strings::escapeTags(trim(hex2bin($a->argv[2])))]);
		DI::baseUrl()->redirect($_SESSION['photo_return']);
	}

	$item_id = (($a->argc > 1) ? intval($a->argv[1]) : 0);
	if (!$item_id) {
		DI::baseUrl()->redirect($_SESSION['photo_return']);
		// NOTREACHED
	}

	$item = Item::selectFirst(['tag'], ['id' => $item_id, 'uid' => local_user()]);
	if (!DBA::isResult($item)) {
		DI::baseUrl()->redirect($_SESSION['photo_return']);
	}

	$arr = explode(',', $item['tag']);


	if (empty($item['tag'])) {
		DI::baseUrl()->redirect($_SESSION['photo_return']);
	}

	$o .= '<h3>' . DI::l10n()->t('Remove Item Tag') . '</h3>';

	$o .= '<p id="tag-remove-desc">' . DI::l10n()->t('Select a tag to remove: ') . '</p>';

	$o .= '<form id="tagrm" action="tagrm" method="post" >';
	$o .= '<input type="hidden" name="item" value="' . $item_id . '" />';
	$o .= '<ul>';

	foreach ($arr as $x) {
		$o .= '<li><input type="checkbox" name="tag[]" value="' . bin2hex($x) . '" >' . BBCode::convert($x) . '</input></li>';
	}

	$o .= '</ul>';
	$o .= '<input id="tagrm-submit" type="submit" name="submit" value="' . DI::l10n()->t('Remove') .'" />';
	$o .= '<input id="tagrm-cancel" type="submit" name="submit" value="' . DI::l10n()->t('Cancel') .'" />';
	$o .= '</form>';

	return $o;
}
