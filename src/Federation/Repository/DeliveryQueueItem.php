<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Federation\Repository;

use Friendica\Database\Database;
use Friendica\Database\DBA;
use Friendica\Federation\Collection;
use Friendica\Federation\Entity;
use Friendica\Federation\Factory;
use Friendica\Util\DateTimeFormat;
use Psr\Log\LoggerInterface;

final class DeliveryQueueItem extends \Friendica\BaseRepository
{
	protected static $table_name = 'delivery-queue';

	public function __construct(
		Database $database,
		LoggerInterface $logger,
		private readonly Factory\DeliveryQueueItem $entityFactory,
	) {
		parent::__construct($database, $logger, $entityFactory);
	}

	/** @not-deprecated */
	protected function getFactory(): Factory\DeliveryQueueItem
	{
		return $this->entityFactory;
	}

	public function selectByServerId(int $gsid, int $maxFailedCount): Collection\DeliveryQueueItems
	{
		$Entities = new Collection\DeliveryQueueItems();

		$deliveryQueueItems = $this->db->select(
			self::$table_name,
			[],
			["`gsid` = ? AND `failed` < ?", $gsid, $maxFailedCount],
			['order' => ['created']],
		);
		while ($deliveryQueueItem = $this->db->fetch($deliveryQueueItems)) {
			$Entities[] = $this->getFactory()->createFromTableRow($deliveryQueueItem);
		}

		$this->db->close($deliveryQueueItems);

		return $Entities;
	}

	public function selectAggregateByServerId(): Collection\DeliveryQueueAggregates
	{
		$Entities = new Collection\DeliveryQueueAggregates();

		$deliveryQueueAggregates = $this->db->p("SELECT `gsid`, MAX(`failed`) AS `failed` FROM " . DBA::buildTableString([self::$table_name]) . " GROUP BY `gsid` ORDER BY RAND()");
		while ($deliveryQueueAggregate = $this->db->fetch($deliveryQueueAggregates)) {
			$Entities[] = new Entity\DeliveryQueueAggregate($deliveryQueueAggregate['gsid'], $deliveryQueueAggregate['failed']);
		}

		$this->db->close($deliveryQueueAggregates);

		return $Entities;
	}

	public function save(Entity\DeliveryQueueItem $deliveryQueueItem)
	{
		$fields = [
			'gsid'    => $deliveryQueueItem->targetServerId,
			'uri-id'  => $deliveryQueueItem->postUriId,
			'created' => $deliveryQueueItem->created->format(DateTimeFormat::MYSQL),
			'command' => $deliveryQueueItem->command,
			'cid'     => $deliveryQueueItem->targetContactId,
			'uid'     => $deliveryQueueItem->senderUserId,
			'failed'  => $deliveryQueueItem->failed,
		];

		$this->db->insert(self::$table_name, $fields, Database::INSERT_UPDATE);
	}

	public function remove(Entity\DeliveryQueueItem $deliveryQueueItem): bool
	{
		return $this->db->delete(self::$table_name, [
			'uri-id' => $deliveryQueueItem->postUriId,
			'gsid'   => $deliveryQueueItem->targetServerId,
		]);
	}

	public function removeFailedByServerId(int $gsid, int $failedThreshold): bool
	{
		return $this->db->delete(self::$table_name, ["`gsid` = ? AND `failed` >= ?", $gsid, $failedThreshold]);
	}

	public function incrementFailed(Entity\DeliveryQueueItem $deliveryQueueItem): bool
	{
		return $this->db->update(self::$table_name, [
			"`failed` = `failed` + 1",
		], [
			"`uri-id` = ? AND `gsid` = ?",
			$deliveryQueueItem->postUriId,
			$deliveryQueueItem->targetServerId,
		]);
	}

	public function optimizeStorage(): bool
	{
		return $this->db->optimizeTable(self::$table_name);
	}
}
