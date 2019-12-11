<?php

namespace Friendica\Module\Api\Mastodon;

use Friendica\Api\Mastodon;
use Friendica\App\BaseURL;
use Friendica\Core\System;
use Friendica\Database\DBA;
use Friendica\Model\Contact;
use Friendica\Module\Base\Api;
use Friendica\Network\HTTPException;

/**
 * @see https://docs.joinmastodon.org/api/rest/follow-requests/
 */
class FollowRequests extends Api
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
	 * @throws HTTPException\InternalServerErrorException
	 * @see https://docs.joinmastodon.org/api/rest/follow-requests/#get-api-v1-follow-requests
	 */
	public static function rawContent(array $parameters = [])
	{
		$since_id = $_GET['since_id'] ?? null;
		$max_id = $_GET['max_id'] ?? null;
		$limit = intval($_GET['limit'] ?? 40);

		if (isset($since_id) && isset($max_id)) {
			$condition = ['`uid` = ? AND NOT `ignore` AND `id` > ? AND `id` < ?', self::$current_user_id, $since_id, $max_id];
		} elseif (isset($since_id)) {
			$condition = ['`uid` = ? AND NOT `ignore` AND `id` > ?', self::$current_user_id, $since_id];
		} elseif (isset($max_id)) {
			$condition = ['`uid` = ? AND NOT `ignore` AND `id` < ?', self::$current_user_id, $max_id];
		} else {
			$condition = ['`uid` = ? AND NOT `ignore`', self::$current_user_id];
		}

		$count = DBA::count('intro', $condition);

		$intros = DBA::selectToArray(
			'intro',
			[],
			$condition,
			['order' => ['id' => 'DESC'], 'limit' => $limit]
		);

		$return = [];
		foreach ($intros as $intro) {
			$account = Mastodon\Account::createFromContact(Contact::getById($intro['contact-id']));

			// Not ideal, the same "account" can have multiple ids depending on the context
			$account->id = $intro['id'];

			$return[] = $account;
		}

		$base_query = [];
		if (isset($_GET['limit'])) {
			$base_query['limit'] = $limit;
		}

		/** @var BaseURL $BaseURL */
		$BaseURL = self::getClass(BaseURL::class);

		$links = [];
		if ($count > $limit) {
			$links[] = '<' . $BaseURL->get() . '/api/v1/follow_requests?' . http_build_query($base_query + ['max_id' => $intros[count($intros) - 1]['id']]) . '>; rel="next"';
		}
		$links[] = '<' . $BaseURL->get() . '/api/v1/follow_requests?' . http_build_query($base_query + ['since_id' => $intros[0]['id']]) . '>; rel="prev"';

		header('Link: ' . implode(', ', $links));

		System::jsonExit($return);
	}
}
