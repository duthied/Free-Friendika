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

namespace Friendica\Module\Api\Mastodon;

use Friendica\Core\System;
use Friendica\DI;
use Friendica\Module\BaseApi;
use Friendica\Network\HTTPException;

/**
 * @see https://docs.joinmastodon.org/methods/accounts/follow_requests
 */
class FollowRequests extends BaseApi
{
	public static function init(array $parameters = [])
	{
		parent::init($parameters);

		if (!self::login()) {
			throw new HTTPException\UnauthorizedException();
		}
	}

	/**
	 * @param array $parameters
	 * @throws HTTPException\BadRequestException
	 * @throws HTTPException\ForbiddenException
	 * @throws HTTPException\InternalServerErrorException
	 * @throws HTTPException\NotFoundException
	 * @throws HTTPException\UnauthorizedException
	 * @throws \ImagickException
	 *
	 * @see https://docs.joinmastodon.org/methods/accounts/follow_requests#accept-follow
	 * @see https://docs.joinmastodon.org/methods/accounts/follow_requests#reject-follow
	 */
	public static function post(array $parameters = [])
	{
		parent::post($parameters);

		$introduction = DI::intro()->selectFirst(['id' => $parameters['id'], 'uid' => self::$current_user_id]);

		$contactId = $introduction->{'contact-id'};

		switch ($parameters['action']) {
			case 'authorize':
				$introduction->confirm();

				$relationship = DI::mstdnRelationship()->createFromContactId($contactId);
				break;
			case 'ignore':
				$introduction->ignore();

				$relationship = DI::mstdnRelationship()->createDefaultFromContactId($contactId);
				break;
			case 'reject':
				$introduction->discard();

				$relationship = DI::mstdnRelationship()->createDefaultFromContactId($contactId);
				break;
			default:
				throw new HTTPException\BadRequestException('Unexpected action parameter, expecting "authorize", "ignore" or "reject"');
		}

		System::jsonExit($relationship);
	}

	/**
	 * @param array $parameters
	 * @throws HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 * @see https://docs.joinmastodon.org/methods/accounts/follow_requests#pending-follows
	 */
	public static function rawContent(array $parameters = [])
	{
		$since_id = $_GET['since_id'] ?? null;
		$max_id = $_GET['max_id'] ?? null;
		$limit = intval($_GET['limit'] ?? 40);

		$baseUrl = DI::baseUrl();

		$introductions = DI::intro()->selectByBoundaries(
			['`uid` = ? AND NOT `ignore`', self::$current_user_id],
			['order' => ['id' => 'DESC']],
			$since_id,
			$max_id,
			$limit
		);

		$return = [];

		foreach ($introductions as $key => $introduction) {
			try {
				$return[] = DI::mstdnFollowRequest()->createFromIntroduction($introduction);
			} catch (HTTPException\InternalServerErrorException $exception) {
				DI::intro()->delete($introduction);
				unset($introductions[$key]);
			}
		}

		$base_query = [];
		if (isset($_GET['limit'])) {
			$base_query['limit'] = $limit;
		}

		$links = [];
		if ($introductions->getTotalCount() > $limit) {
			$links[] = '<' . $baseUrl->get() . '/api/v1/follow_requests?' . http_build_query($base_query + ['max_id' => $introductions[count($introductions) - 1]->id]) . '>; rel="next"';
		}

		if (count($introductions)) {
			$links[] = '<' . $baseUrl->get() . '/api/v1/follow_requests?' . http_build_query($base_query + ['since_id' => $introductions[0]->id]) . '>; rel="prev"';
		}

		header('Link: ' . implode(', ', $links));

		System::jsonExit($return);
	}
}
