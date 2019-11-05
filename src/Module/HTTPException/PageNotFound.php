<?php

namespace Friendica\Module\HTTPException;

use Friendica\BaseModule;
use Friendica\Core\L10n;
use Friendica\Network\HTTPException;

class PageNotFound extends BaseModule
{
	public static function content(array $parameters = [])
	{
		throw new HTTPException\NotFoundException(L10n::t('Page not found.'));
	}
}
