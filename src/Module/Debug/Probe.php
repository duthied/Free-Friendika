<?php

namespace Friendica\Module\Debug;

use Friendica\BaseModule;
use Friendica\Core\L10n;
use Friendica\Core\Renderer;
use Friendica\Network\HTTPException;
use Friendica\Network\Probe as NetworkProbe;

/**
 * Fetch information (protocol endpoints and user information) about a given uri
 */
class Probe extends BaseModule
{
	public static function content(array $parameters = [])
	{
		if (!local_user()) {
			$e           = new HTTPException\ForbiddenException(L10n::t('Only logged in users are permitted to perform a probing.'));
			$e->httpdesc = L10n::t('Public access denied.');
			throw $e;
		}

		$addr = $_GET['addr'] ?? '';
		$res  = '';

		if (!empty($addr)) {
			$res = NetworkProbe::uri($addr, '', 0, false);
			$res = print_r($res, true);
		}

		$tpl = Renderer::getMarkupTemplate('probe.tpl');
		return Renderer::replaceMacros($tpl, [
			'$addr' => ['addr',
				L10n::t('Lookup address'),
				$addr,
				'',
				'required'
			],
			'$res'  => $res,
		]);
	}
}
