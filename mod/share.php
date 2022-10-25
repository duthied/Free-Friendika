<?php
/**
 * @copyright Copyright (C) 2010-2022, the Friendica project
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
use Friendica\Core\System;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Item;
use Friendica\Model\Post;

function share_init(App $a) {
	$post_id = ((DI::args()->getArgc() > 1) ? intval(DI::args()->getArgv()[1]) : 0);

	if (!$post_id || !DI::userSession()->getLocalUserId()) {
		System::exit();
	}

	$fields = ['private', 'body', 'uri'];
	$item = Post::selectFirst($fields, ['id' => $post_id]);

	if (!DBA::isResult($item) || $item['private'] == Item::PRIVATE) {
		System::exit();
	}

	$shared = Item::getShareArray($item);
	if (empty($shared['comment']) && (!empty($shared['message_id']) || !empty($shared['link']))) {
		$content = '[share]' . ($shared['message_id'] ?: $shared['link']) . '[/share]';
	} else {
		$content = '[share]' . $item['uri'] . '[/share]';
	}
	System::httpExit($content);
}
