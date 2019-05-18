<?php

namespace Friendica\Module\GnuSocial;

use Friendica\BaseModule;
use Friendica\Core\L10n;
use Friendica\Model\ItemUser;
use Friendica\Network\HTTPException;

/**
 * GNU Social -> friendica items permanent-url compatibility
 */
class Notice extends BaseModule
{
	public static function rawContent()
	{
		$a = self::getApp();

		// @TODO: Replace with parameter from router
		$id = ($a->argc > 1) ? $a->argv[1] : 0;

		if (empty($id)) {
			throw new HTTPException\NotFoundException(L10n::t('Item not found.'));
		}

		$user = ItemUser::getUserForItemId($id, ['nickname']);

		if (empty($user)) {
			throw new HTTPException\NotFoundException(L10n::t('Item not found.'));
		} else {
			$a->internalRedirect('display/' . $user['nickname'] . '/' . $id);
		}
	}
}
