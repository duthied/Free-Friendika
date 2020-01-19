<?php

namespace Friendica\Module\Debug;

use Friendica\BaseModule;
use Friendica\Core\Renderer;
use Friendica\DI;
use Friendica\Network\Probe;

/**
 * Web based module to perform webfinger probing
 */
class WebFinger extends BaseModule
{
	public static function content(array $parameters = [])
	{
		if (!local_user()) {
			$e           = new \Friendica\Network\HTTPException\ForbiddenException(DI::l10n()->t('Only logged in users are permitted to perform a probing.'));
			$e->httpdesc = DI::l10n()->t('Public access denied.');
			throw $e;
		}

		$addr = $_GET['addr'] ?? '';
		$res  = '';

		if (!empty($addr)) {
			$res = Probe::lrdd($addr);
			$res = print_r($res, true);
		}

		$tpl = Renderer::getMarkupTemplate('webfinger.tpl');
		return Renderer::replaceMacros($tpl, [
			'$addr' => $addr,
			'$res'  => $res,
		]);
	}
}
