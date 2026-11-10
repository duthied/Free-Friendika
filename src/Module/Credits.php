<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module;

use Friendica\BaseModule;
use Friendica\Core\Renderer;
use Friendica\DI;

/**
 * Show a credits page for all the developers who helped with the project
 * (only contributors to the git repositories for friendica core and the
 * addons repository will be listed though ATM)
 */
class Credits extends BaseModule
{
	protected function content(array $request = []): string
	{
		/* fill the page with credits */
		$credits_string = file_get_contents('CREDITS.txt');

		$names = explode("\n", $credits_string);

		$tpl = Renderer::getMarkupTemplate('credits.tpl');
		return Renderer::replaceMacros($tpl, [
			'$title'  => DI::l10n()->t('Credits'),
			'$thanks' => DI::l10n()->t('Friendica is a community project, that would not be possible without the help of many people. Here is a list of those who have contributed to the code or the translation of Friendica. Thank you all!'),
			'$names'  => $names,
		]);
	}
}
