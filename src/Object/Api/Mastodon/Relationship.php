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

namespace Friendica\Object\Api\Mastodon;

use Friendica\BaseDataTransferObject;
use Friendica\Model\Contact;
use Friendica\Util\Network;

/**
 * Class Relationship
 *
 * @see https://docs.joinmastodon.org/api/entities/#relationship
 */
class Relationship extends BaseDataTransferObject
{
	/** @var int */
	protected $id;
	/** @var bool */
	protected $following = false;
	/** @var bool */
	protected $requested = false;
	/**
	 * Unsupported
	 * @var bool
	 */
	protected $endorsed = false;
	/** @var bool */
	protected $followed_by = false;
	/** @var bool */
	protected $muting = false;
	/** @var bool */
	protected $muting_notifications = false;
	/**
	 * Unsupported
	 * @var bool
	 */
	protected $showing_reblogs = true;
	/** @var bool */
	protected $notifying = false;
	/** @var bool */
	protected $blocking = false;
	/** @var bool */
	protected $domain_blocking = false;
	/**
	 * Unsupported
	 * @var bool
	 */
	protected $blocked_by = false;
	/**
	 * Unsupported
	 * @var string
	 */
	protected $note = '';

	/**
	 * @param int   $userContactId Contact row Id with uid != 0
	 * @param array $userContact   Full Contact table record with uid != 0
	 * @param bool  $blocked "true" if user is blocked
	 * @param bool  $muted "true" if user is muted
	 */
	public function __construct(int $userContactId, array $userContact = [], bool $blocked = false, bool $muted = false)
	{
		$this->id                   = $userContactId;
		$this->following            = in_array($userContact['rel'] ?? 0, [Contact::SHARING, Contact::FRIEND]);
		$this->requested            = (bool)$userContact['pending'] ?? false;
		$this->endorsed             = false;
		$this->followed_by          = in_array($userContact['rel'] ?? 0, [Contact::FOLLOWER, Contact::FRIEND]);
		$this->muting               = (bool)($userContact['readonly'] ?? false) || $muted;
		$this->muting_notifications = $this->muting;
		$this->showing_reblogs      = true;
		$this->notifying            = (bool)$userContact['notify_new_posts'] ?? false;
		$this->blocking             = (bool)($userContact['blocked'] ?? false) || $blocked;
		$this->domain_blocking      = Network::isUrlBlocked($userContact['url'] ?? '');
		$this->blocked_by           = false;
		$this->note                 = '';

		return $this;
	}
}
