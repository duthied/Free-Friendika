<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module;

use Friendica\BaseModule;
use Friendica\Core\System;
use Friendica\Util\Strings;

/**
 * load view/theme/$current_theme/style.php with friendica context
 */
class Theme extends BaseModule
{
	protected function rawContent(array $request = [])
	{
		header('Content-Type: text/css');

		$theme = Strings::sanitizeFilePathItem($this->parameters['theme']);

		if (file_exists("view/theme/$theme/theme.php")) {
			require_once "view/theme/$theme/theme.php";
		}

		// set the path for later use in the theme styles
		$THEMEPATH = "view/theme/$theme";
		if (file_exists("view/theme/$theme/style.php")) {
			require_once "view/theme/$theme/style.php";
		}
		System::exit();
	}
}
