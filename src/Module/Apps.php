<?php

namespace Friendica\Module;

use Friendica\BaseModule;
use Friendica\Content\Nav;
use Friendica\Core\Config;
use Friendica\Core\Renderer;
use Friendica\DI;

/**
 * Shows the App menu
 */
class Apps extends BaseModule
{
	public static function init(array $parameters = [])
	{
		$privateaddons = Config::get('config', 'private_addons');
		if ($privateaddons === "1" && !local_user()) {
			DI::baseUrl()->redirect();
		}
	}

	public static function content(array $parameters = [])
	{
		$apps = Nav::getAppMenu();

		if (count($apps) == 0) {
			notice(DI::l10n()->t('No installed applications.') . EOL);
		}

		$tpl = Renderer::getMarkupTemplate('apps.tpl');
		return Renderer::replaceMacros($tpl, [
			'$title' => DI::l10n()->t('Applications'),
			'$apps'  => $apps,
		]);
	}
}
