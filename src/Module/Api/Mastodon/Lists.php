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

namespace Friendica\Module\Api\Mastodon;

use Friendica\Core\System;
use Friendica\DI;
use Friendica\Module\BaseApi;
use Friendica\Model\Group;

/**
 * @see https://docs.joinmastodon.org/methods/timelines/lists/
 */
class Lists extends BaseApi
{
	public static function delete()
	{
		self::checkAllowedScope(self::SCOPE_WRITE);
		$uid = self::getCurrentUserID();

		if (empty(static::$parameters['id'])) {
			DI::mstdnError()->UnprocessableEntity();
		}

		if (!Group::exists(static::$parameters['id'], $uid)) {
			DI::mstdnError()->RecordNotFound();
		}

		if (!Group::remove(static::$parameters['id'])) {
			DI::mstdnError()->InternalError();
		}

		System::jsonExit([]);
	}

	public static function post()
	{
		self::checkAllowedScope(self::SCOPE_WRITE);
		$uid = self::getCurrentUserID();

		$request = self::getRequest([
			'title' => '',
		]);

		if (empty($request['title'])) {
			DI::mstdnError()->UnprocessableEntity();
		}

		Group::create($uid, $request['title']);

		$id = Group::getIdByName($uid, $request['title']);
		if (!$id) {
			DI::mstdnError()->InternalError();
		}

		System::jsonExit(DI::mstdnList()->createFromGroupId($id));
	}

	public static function put()
	{
		$request = self::getRequest([
			'title'          => '', // The title of the list to be updated.
			'replies_policy' => '', // One of: "followed", "list", or "none".
		]);

		if (empty($request['title']) || empty(static::$parameters['id'])) {
			DI::mstdnError()->UnprocessableEntity();
		}

		Group::update(static::$parameters['id'], $request['title']);
	}

	/**
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function rawContent()
	{
		self::checkAllowedScope(self::SCOPE_READ);
		$uid = self::getCurrentUserID();

		if (empty(static::$parameters['id'])) {
			$lists = [];

			$groups = Group::getByUserId($uid);

			foreach ($groups as $group) {
				$lists[] = DI::mstdnList()->createFromGroupId($group['id']);
			}
		} else {
			$id = static::$parameters['id'];

			if (!Group::exists($id, $uid)) {
				DI::mstdnError()->RecordNotFound();
			}
			$lists = DI::mstdnList()->createFromGroupId($id);
		}

		System::jsonExit($lists);
	}
}
