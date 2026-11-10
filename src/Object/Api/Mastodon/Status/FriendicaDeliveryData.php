<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Object\Api\Mastodon\Status;

use Friendica\BaseDataTransferObject;

/**
 * Class FriendicaDeliveryData
 *
 * Additional fields on Mastodon Statuses for storing Friendica delivery data
 *
 * @see https://docs.joinmastodon.org/entities/status
 */
class FriendicaDeliveryData extends BaseDataTransferObject
{
	/** @var int|null */
	protected $delivery_queue_count;

	/** @var int|null */
	protected $delivery_queue_done;

	/** @var int|null */
	protected $delivery_queue_failed;

	/**
	 * Creates a FriendicaDeliveryData object
	 *
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public function __construct(?int $delivery_queue_count, ?int $delivery_queue_done, ?int $delivery_queue_failed)
	{
		$this->delivery_queue_count  = $delivery_queue_count;
		$this->delivery_queue_done   = $delivery_queue_done;
		$this->delivery_queue_failed = $delivery_queue_failed;
	}
}
