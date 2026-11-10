<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Factory\Api\Mastodon;

use Friendica\BaseFactory;
use Friendica\Content\Text\BBCode;
use Friendica\Core\Protocol;
use Friendica\Model\Post;
use Friendica\Network\HTTPException;

class StatusSource extends BaseFactory
{
	/**
	 * @param int $uriId Uri-ID of the item
	 *
	 * @return \Friendica\Object\Api\Mastodon\StatusSource
	 * @throws HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 * @throws \Exception
	 */
	public function createFromUriId(int $uriId, int $uid): \Friendica\Object\Api\Mastodon\StatusSource
	{
		$post = Post::selectOriginal(['uri-id', 'raw-body', 'body', 'title', 'content-warning'], ['uri-id' => $uriId, 'uid' => [0, $uid]]);

		$spoiler_text = $post['title'] ?: $post['content-warning'] ?: BBCode::toPlaintext(BBCode::getAbstract($post['body'], Protocol::ACTIVITYPUB));

		$body = Post\Media::removeFromEndOfBody($post['body']);
		$body = Post\Media::addHTMLLinkToBody($uriId, $body);
		$body = BBCode::setMentionsToAddr($body);
		$body = BBCode::toPlaintext($body);

		return new \Friendica\Object\Api\Mastodon\StatusSource($post['uri-id'], $body, $spoiler_text);
	}
}
