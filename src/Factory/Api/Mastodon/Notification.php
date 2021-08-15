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

namespace Friendica\Factory\Api\Mastodon;

use Friendica\BaseFactory;
use Friendica\Database\Database;
use Friendica\Model\Notification as ModelNotification;
use Psr\Log\LoggerInterface;

class Notification extends BaseFactory
{
	/** @var Database */
	private $dba;
	/** @var Account */
	private $mstdnAccountFactory;
	/** @var Status */
	private $mstdnStatusFactory;

	public function __construct(LoggerInterface $logger, Database $dba, Account $mstdnAccountFactory, Status $mstdnStatusFactoryFactory)
	{
		parent::__construct($logger);
		$this->dba                 = $dba;
		$this->mstdnAccountFactory = $mstdnAccountFactory;
		$this->mstdnStatusFactory  = $mstdnStatusFactoryFactory;
	}

	public function createFromNotificationId(int $id)
	{
		$notification = $this->dba->selectFirst('notification', [], ['id' => $id]);
		if (!$this->dba->isResult($notification)) {
			return null;
		}
		/*
		follow         = Someone followed you
		follow_request = Someone requested to follow you
		mention        = Someone mentioned you in their status
		reblog         = Someone boosted one of your statuses
		favourite      = Someone favourited one of your statuses
		poll           = A poll you have voted in or created has ended
		status         = Someone you enabled notifications for has posted a status
		*/

		$type = ModelNotification::getType($notification);
		if (empty($type)) {
			return null;
		}

		$account = $this->mstdnAccountFactory->createFromContactId($notification['actor-id'], $notification['uid']);

		if (!empty($notification['target-uri-id'])) {
			try {
				$status = $this->mstdnStatusFactory->createFromUriId($notification['target-uri-id'], $notification['uid']);
			} catch (\Throwable $th) {
				$status = null;
			}
		} else {
			$status = null;
		}

		return new \Friendica\Object\Api\Mastodon\Notification($id, $type, $notification['created'], $account, $status);
	}
}
