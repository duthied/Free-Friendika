<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Object\Api\Mastodon;

use Friendica\BaseDataTransferObject;
use Friendica\Object\Api\Mastodon\InstanceV2\Configuration;
use Friendica\Object\Api\Mastodon\InstanceV2\Contact;
use Friendica\Object\Api\Mastodon\InstanceV2\FriendicaExtensions;
use Friendica\Object\Api\Mastodon\InstanceV2\Registrations;
use Friendica\Object\Api\Mastodon\InstanceV2\Thumbnail;
use Friendica\Object\Api\Mastodon\InstanceV2\Usage;

/**
 * Class Instance
 *
 * @see https://docs.joinmastodon.org/entities/Instance/
 */
class InstanceV2 extends BaseDataTransferObject
{
	/** @var string */
	protected $domain;
	/** @var string */
	protected $title;
	/** @var string */
	protected $version;
	/** @var string */
	protected $source_url;
	/** @var string */
	protected $description;
	/** @var Usage */
	protected $usage;
	/** @var Thumbnail */
	protected $thumbnail;
	/** @var array */
	protected $languages;
	/** @var Configuration  */
	protected $configuration;
	/** @var Registrations */
	protected $registrations;
	/** @var Contact */
	protected $contact;
	/** @var array */
	protected $rules = [];
	/** @var FriendicaExtensions */
	protected $friendica;

	/**
	 * @param string $domain
	 * @param string $title
	 * @param $version
	 * @param string $description
	 * @param Usage $usage
	 * @param Thumbnail $thumbnail
	 * @param array $languages
	 * @param Configuration $configuration
	 * @param Registrations $registrations
	 * @param Contact $contact
	 * @param FriendicaExtensions $friendica_extensions
	 * @param array $rules
	 */
	public function __construct(
		string              $domain,
		string              $title,
		string              $version,
		string              $source_url,
		string              $description,
		Usage               $usage,
		Thumbnail           $thumbnail,
		array               $languages,
		Configuration       $configuration,
		Registrations       $registrations,
		Contact             $contact,
		FriendicaExtensions $friendica_extensions,
		array $rules,
	) {
		$this->domain        = $domain;
		$this->title         = $title;
		$this->version       = $version;
		$this->source_url    = $source_url;
		$this->description   = $description;
		$this->usage         = $usage;
		$this->thumbnail     = $thumbnail;
		$this->languages     = $languages;
		$this->configuration = $configuration;
		$this->registrations = $registrations;
		$this->contact       = $contact;
		$this->rules         = $rules;
		$this->friendica     = $friendica_extensions;
	}
}
