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

namespace Friendica\Worker;

use Friendica\Core\Logger;
use Friendica\Core\Protocol;
use Friendica\Core\Worker;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Util\DateTimeFormat;

/**
 * Update public contacts
 */
class UpdatePublicContacts
{
	public static function execute()
	{
		$count = 0;
		$ids = [];
		$base_condition = ['network' => Protocol::FEDERATED, 'uid' => 0, 'self' => false];

		$existing = Worker::countWorkersByCommand('UpdateContact');
		Logger::info('Already existing jobs', ['existing' => $existing]);
		if ($existing > 100) {
			return;
		}

		$limit = 100 - $existing;

		if (!DI::config()->get('system', 'update_active_contacts')) {
			$part = 3;
			// Add every contact (mostly failed ones) that hadn't been updated for six months
			$condition = DBA::mergeConditions($base_condition,
				["`last-update` < ?", DateTimeFormat::utc('now - 6 month')]);
			$ids = self::getContactsToUpdate($condition, $ids, round($limit / $part));

			// Add every non failed contact that hadn't been updated for a month
			$condition = DBA::mergeConditions($base_condition,
				["NOT `failed` AND `last-update` < ?", DateTimeFormat::utc('now - 1 month')]);
			$ids = self::getContactsToUpdate($condition, $ids, round($limit / $part));
		} else {
			$part = 1;
		}

		// Add every contact our system interacted with and hadn't been updated for a week
		$condition = DBA::mergeConditions($base_condition, ["(`id` IN (SELECT `author-id` FROM `item`) OR
			`id` IN (SELECT `owner-id` FROM `item`) OR `id` IN (SELECT `causer-id` FROM `item`) OR
			`id` IN (SELECT `cid` FROM `post-tag`) OR `id` IN (SELECT `cid` FROM `user-contact`)) AND
			`last-update` < ?", DateTimeFormat::utc('now - 1 week')]);
		$ids = self::getContactsToUpdate($condition, $ids, round($limit / $part));

		foreach ($ids as $id) {
			Worker::add(PRIORITY_LOW, "UpdateContact", $id);
			++$count;
		}

		Logger::info('Initiated update for public contacts', ['count' => $count]);
	}

	/**
	 * Returns contact ids based on a given condition
	 *
	 * @param array $condition
	 * @param array $ids
	 * @return array contact ids
	 */
	private static function getContactsToUpdate(array $condition, array $ids = [], int $limit)
	{
		$contacts = DBA::select('contact', ['id'], $condition, ['limit' => $limit, 'order' => ['last-update']]);
		while ($contact = DBA::fetch($contacts)) {
			$ids[] = $contact['id'];
		}
		DBA::close($contacts);
		return $ids;
	}
}
