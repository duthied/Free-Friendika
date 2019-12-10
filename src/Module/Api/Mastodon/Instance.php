<?php

namespace Friendica\Module\Api\Mastodon;

use Friendica\Core\Config;
use Friendica\Core\Protocol;
use Friendica\Core\System;
use Friendica\Database\DBA;
use Friendica\Model\User;
use Friendica\Module\Base\Api;
use Friendica\Module\Register;
use Friendica\Network\HTTPException;
use Friendica\Util\Network;

/**
 * @see https://docs.joinmastodon.org/api/rest/instances/
 */
class Instance extends Api
{
	public static function init(array $parameters = [])
	{
		parent::init($parameters);
	}

	/**
	 * @param array $parameters
	 * @throws HTTPException\InternalServerErrorException
	 */
	public static function rawContent(array $parameters = [])
	{
		$app = self::getApp();

                $register_policy = intval(Config::get('config', 'register_policy'));

		$return = [
			'uri' => $app->getBaseURL(),
			'title' => Config::get('config', 'sitename'),
			'short_description' => '', // Not supported
			'description' => Config::get('config', 'info'),
			'email' => Config::get('config', 'admin_email'),
			'version' => FRIENDICA_VERSION,
			'urls' => [], // Not supported
			'stats' => [],
			'thumbnail' => $app->getBaseURL() . (Config::get('system', 'shortcut_icon') ?? 'images/friendica-32.png'),
			'languages' => [Config::get('system', 'language')],
			'registrations' => ($register_policy != Register::CLOSED),
			'approval_required' => ($register_policy == Register::APPROVE),
			'contact_account' => [] // Currently unsupported
		];

		if (!empty(Config::get('system', 'nodeinfo'))) {
			$count = DBA::count('gserver', ["`network` in (?, ?) AND `last_contact` >= `last_failure`", Protocol::DFRN, Protocol::ACTIVITYPUB]);
			$return['stats'] = [
				'user_count' => intval(Config::get('nodeinfo', 'total_users')),
				'status_count' => Config::get('nodeinfo', 'local_posts') + Config::get('nodeinfo', 'local_comments'),
				'domain_count' => $count
			];
		}

		/// @ToDo will be done, once that we have an API function for that
		/*
		if (!empty(Config::get('config', 'admin_email'))) {
			$adminList = explode(',', str_replace(' ', '', Config::get('config', 'admin_email')));
			$administrator = User::getByEmail($adminList[0], ['nickname']);
			if (!empty($administrator)) {
				$adminContact = DBA::selectFirst('contact', [], ['nick' => $administrator['nickname'], 'self' => true]);
				$return['contact_account'] = Api::getAccountArray($adminContact);
			}
		}
		*/

		System::jsonExit($return);
	}
}
