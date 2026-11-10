<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Api\Mastodon;

use Friendica\Content\Smilies;
use Friendica\DI;
use Friendica\Module\BaseApi;
use Friendica\Network\HTTPException;

/**
 * @see https://docs.joinmastodon.org/methods/accounts/follow_requests
 */
class CustomEmojis extends BaseApi
{
	/**
	 * @throws HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 * @see https://docs.joinmastodon.org/methods/accounts/follow_requests#pending-follows
	 */
	protected function rawContent(array $request = []): never
	{
		$emojis = DI::mstdnEmoji()->createCollectionFromSmilies(Smilies::getList());

		$this->earlyJsonExit($emojis->getArrayCopy());
	}
}
