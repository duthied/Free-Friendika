<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Debug;

use Friendica\BaseModule;
use Friendica\Core\System;
use Friendica\DI;
use Friendica\Model\Post;
use Friendica\Network\HTTPException\NotFoundException;
use Friendica\Network\HTTPException\UnauthorizedException;

/**
 * Print the body of an Item
 */
class ItemBody extends BaseModule
{
	/**
	 * @throws NotFoundException|UnauthorizedException
	 *
	 * @return string|never
	 */
	protected function content(array $request = []): string
	{
		if (!DI::userSession()->getLocalUserId()) {
			throw new UnauthorizedException(DI::l10n()->t('Access denied.'));
		}

		if (empty($this->parameters['item'])) {
			throw new NotFoundException(DI::l10n()->t('Item not found.'));
		}

		$itemId = intval($this->parameters['item']);

		$item = Post::selectFirst(['body'], ['uid' => [0, DI::userSession()->getLocalUserId()], 'uri-id' => $itemId]);

		if (empty($item)) {
			throw new NotFoundException(DI::l10n()->t('Item not found.'));
		}

		// TODO: Extract this code into controller
		if (DI::mode()->isAjax()) {
			echo str_replace("\n", '<br />', $item['body']);
			System::exit();
		}

		return str_replace("\n", '<br />', $item['body']);
	}
}
