<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Federation\Factory;

use Friendica\Federation\Entity;

final class DeliveryQueueItem extends \Friendica\BaseFactory implements \Friendica\Capabilities\ICanCreateFromTableRow
{
	/**
	 * @inheritDoc
	 */
	public function createFromTableRow(array $row): Entity\DeliveryQueueItem
	{
		return new Entity\DeliveryQueueItem(
			$row['gsid'],
			$row['uri-id'],
			new \DateTimeImmutable($row['created']),
			$row['command'],
			$row['cid'],
			$row['uid'],
			$row['failed'],
		);
	}

	public function createFromDelivery(string $cmd, int $uri_id, \DateTimeImmutable $created, int $cid, int $gsid, int $uid): Entity\DeliveryQueueItem
	{
		return new Entity\DeliveryQueueItem(
			$gsid,
			$uri_id,
			$created,
			$cmd,
			$cid,
			$uid,
		);
	}
}
