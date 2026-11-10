<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Search;

use Friendica\Content\Widget;
use Friendica\DI;
use Friendica\Module\BaseSearch;
use Friendica\Module\Security\Login;

/**
 * Directory search module
 */
class Directory extends BaseSearch
{
	protected function content(array $request = []): string
	{
		if (!DI::userSession()->getLocalUserId()) {
			DI::sysmsg()->addNotice(DI::l10n()->t('Permission denied.'));
			return Login::form();
		}

		$search = trim(rawurldecode($_REQUEST['search'] ?? ''));

		if (empty(DI::page()['aside'])) {
			DI::page()['aside'] = '';
		}

		DI::page()['aside'] .= Widget::findPeople();
		DI::page()['aside'] .= Widget::follow();

		return self::performContactSearch($search);
	}
}
