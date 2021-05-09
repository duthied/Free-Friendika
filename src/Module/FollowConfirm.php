<?php
namespace Friendica\Module;

use Friendica\BaseModule;
use Friendica\DI;

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

		$intro = DI::intro()->selectFirst(['id' => $intro_id, 'uid' => local_user()]);

		$cid = $intro->{'contact-id'};

		$intro->confirm($duplex, $hidden);

		DI::baseUrl()->redirect('contact/' . intval($cid));
	}
}
