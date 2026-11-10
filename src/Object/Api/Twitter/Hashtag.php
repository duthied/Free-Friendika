<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Object\Api\Twitter;

use Friendica\BaseDataTransferObject;

/**
 * Class Hashtag
 *
 * @see https://developer.twitter.com/en/docs/twitter-api/v1/data-dictionary/object-model/entities#hashtags
 */
class Hashtag extends BaseDataTransferObject
{
	/** @var array */
	protected $indices;
	/** @var string */
	protected $text;

	/**
	 * Creates a hashtag
	 *
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public function __construct(string $name, array $indices)
	{
		$this->indices = $indices;
		$this->text    = $name;
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
