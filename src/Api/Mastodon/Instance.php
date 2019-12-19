<?php

namespace Friendica\Api\Mastodon;

use Friendica\App;
use Friendica\Api\Mastodon\Account;
use Friendica\Api\Mastodon\Stats;
use Friendica\Core\Config;
use Friendica\Database\DBA;
use Friendica\Model\APContact;
use Friendica\Model\User;
use Friendica\Module\Register;

/**
 * Class Instance
 *
 * @see https://docs.joinmastodon.org/api/entities/#instance
 */
class Instance
{
	/** @var string (URL) */
	var $uri;
	/** @var string */
	var $title;
	/** @var string */
	var $description;
	/** @var string */
	var $email;
	/** @var string */
	var $version;
	/** @var array */
	var $urls;
	/** @var Stats */
	var $stats;
	/** @var string */
	var $thumbnail;
	/** @var array */
	var $languages;
	/** @var int */
	var $max_toot_chars;
	/** @var bool */
	var $registrations;
	/** @var bool */
	var $approval_required;
	/** @var Account|null */
	var $contact_account;

	/**
	 * Creates an instance record
	 *
	 * @param App $app
	 *
	 * @return Instance
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function get(App $app) {
		$register_policy = intval(Config::get('config', 'register_policy'));

		$instance = new Instance();
		$instance->uri = $app->getBaseURL();
		$instance->title = Config::get('config', 'sitename');
		$instance->description = Config::get('config', 'info');
		$instance->email = Config::get('config', 'admin_email');
		$instance->version = FRIENDICA_VERSION;
		$instance->urls = []; // Not supported
		$instance->stats = Stats::get();
		$instance->thumbnail = $app->getBaseURL() . (Config::get('system', 'shortcut_icon') ?? 'images/friendica-32.png');
		$instance->languages = [Config::get('system', 'language')];
		$instance->max_toot_chars = (int)Config::get('config', 'api_import_size', Config::get('config', 'max_import_size'));
		$instance->registrations = ($register_policy != Register::CLOSED);
		$instance->approval_required = ($register_policy == Register::APPROVE);
		$instance->contact_account = [];

		if (!empty(Config::get('config', 'admin_email'))) {
			$adminList = explode(',', str_replace(' ', '', Config::get('config', 'admin_email')));
			$administrator = User::getByEmail($adminList[0], ['nickname']);
			if (!empty($administrator)) {
				$adminContact = DBA::selectFirst('contact', [], ['nick' => $administrator['nickname'], 'self' => true]);
				$apcontact = APContact::getByURL($adminContact['url'], false);
				$instance->contact_account = Account::createFromContact($adminContact, $apcontact);
			}
		}

		return $instance;
	}
}
