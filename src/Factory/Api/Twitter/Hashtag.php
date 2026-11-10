<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Factory\Api\Twitter;

use Friendica\BaseFactory;
use Friendica\Network\HTTPException;
use Friendica\Model\Tag;
use Psr\Log\LoggerInterface;

class Hashtag extends BaseFactory
{
	public function __construct(LoggerInterface $logger)
	{
		parent::__construct($logger);
	}

	/**
	 * @param int $uriId Uri-ID of the attachments
	 * @param string $text
	 *
	 * @return array
	 * @throws HTTPException\InternalServerErrorException
	 */
	public function createFromUriId(int $uriId, string $text): array
	{
		$hashtags = [];
		foreach (Tag::getByURIId($uriId, [Tag::HASHTAG]) as $tag) {
			$indices    = [];
			$object     = new \Friendica\Object\Api\Twitter\Hashtag($tag['name'], $indices);
			$hashtags[] = $object->toArray();
		}

		return $hashtags;
	}
}
