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

namespace Friendica\Model;

use Friendica\Core\Logger;
use Friendica\Core\Protocol;
use Friendica\Database\DBA;
use Friendica\Util\DateTimeFormat;

class Conversation
{
	/*
	 * These constants represent the parcel format used to transport a conversation independently of the message protocol.
	 * It currently is stored in the "protocol" field for legacy reasons.
	 */
	const PARCEL_ACTIVITYPUB        = 0;
	const PARCEL_DFRN               = 1;
	const PARCEL_DIASPORA           = 2;
	const PARCEL_SALMON             = 3;
	const PARCEL_FEED               = 4; // Deprecated
	const PARCEL_SPLIT_CONVERSATION = 6;
	const PARCEL_TWITTER            = 67;
	const PARCEL_UNKNOWN            = 255;

	/**
	 * Unknown message direction
	 */
	const UNKNOWN = 0;
	/**
	 * The message had been pushed to this sytem
	 */
	const PUSH    = 1;
	/**
	 * The message had been fetched by our system
	 */
	const PULL    = 2;

	public static function getByItemUri($item_uri)
	{
		return DBA::selectFirst('conversation', [], ['item-uri' => $item_uri]);
	}

	/**
	 * Store the conversation data
	 *
	 * @param array $arr Item array with conversation data
	 * @return array Item array with removed conversation data
	 * @throws \Exception
	 */
	public static function insert(array $arr)
	{
		if (in_array(($arr['network'] ?? '') ?: Protocol::PHANTOM,
			[Protocol::ACTIVITYPUB, Protocol::DFRN, Protocol::DIASPORA, Protocol::OSTATUS, Protocol::TWITTER]) && !empty($arr['uri'])) {
			$conversation = ['item-uri' => $arr['uri'], 'received' => DateTimeFormat::utcNow()];

			if (isset($arr['parent-uri']) && ($arr['parent-uri'] != $arr['uri'])) {
				$conversation['reply-to-uri'] = $arr['parent-uri'];
			}

			if (isset($arr['thr-parent']) && ($arr['thr-parent'] != $arr['uri'])) {
				$conversation['reply-to-uri'] = $arr['thr-parent'];
			}

			if (isset($arr['conversation-uri'])) {
				$conversation['conversation-uri'] = $arr['conversation-uri'];
			}

			if (isset($arr['conversation-href'])) {
				$conversation['conversation-href'] = $arr['conversation-href'];
			}

			if (isset($arr['protocol'])) {
				$conversation['protocol'] = $arr['protocol'];
			}

			if (isset($arr['direction'])) {
				$conversation['direction'] = $arr['direction'];
			}

			if (isset($arr['source'])) {
				$conversation['source'] = $arr['source'];
			}

			$fields = ['item-uri', 'reply-to-uri', 'conversation-uri', 'conversation-href', 'protocol', 'source'];
			$old_conv = DBA::selectFirst('conversation', $fields, ['item-uri' => $conversation['item-uri']]);
			if (DBA::isResult($old_conv)) {
				// Don't update when only the source has changed.
				// Only do this when there had been no source before.
				if ($old_conv['source'] != '') {
					unset($old_conv['source']);
				}
				// Update structure data all the time but the source only when its from a better protocol.
				if (
					empty($conversation['source'])
					|| (
						!empty($old_conv['source'])
						&& ($old_conv['protocol'] < (($conversation['protocol'] ?? '') ?: self::PARCEL_UNKNOWN))
					)
				) {
					unset($conversation['protocol']);
					unset($conversation['source']);
				}
				if (!DBA::update('conversation', $conversation, ['item-uri' => $conversation['item-uri']], $old_conv)) {
					Logger::log('Conversation: update for ' . $conversation['item-uri'] . ' from ' . $old_conv['protocol'] . ' to ' . $conversation['protocol'] . ' failed',
						Logger::DEBUG);
				}
			} else {
				if (!DBA::insert('conversation', $conversation, true)) {
					Logger::log('Conversation: insert for ' . $conversation['item-uri'] . ' (protocol ' . $conversation['protocol'] . ') failed',
						Logger::DEBUG);
				}
			}
		}

		unset($arr['conversation-uri']);
		unset($arr['conversation-href']);
		unset($arr['protocol']);
		unset($arr['source']);
		unset($arr['direction']);

		return $arr;
	}
}
