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

class Attachment extends BaseFactory
{
	public function __construct(LoggerInterface $logger)
	{
		parent::__construct($logger);
	}

	/**
	 * @param int $uriId Uri-ID of the attachments
	 *
	 * @return array
	 * @throws HTTPException\InternalServerErrorException
	 */
	public function createFromUriId(int $uriId): array
	{
		$attachments = [];
		foreach (Post\Media::getByURIId($uriId, [PostMedia::TYPE_AUDIO, PostMedia::TYPE_VIDEO, PostMedia::TYPE_IMAGE]) as $attachment) {
			$object        = new \Friendica\Object\Api\Twitter\Attachment($attachment);
			$attachments[] = $object->toArray();
		}

		return $attachments;
	}
}
