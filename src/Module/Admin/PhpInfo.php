<?php

namespace Friendica\Module\Admin;

use Friendica\Module\BaseAdmin;

class PhpInfo extends BaseAdmin
{
	public static function rawContent(array $parameters = [])
	{
		parent::rawContent($parameters);

		phpinfo();
		exit();
	}
}
