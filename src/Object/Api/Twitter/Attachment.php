<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Object\Api\Twitter;

use Friendica\BaseDataTransferObject;

/**
 * Class Attachment
 *
 *
 */
class Attachment extends BaseDataTransferObject
{
	/** @var string */
	protected $url;
	/** @var string */
	protected $mimetype;
	/** @var int */
	protected $size;

	/**
	 * Creates an Attachment entity array
	 *
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public function __construct(array $media)
	{
		$this->url      = $media['url'];
		$this->mimetype = $media['mimetype'];
		$this->size     = $media['size'];
	}

	/**
	 * Returns the current entity as an array
	 *
	 * @return array
	 */
	public function toArray(): array
	{
		$status = parent::toArray();

		if (empty($status['mimetype'])) {
			unset($status['mimetype']);
		}

		if (empty($status['size'])) {
			unset($status['size']);
		}

		return $status;
	}
}
