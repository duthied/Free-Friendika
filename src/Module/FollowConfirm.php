<?php
namespace Friendica\Module;

use Friendica\BaseModule;
use Friendica\DI;
use Friendica\Model\Contact;

/**
 * Process follow request confirmations
 */
class FollowConfirm extends BaseModule
{
	public static function post(array $parameters = [])
	{
		$uid = local_user();
		if (!$uid) {
			notice(DI::l10n()->t('Permission denied.'));
			return;
		}

		$intro_id = intval($_POST['intro_id']   ?? 0);
		$duplex   = intval($_POST['duplex']     ?? 0);
		$hidden   = intval($_POST['hidden']     ?? 0);

		$intro = DI::intro()->selectOneById($intro_id, local_user());

		Contact\Introduction::confirm($intro, $duplex, $hidden);
		DI::intro()->delete($intro);

		DI::baseUrl()->redirect('contact/' .  $intro->cid);
	}
}
