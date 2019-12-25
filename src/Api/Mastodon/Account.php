<?php

namespace Friendica\Api\Mastodon;

use Friendica\App\BaseURL;
use Friendica\Content\Text\BBCode;
use Friendica\Database\DBA;
use Friendica\Model\Contact;
use Friendica\Util\DateTimeFormat;

/**
 * Class Account
 *
 * @see https://docs.joinmastodon.org/entities/account
 */
class Account
{
	/** @var string */
	var $id;
	/** @var string */
	var $username;
	/** @var string */
	var $acct;
	/** @var string */
	var $display_name;
	/** @var bool */
	var $locked;
	/** @var string (Datetime) */
	var $created_at;
	/** @var int */
	var $followers_count;
	/** @var int */
	var $following_count;
	/** @var int */
	var $statuses_count;
	/** @var string */
	var $note;
	/** @var string (URL)*/
	var $url;
	/** @var string (URL) */
	var $avatar;
	/** @var string (URL) */
	var $avatar_static;
	/** @var string (URL) */
	var $header;
	/** @var string (URL) */
	var $header_static;
	/** @var Emoji[] */
	var $emojis;
	/** @var Account|null */
	var $moved = null;
	/** @var Field[]|null */
	var $fields = null;
	/** @var bool|null */
	var $bot = null;
	/** @var bool */
	var $group;
	/** @var bool */
	var $discoverable;
	/** @var string|null (Datetime) */
	var $last_status_at = null;

	/**
	 * Creates an account record from a public contact record. Expects all contact table fields to be set.
	 *
	 * @param BaseURL $baseUrl
	 * @param array   $publicContact Full contact table record with uid = 0
	 * @param array   $apcontact     Optional full apcontact table record
	 * @return Account
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function create(BaseURL $baseUrl, array $publicContact, array $apcontact = [])
	{
		$account = new Account();
		$account->id              = $publicContact['id'];
		$account->username        = $publicContact['nick'];
		$account->acct            =
			strpos($publicContact['url'], $baseUrl->get() . '/') === 0 ?
			$publicContact['nick'] :
			$publicContact['addr'];
		$account->display_name    = $publicContact['name'];
		$account->locked          = !empty($apcontact['manually-approve']);
		$account->created_at      = DateTimeFormat::utc($publicContact['created'], DateTimeFormat::ATOM);
		$account->followers_count = $apcontact['followers_count'] ?? 0;
		$account->following_count = $apcontact['following_count'] ?? 0;
		$account->statuses_count  = $apcontact['statuses_count'] ?? 0;
		$account->note            = BBCode::convert($publicContact['about'], false);
		$account->url             = $publicContact['url'];
		$account->avatar          = $publicContact['avatar'];
		$account->avatar_static   = $publicContact['avatar'];
		// No header picture in Friendica
		$account->header          = '';
		$account->header_static   = '';
		// No custom emojis per account in Friendica
		$account->emojis          = [];
		// No metadata fields in Friendica
		$account->fields          = [];
		$account->bot             = ($publicContact['contact-type'] == Contact::TYPE_NEWS);
		$account->group           = ($publicContact['contact-type'] == Contact::TYPE_COMMUNITY);
		$account->discoverable    = !$publicContact['unsearchable'];
		$account->last_status_at  = !empty($publicContact['last-item']) ? DateTimeFormat::utc($publicContact['last-item'], DateTimeFormat::ATOM) : null;

		return $account;
	}
}
