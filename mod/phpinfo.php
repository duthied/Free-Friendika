<?php
/**
 * @file mod/phpinfo.php
 */

function phpinfo_content()
{
	if (!is_site_admin()) {
		return false;
	}

	phpinfo();
	killme();
}
