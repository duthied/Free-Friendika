<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\OAuth;

use Friendica\Core\Renderer;
use Friendica\DI;
use Friendica\Module\BaseApi;

/**
 * Acknowledgement of OAuth requests
 */
class Acknowledge extends BaseApi
{
	/**
	 * @internal
	 */
	protected function checkScope(): void {}

	protected function post(array $request = [])
	{
		DI::session()->set('oauth_acknowledge', true);
		DI::appHelper()->redirect(DI::session()->get('return_path'));
	}

	protected function content(array $request = []): string
	{
		DI::session()->set('return_path', 'oauth/authorize?' . $request['return_authorize']);

		$o = Renderer::replaceMacros(Renderer::getMarkupTemplate('oauth_authorize.tpl'), [
			'$title'     => DI::l10n()->t('Authorize application connection'),
			'$app'       => ['name' => $_REQUEST['application'] ?? ''],
			'$authorize' => DI::l10n()->t('Do you want to authorize this application to access your posts and contacts, and/or create new posts for you?'),
			'$yes'       => DI::l10n()->t('Yes'),
			'$no'        => DI::l10n()->t('No'),
		]);

		return $o;
	}
}
