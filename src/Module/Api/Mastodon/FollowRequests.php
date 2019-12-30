<?php

namespace Friendica\Module\Api\Mastodon;

use Friendica\Api\Mastodon;
use Friendica\Core\System;
use Friendica\Database\DBA;
use Friendica\Model\APContact;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Model\Introduction;
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
	 * @throws HTTPException\BadRequestException
	 * @throws HTTPException\ForbiddenException
	 * @throws HTTPException\NotFoundException
	 * @throws HTTPException\UnauthorizedException
	 * @see https://docs.joinmastodon.org/methods/accounts/follow_requests#accept-follow
	 * @see https://docs.joinmastodon.org/methods/accounts/follow_requests#reject-follow
	 */
	public static function post(array $parameters = [])
	{
		parent::post($parameters);

		$Intro = DI::intro()->fetch(['id' => $parameters['id'], 'uid' => self::$current_user_id]);

		$contactId = $Intro->{'contact-id'};

		$relationship = new Mastodon\Relationship();
		$relationship->id = $contactId;

		switch ($parameters['action']) {
			case 'authorize':
				$Intro->confirm();
				$relationship = Mastodon\Relationship::createFromContact(Contact::getById($contactId));
				break;
			case 'ignore':
				$Intro->ignore();
				break;
			case 'reject':
				$Intro->discard();
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
			$cdata = Contact::getPublicAndUserContacID($intro['contact-id'], $intro['uid']);
			if (empty($cdata['public'])) {
				continue;
			}

			$publicContact = Contact::getById($cdata['public']);
			$userContact = Contact::getById($cdata['user']);
			$apcontact = APContact::getByURL($publicContact['url'], false);
			$account = Mastodon\Account::create($baseUrl, $publicContact, $apcontact, $userContact);

			// Not ideal, the same "account" can have multiple ids depending on the context
			$account->id = $intro['id'];

			$return[] = $account;
		}

		$base_query = [];
		if (isset($_GET['limit'])) {
			$base_query['limit'] = $limit;
		}

		$links = [];
		if ($count > $limit) {
			$links[] = '<' . $baseUrl->get() . '/api/v1/follow_requests?' . http_build_query($base_query + ['max_id' => $intros[count($intros) - 1]['id']]) . '>; rel="next"';
		}
		$links[] = '<' . $baseUrl->get() . '/api/v1/follow_requests?' . http_build_query($base_query + ['since_id' => $intros[0]['id']]) . '>; rel="prev"';

		header('Link: ' . implode(', ', $links));

		System::jsonExit($return);
	}
}
