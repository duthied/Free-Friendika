<?php

namespace Friendica\Module;

use Friendica\BaseModule;
use Friendica\Core\L10n;
use Friendica\Core\Renderer;
use Friendica\Core\System;
use Friendica\Util\Strings;

/**
 * Shows the maintenance reason
 * or redirects to the alternate location
 */
class Maintenance extends BaseModule
{
	public static function content()
	{
		$config = self::getApp()->getConfig();

		$reason = $config->get('system', 'maintenance_reason');

		if ((substr(Strings::normaliseLink($reason), 0, 7) === 'http://') ||
			(substr(Strings::normaliseLink($reason), 0, 8) === 'https://')) {
			System::externalRedirect($reason, 307);
		}

		header('HTTP/1.1 503 Service Temporarily Unavailable');
		header('Status: 503 Service Temporarily Unavailable');
		header('Retry-After: 600');

		return Renderer::replaceMacros(Renderer::getMarkupTemplate('maintenance.tpl'), [
			'$sysdown' => L10n::t('System down for maintenance'),
			'$reason' => $reason
		]);
	}
}
