<?php

namespace Friendica\Module\HTTPException;

use Friendica\BaseModule;
use Friendica\Core\L10n;
use Friendica\Network\HTTPException;

class MethodNotAllowed extends BaseModule
{
	public static function content(array $parameters = [])
	{
		throw new HTTPException\MethodNotAllowedException(L10n::t('Method Not Allowed.'));
	}
}
