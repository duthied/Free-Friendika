<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Object\Api\Mastodon;

use Friendica\BaseDataTransferObject;

/**
 * Class Error
 *
 * @see https://docs.joinmastodon.org/entities/error
 */
class Error extends BaseDataTransferObject
{
	/** @var string */
	protected $error;
	/** @var string */
	protected $error_description;

	/**
	 * Creates an error record
	 */
	public function __construct(string $error, string $error_description)
	{
		$this->error             = $error;
		$this->error_description = $error_description;
	}

	/**
	 * Returns the current entity as an array
	 *
	 * @return array
	 */
	public function toArray(): array
	{
		$error = parent::toArray();

		if (empty($error['error_description'])) {
			unset($error['error_description']);
		}

		return $error;
	}
}
