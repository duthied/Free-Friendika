<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Federation\Entity;

use DateTimeImmutable;
use Psr\Http\Message\UriInterface;

/**
 * @property-read int                $id
 * @property-read string             $url
 * @property-read string             $nurl
 * @property-read string             $version
 * @property-read string             $siteName
 * @property-read string             $info
 * @property-read int                $registerPolicy
 * @property-read int                $registeredUsers
 * @property-read string             $poco
 * @property-read string             $noscrape
 * @property-read string             $network
 * @property-read string             $platform
 * @property-read int                $relaySubscribe
 * @property-read string             $relayScope
 * @property-read DateTimeImmutable  $created
 * @property-read ?DateTimeImmutable $lastPocoQuery
 * @property-read ?DateTimeImmutable $lastContact
 * @property-read ?DateTimeImmutable $lastFailure
 * @property-read int                $directoryType
 * @property-read int                $detectionMethod
 * @property-read bool               $failed
 * @property-read DateTimeImmutable  $nextContact
 * @property-read int                $protocol
 * @property-read int                $activeWeekUsers
 * @property-read int                $activeMonthUsers
 * @property-read int                $activeHalfyearUsers
 * @property-read int                $localPosts
 * @property-read int                $localComments
 * @property-read bool               $blocked
 */
class GServer extends \Friendica\BaseEntity
{
	/** @var ?int */
	protected $id;
	/** @var UriInterface */
	protected $url;
	/** @var UriInterface */
	protected $nurl;
	/** @var string */
	protected $version;
	/** @var string */
	protected $siteName;
	/** @var string */
	protected $info;
	/** @var int One of Module\Register::* constant values */
	protected $registerPolicy;
	/** @var int */
	protected $registeredUsers;
	/** @var ?UriInterface */
	protected $poco;
	/** @var ?UriInterface */
	protected $noscrape;
	/** @var string One of the Protocol::* constant values */
	protected $network;
	/** @var string */
	protected $platform;
	/** @var bool */
	protected $relaySubscribe;
	/** @var string */
	protected $relayScope;
	/** @var DateTimeImmutable */
	protected $created;
	/** @var DateTimeImmutable */
	protected $lastPocoQuery;
	/** @var DateTimeImmutable */
	protected $lastContact;
	/** @var DateTimeImmutable */
	protected $lastFailure;
	/** @var int One of Model\Gserver::DT_* constant values */
	protected $directoryType;
	/** @var ?int One of Model\Gserver::DETECT_* constant values */
	protected $detectionMethod;
	/** @var bool */
	protected $failed;
	/** @var DateTimeImmutable */
	protected $nextContact;
	/** @var ?int One of Model\Post\DeliveryData::* constant values */
	protected $protocol;
	/** @var ?int */
	protected $activeWeekUsers;
	/** @var ?int */
	protected $activeMonthUsers;
	/** @var ?int */
	protected $activeHalfyearUsers;
	/** @var ?int */
	protected $localPosts;
	/** @var ?int */
	protected $localComments;
	/** @var bool */
	protected $blocked;

	public function __construct(UriInterface $url, UriInterface $nurl, string $version, string $siteName, string $info, int $registerPolicy, int $registeredUsers, ?UriInterface $poco, ?UriInterface $noscrape, string $network, string $platform, bool $relaySubscribe, string $relayScope, DateTimeImmutable $created, ?DateTimeImmutable $lastPocoQuery, ?DateTimeImmutable $lastContact, ?DateTimeImmutable $lastFailure, int $directoryType, ?int $detectionMethod, bool $failed, ?DateTimeImmutable $nextContact, ?int $protocol, ?int $activeWeekUsers, ?int $activeMonthUsers, ?int $activeHalfyearUsers, ?int $localPosts, ?int $localComments, bool $blocked, ?int $id = null)
	{
		$this->url                 = $url;
		$this->nurl                = $nurl;
		$this->version             = $version;
		$this->siteName            = $siteName;
		$this->info                = $info;
		$this->registerPolicy      = $registerPolicy;
		$this->registeredUsers     = $registeredUsers;
		$this->poco                = $poco;
		$this->noscrape            = $noscrape;
		$this->network             = $network;
		$this->platform            = $platform;
		$this->relaySubscribe      = $relaySubscribe;
		$this->relayScope          = $relayScope;
		$this->created             = $created;
		$this->lastPocoQuery       = $lastPocoQuery;
		$this->lastContact         = $lastContact;
		$this->lastFailure         = $lastFailure;
		$this->directoryType       = $directoryType;
		$this->detectionMethod     = $detectionMethod;
		$this->failed              = $failed;
		$this->nextContact         = $nextContact;
		$this->protocol            = $protocol;
		$this->activeWeekUsers     = $activeWeekUsers;
		$this->activeMonthUsers    = $activeMonthUsers;
		$this->activeHalfyearUsers = $activeHalfyearUsers;
		$this->localPosts          = $localPosts;
		$this->localComments       = $localComments;
		$this->blocked             = $blocked;
		$this->id                  = $id;
	}
}
