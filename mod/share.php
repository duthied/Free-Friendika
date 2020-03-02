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
use Friendica\Database\DBA;
use Friendica\Model\Item;

function share_init(App $a) {
	$post_id = (($a->argc > 1) ? intval($a->argv[1]) : 0);

	if (!$post_id || !local_user()) {
		exit();
	}

	$fields = ['private', 'body', 'author-name', 'author-link', 'author-avatar',
		'guid', 'created', 'plink', 'title'];
	$item = Item::selectFirst($fields, ['id' => $post_id]);

	if (!DBA::isResult($item) || $item['private'] == Item::PRIVATE) {
		exit();
	}

	if (strpos($item['body'], "[/share]") !== false) {
		$pos = strpos($item['body'], "[share");
		$o = substr($item['body'], $pos);
	} else {
		$o = share_header($item['author-name'], $item['author-link'], $item['author-avatar'], $item['guid'], $item['created'], $item['plink']);

		if ($item['title']) {
			$o .= '[h3]'.$item['title'].'[/h3]'."\n";
		}

		$o .= $item['body'];
		$o .= "[/share]";
	}

	echo $o;
	exit();
}

/// @TODO Rewrite to handle over whole record array
function share_header($author, $profile, $avatar, $guid, $posted, $link) {
	$header = "[share author='" . str_replace(["'", "[", "]"], ["&#x27;", "&#x5B;", "&#x5D;"], $author).
		"' profile='" . str_replace(["'", "[", "]"], ["&#x27;", "&#x5B;", "&#x5D;"], $profile).
		"' avatar='" . str_replace(["'", "[", "]"], ["&#x27;", "&#x5B;", "&#x5D;"], $avatar);

	if ($guid) {
		$header .= "' guid='" . str_replace(["'", "[", "]"], ["&#x27;", "&#x5B;", "&#x5D;"], $guid);
	}

	if ($posted) {
		$header .= "' posted='" . str_replace(["'", "[", "]"], ["&#x27;", "&#x5B;", "&#x5D;"], $posted);
	}

	$header .= "' link='" . str_replace(["'", "[", "]"], ["&#x27;", "&#x5B;", "&#x5D;"], $link)."']";

	return $header;
}
