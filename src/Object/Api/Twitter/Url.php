<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Object\Api\Twitter;

use Friendica\BaseDataTransferObject;

/**
 * Class Url
 *
 * @see https://developer.twitter.com/en/docs/twitter-api/v1/data-dictionary/object-model/entities#urls
 */
class Url extends BaseDataTransferObject
{
	/** @var string */
	protected $display_url;
	/** @var string */
	protected $expanded_url;
	/** @var array */
	protected $indices;
	/** @var string */
	protected $url;

	/**
	 * Creates an URL entity array
	 *
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public function __construct(array $media, array $indices)
	{
		$this->display_url  = $media['url'];
		$this->expanded_url = $media['url'];
		$this->indices      = $indices;
		$this->url          = $media['url'];
	}

	/**
	 * Returns the current entity as an array
	 *
	 * @return array
	 */
	public function toArray(): array
	{
		$status = parent::toArray();

		if (empty($status['indices'])) {
			unset($status['indices']);
		}

		return $status;
	}
}
