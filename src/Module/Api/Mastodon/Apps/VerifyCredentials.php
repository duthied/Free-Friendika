<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Api\Mastodon\Apps;

use Friendica\DI;
use Friendica\Module\BaseApi;

/**
 * @see https://docs.joinmastodon.org/methods/apps/#verify_credentials
 */
class VerifyCredentials extends BaseApi
{
	protected function rawContent(array $request = [])
	{
		$this->checkAllowedScope(self::SCOPE_ANY);
		$application = self::getCurrentApplication();

		if (empty($application['id'])) {
			$this->logAndJsonError(401, $this->errorFactory->Unauthorized());
		}

		$this->earlyJsonExit(DI::mstdnApplication()->createFromApplicationId($application['id']));
	}
}
