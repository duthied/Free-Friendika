<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Object\Api\Mastodon\Status;

use Friendica\BaseDataTransferObject;
use Friendica\Util\DateTimeFormat;

/**
 * Class FriendicaExtension
 *
 * Additional fields on Mastodon Statuses for storing Friendica specific data
 *
 * @see https://docs.joinmastodon.org/entities/status
 */
class FriendicaExtension extends BaseDataTransferObject
{
	/** @var string */
	protected $title;

	/** @var string|null (Datetime) */
	protected $changed_at;

	/** @var string|null (Datetime) */
	protected $commented_at;

	/** @var string|null (Datetime) */
	protected $received_at;

	/** @var FriendicaDeliveryData|null */
	protected $delivery_data;

	/** @var int */
	protected $dislikes_count;

	/** @var bool */
	protected $disliked = false;

	/** @var string|null */
	protected $network;

	/** @var string|null */
	protected $platform;

	/** @var string|null */
	protected $version;

	/** @var string|null */
	protected $sitename;

	/**
	 * @var FriendicaVisibility|null
	 */
	protected $visibility;

	/** @var string|null */
	protected $content;

	/**
	 * Creates a FriendicaExtension object
	 *
	 * @param string                 $title
	 * @param ?string                $changed_at
	 * @param ?string                $commented_at
	 * @param ?string                $received_at
	 * @param int                    $dislikes_count
	 * @param bool                   $disliked
	 * @param ?string                $network
	 * @param ?string                $platform
	 * @param ?string                $version
	 * @param ?string                $sitename
	 * @param ?FriendicaDeliveryData $delivery_data
	 * @param ?FriendicaVisibility   $visibility
	 * @throws \Exception
	 */
	public function __construct(
		string $title,
		?string $changed_at,
		?string $commented_at,
		?string $received_at,
		int $dislikes_count,
		bool $disliked,
		?string $network,
		?string $platform,
		?string $version,
		?string $sitename,
		?FriendicaDeliveryData $delivery_data,
		?FriendicaVisibility $visibility,
		?string $content,
	) {
		$this->title          = $title;
		$this->changed_at     = $changed_at ? DateTimeFormat::utc($changed_at, DateTimeFormat::JSON) : null;
		$this->commented_at   = $commented_at ? DateTimeFormat::utc($commented_at, DateTimeFormat::JSON) : null;
		$this->received_at    = $received_at ? DateTimeFormat::utc($received_at, DateTimeFormat::JSON) : null;
		$this->delivery_data  = $delivery_data;
		$this->dislikes_count = $dislikes_count;
		$this->disliked       = $disliked;
		$this->network        = $network;
		$this->platform       = $platform;
		$this->version        = $version;
		$this->sitename       = $sitename;
		$this->visibility     = $visibility;
		$this->content        = $content;
	}

	/**
	 * Returns the current changed_at string or null if not set
	 * @return ?string
	 */
	public function changedAt(): ?string
	{
		return $this->changed_at;
	}

	/**
	 * Returns the current commented_at string or null if not set
	 * @return ?string
	 */
	public function commentedAt(): ?string
	{
		return $this->commented_at;
	}

	/**
	 * Returns the current received_at string or null if not set
	 * @return ?string
	 */
	public function receivedAt(): ?string
	{
		return $this->received_at;
	}
}
