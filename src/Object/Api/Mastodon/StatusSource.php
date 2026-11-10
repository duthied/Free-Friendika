<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Object\Api\Mastodon;

use Friendica\BaseDataTransferObject;

/**
 * Class StatusSource
 *
 * @see https://docs.joinmastodon.org/entities/StatusSource/
 */
class StatusSource extends BaseDataTransferObject
{
	/** @var string */
	protected $id;
	/** @var string */
	protected $text;
	/** @var string */
	protected $spoiler_text = "";

	/**
	 * Creates a source record from an post array.
	 *
	 * @param integer $id
	 * @param string $text
	 * @param string $spoiler_text
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public function __construct(int $id, string $text, string $spoiler_text)
	{
		$this->id           = (string) $id;
		$this->text         = $text;
		$this->spoiler_text = $spoiler_text;
	}
}
