<?php

namespace Friendica\Module\HTTPException;

use Friendica\BaseModule;
use Friendica\DI;
use Friendica\Network\HTTPException;

class MethodNotAllowed extends BaseModule
{
	public static function content(array $parameters = [])
	{
		throw new HTTPException\MethodNotAllowedException(DI::l10n()->t('Method Not Allowed.'));
	}
}
