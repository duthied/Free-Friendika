<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Factory\Api\Twitter;

use Friendica\BaseFactory;
use Friendica\Network\HTTPException;
use Friendica\Model\Post;
use Psr\Log\LoggerInterface;
use Friendica\Content\Post\Entity\PostMedia;

class Url extends BaseFactory
{
	public function __construct(LoggerInterface $logger)
	{
		parent::__construct($logger);
	}

	/**
	 * @param int $uriId Uri-ID of the attachments
	 * @return array
	 * @throws HTTPException\InternalServerErrorException
	 */
	public function createFromUriId(int $uriId): array
	{
		$attachments = [];
		foreach (Post\Media::getByURIId($uriId, [PostMedia::TYPE_HTML, PostMedia::TYPE_PLAIN, PostMedia::TYPE_TEXT]) as $attachment) {
			$indices       = [];
			$object        = new \Friendica\Object\Api\Twitter\Url($attachment, $indices);
			$attachments[] = $object->toArray();
		}

		return $attachments;
	}
}
