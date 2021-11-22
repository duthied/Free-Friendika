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

namespace Friendica\Object\Api\Twitter;

use Friendica\BaseDataTransferObject;
use Friendica\Content\ContactSelector;
use Friendica\Content\Text\BBCode;
use Friendica\Content\Text\HTML;
use Friendica\Model\Item;
use Friendica\Util\DateTimeFormat;

/**
 * Class Status
 *
 * @see https://docs.joinmastodon.org/entities/status
 */
class Status extends BaseDataTransferObject
{
	/** @var int */
	protected $id;
	/** @var string */
	protected $id_str;
	/** @var string (Datetime) */
	protected $created_at;
	/** @var int|null */
	protected $in_reply_to_status_id = null;
	/** @var string|null */
	protected $in_reply_to_status_id_str = null;
	/** @var int|null */
	protected $in_reply_to_user_id = null;
	/** @var string|null */
	protected $in_reply_to_user_id_str = null;
	/** @var string|null */
	protected $in_reply_to_screen_name = null;
	/** @var User */
	protected $user;
	/** @var User */
	protected $friendica_author;
	/** @var User */
	protected $friendica_owner;
	/** @var bool */
	protected $favorited = false;
	/** @var Status|null */
	protected $retweeted_status = null;
	/** @var Status|null */
	protected $quoted_status = null;
	/** @var string */
	protected $text;
	/** @var string */
	protected $statusnet_html;
	/** @var string */
	protected $friendica_html;
	/** @var string */
	protected $friendica_title;
	/** @var bool */
	protected $truncated;
	/** @var int */
	protected $friendica_comments;
	/** @var string */
	protected $source;
	/** @var string */
	protected $external_url;
	/** @var int */
	protected $statusnet_conversation_id;
	/** @var bool */
	protected $friendica_private;
	/** @var Attachment */
	protected $attachments = [];
	protected $geo;
	protected $friendica_activities;
	protected $entities;

	/**
	 * Creates a status record from an item record.
	 *
	 * @param array   $item
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public function __construct(array $item, User $author, User $owner, array $retweeted, array $quoted, array $attachments, array $geo, array $friendica_activities, array $entities, int $friendica_comments)
	{
		$this->id                        = (int)$item['id'];
		$this->id_str                    = (string)$item['id'];
		$this->statusnet_conversation_id = (int)$item['parent'];

		$this->created_at = DateTimeFormat::utc($item['created'], DateTimeFormat::API);

		if ($item['gravity'] == GRAVITY_COMMENT) {
			$this->in_reply_to_status_id     = (int)$item['thr-parent-id'];
			$this->in_reply_to_status_id_str = (string)$item['thr-parent-id'];
			$this->in_reply_to_user_id       = (int)$item['parent-author-id'];
			$this->in_reply_to_user_id_str   = (string)$item['parent-author-id'];
			$this->in_reply_to_screen_name   = $item['parent-author-nick'];
		}

		$this->text                 = trim(HTML::toPlaintext(BBCode::convertForUriId($item['uri-id'], $item['body'], BBCode::API), 0));
		$this->friendica_title      = $item['title'];
		$this->statusnet_html       = BBCode::convertForUriId($item['uri-id'], BBCode::setMentionsToNicknames($item['raw-body'] ?? $item['body']), BBCode::API);
		$this->friendica_html       = BBCode::convertForUriId($item['uri-id'], $item['body'], BBCode::EXTERNAL);
		$this->user                 = $author->toArray();
		$this->friendica_author     = $author->toArray();
		$this->friendica_owner      = $owner->toArray();
		$this->truncated            = false;
		$this->friendica_private    = $item['private'] == Item::PRIVATE;
		$this->retweeted_status     = $retweeted;
		$this->quoted_status        = $quoted;
		$this->external_url         = $item['plink'];
		$this->favorited            = (bool)$item['starred'];
		$this->friendica_comments   = $friendica_comments;
		$this->source               = $item['app'] ?: 'web';
		$this->attachments          = $attachments;
		$this->geo                  = $geo;
		$this->friendica_activities = $friendica_activities;
		$this->entities             = $entities;

		if ($this->source == 'web') {
			$this->source = ContactSelector::networkToName($item['author-network'], $item['author-link'], $item['network']);
		} elseif (ContactSelector::networkToName($item['author-network'], $item['author-link'], $item['network']) != $this->source) {
			$this->source = trim($this->source. ' (' . ContactSelector::networkToName($item['author-network'], $item['author-link'], $item['network']) . ')');
		}
	}

	/**
	 * Returns the current entity as an array
	 *
	 * @return array
	 */
	public function toArray(): array
	{
		$status = parent::toArray();

		if (empty($status['retweeted_status'])) {
			unset($status['retweeted_status']);
		}

		if (empty($status['quoted_status'])) {
			unset($status['quoted_status']);
		}

		return $status;
	}
}
