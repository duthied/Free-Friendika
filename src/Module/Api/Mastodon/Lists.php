<?php
/**
 * @copyright Copyright (C) 2010-2023, the Friendica project
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
use Friendica\Model\Circle;

/**
 * @see https://docs.joinmastodon.org/methods/timelines/lists/
 */
class Lists extends BaseApi
{
	protected function delete(array $request = [])
	{
		$this->checkAllowedScope(self::SCOPE_WRITE);
		$uid = self::getCurrentUserID();

		if (empty($this->parameters['id'])) {
			$this->logAndJsonError(422, $this->errorFactory->UnprocessableEntity());
		}

		if (!Circle::exists($this->parameters['id'], $uid)) {
			$this->logAndJsonError(404, $this->errorFactory->RecordNotFound());
		}

		if (!Circle::remove($this->parameters['id'])) {
			$this->logAndJsonError(500, $this->errorFactory->InternalError());
		}

		$this->jsonExit([]);
	}

	protected function post(array $request = [])
	{
		$this->checkAllowedScope(self::SCOPE_WRITE);
		$uid = self::getCurrentUserID();

		$request = $this->getRequest([
			'title' => '',
		], $request);

		if (empty($request['title'])) {
			$this->logAndJsonError(422, $this->errorFactory->UnprocessableEntity());
		}

		Circle::create($uid, $request['title']);

		$id = Circle::getIdByName($uid, $request['title']);
		if (!$id) {
			$this->logAndJsonError(500, $this->errorFactory->InternalError());
		}

		$this->jsonExit(DI::mstdnList()->createFromCircleId($id));
	}

	public function put(array $request = [])
	{
		$request = $this->getRequest([
			'title'          => '', // The title of the list to be updated.
			'replies_policy' => '', // One of: "followed", "list", or "none".
		], $request);

		if (empty($request['title']) || empty($this->parameters['id'])) {
			$this->logAndJsonError(422, $this->errorFactory->UnprocessableEntity());
		}

		Circle::update($this->parameters['id'], $request['title']);
	}

	/**
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	protected function rawContent(array $request = [])
	{
		$this->checkAllowedScope(self::SCOPE_READ);
		$uid = self::getCurrentUserID();

		if (empty($this->parameters['id'])) {
			$lists = [];

			foreach (Circle::getByUserId($uid) as $circle) {
				$lists[] = DI::mstdnList()->createFromCircleId($circle['id']);
			}
		} else {
			$id = $this->parameters['id'];

			if (!Circle::exists($id, $uid)) {
				$this->logAndJsonError(404, $this->errorFactory->RecordNotFound());
			}
			$lists = DI::mstdnList()->createFromCircleId($id);
		}

		$this->jsonExit($lists);
	}
}
