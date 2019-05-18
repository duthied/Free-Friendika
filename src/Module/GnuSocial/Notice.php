<?php

namespace Friendica\Module\GnuSocial;

use Friendica\BaseModule;
use Friendica\Core\L10n;
use Friendica\Database\DBA;
use Friendica\Network\HTTPException;

/**
 * GNU Social -> friendica items permanent-url compatibility
 */
class Notice extends BaseModule
{
	public static function content()
	{
		$a = self::getApp();

		// @TODO: Replace with parameter from router
		$id = ($a->argc > 1) ? $a->argv[1] : 0;

		if (empty($id)) {
			throw new HTTPException\NotFoundException(L10n::t('Item not found.'));
		}

		$item = DBA::selectFirst('item', ['guid'], ['id' => $id]);

		if (empty($item )) {
			throw new HTTPException\NotFoundException(L10n::t('Item not found.'));
		} else {
			$a->internalRedirect('display/' . $item['guid']);
		}
	}
}
