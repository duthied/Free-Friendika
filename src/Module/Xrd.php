<?php

namespace Friendica\Module;

use Friendica\BaseModule;
use Friendica\Core\L10n;
use Friendica\Core\Renderer;
use Friendica\Core\System;
use Friendica\Network\Probe;

class Xrd extends BaseModule
{
	public static function init()
	{
		if (local_user()) {
			System::httpExit(
				403,
				[
					"title"       => L10n::t("Public access denied."),
					"description" => L10n::t("Only logged in users are permitted to perform a probing.")
				]
			);
			exit();
		}
	}

	public static function content()
	{
		$addr = defaults($_GET, 'addr', '');
		$res = '';

		if (!empty($addr)) {
			$res = Probe::lrdd($addr);
			$res = str_replace("\n", '<br />', print_r($res, true));
		}

		$tpl = Renderer::getMarkupTemplate("xrd.tpl");
		return Renderer::replaceMacros($tpl, [
			'$addr' => $addr,
			'$res'  => $res,
		]);
	}
}
