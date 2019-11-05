<?php

namespace Friendica\Module\Admin;

use Friendica\Module\BaseAdminModule;

class PhpInfo extends BaseAdminModule
{
	public static function rawContent($parameters)
	{
		parent::rawContent($parameters);

		phpinfo();
		exit();
	}
}
