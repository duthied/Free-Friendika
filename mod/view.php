<?php

use Friendica\App;
use Friendica\Util\Strings;

/**
 * load view/theme/$current_theme/style.php with friendica context
 *
 * @param App $a
 */
function view_init(App $a)
{
	header("Content-Type: text/css");

	if ($a->argc == 4) {
		$theme = $a->argv[2];
		$theme = Strings::sanitizeFilePathItem($theme);

		// set the path for later use in the theme styles
		$THEMEPATH = "view/theme/$theme";
		if (file_exists("view/theme/$theme/style.php")) {
			require_once("view/theme/$theme/style.php");
		}
	}

	exit();
}
