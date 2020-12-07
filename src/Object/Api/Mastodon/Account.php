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

namespace Friendica\Object\Api\Mastodon;

use Friendica\App\BaseURL;
use Friendica\BaseEntity;
use Friendica\Collection\Api\Mastodon\Fields;
use Friendica\Content\Text\BBCode;
use Friendica\Database\DBA;
use Friendica\Model\Contact;
use Friendica\Util\DateTimeFormat;

/**
 * Class Account
 *
 * @see https://docs.joinmastodon.org/entities/account
 */
class Account extends BaseEntity
{
	/** @var string */
	protected $id;
	/** @var string */
	protected $username;
	/** @var string */
	protected $acct;
	/** @var string */
	protected $display_name;
	/** @var bool */
	protected $locked;
	/** @var string (Datetime) */
	protected $created_at;
	/** @var int */
	protected $followers_count;
	/** @var int */
	protected $following_count;
	/** @var int */
	protected $statuses_count;
	/** @var string */
	protected $note;
	/** @var string (URL)*/
	protected $url;
	/** @var string (URL) */
	protected $avatar;
	/** @var string (URL) */
	protected $avatar_static;
	/** @var string (URL) */
	protected $header;
	/** @var string (URL) */
	protected $header_static;
	/** @var Emoji[] */
	protected $emojis;
	/** @var Account|null */
	protected $moved = null;
	/** @var Field[]|null */
	protected $fields = null;
	/** @var bool|null */
	protected $bot = null;
	/** @var bool */
	protected $group;
	/** @var bool */
	protected $discoverable;
	/** @var string|null (Datetime) */
	protected $last_status_at = null;

	/**
	 * Creates an account record from a public contact record. Expects all contact table fields to be set.
	 *
	 * @param BaseURL $baseUrl
	 * @param array   $publicContact Full contact table record with uid = 0
	 * @param array   $apcontact     Optional full apcontact table record
	 * @param array   $userContact   Optional full contact table record with uid != 0
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public function __construct(BaseURL $baseUrl, array $publicContact, Fields $fields, array $apcontact = [], array $userContact = [])
	{
		$this->id              = $publicContact['id'];
		$this->username        = $publicContact['nick'];
		$this->acct            =
			strpos($publicContact['url'], $baseUrl->get() . '/') === 0 ?
				$publicContact['nick'] :
				$publicContact['addr'];
		$this->display_name    = $publicContact['name'];
		$this->locked          = !empty($apcontact['manually-approve']);
		$this->created_at      = DateTimeFormat::utc($publicContact['created'], DateTimeFormat::ATOM);
		$this->followers_count = $apcontact['followers_count'] ?? 0;
		$this->following_count = $apcontact['following_count'] ?? 0;
		$this->statuses_count  = $apcontact['statuses_count'] ?? 0;
		$this->note            = BBCode::convert($publicContact['about'], false);
		$this->url             = $publicContact['url'];
		$this->avatar          = $userContact['avatar'] ?? $publicContact['avatar'];
		$this->avatar_static   = $userContact['avatar'] ?? $publicContact['avatar'];
		// No header picture in Friendica
		$this->header          = '';
		$this->header_static   = '';
		// No custom emojis per account in Friendica
		$this->emojis          = [];
		// No metadata fields in Friendica
		$this->fields          = $fields->getArrayCopy();
		$this->bot             = ($publicContact['contact-type'] == Contact::TYPE_NEWS);
		$this->group           = ($publicContact['contact-type'] == Contact::TYPE_COMMUNITY);
		$this->discoverable    = !$publicContact['unsearchable'];

		$publicContactLastItem = $publicContact['last-item'] ?: DBA::NULL_DATETIME;
		$userContactLastItem = $userContact['last-item'] ?? DBA::NULL_DATETIME;

		$lastItem = $userContactLastItem > $publicContactLastItem ? $userContactLastItem : $publicContactLastItem;
		$this->last_status_at  = $lastItem != DBA::NULL_DATETIME ? DateTimeFormat::utc($lastItem, DateTimeFormat::ATOM) : null;
	}
}
