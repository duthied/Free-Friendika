<?php
/**
 * @file mod/maintenance.php
 */
use Friendica\App;
use Friendica\Core\Config;
use Friendica\Core\L10n;
use Friendica\Core\Renderer;

function maintenance_content(App $a)
{
	$reason = Config::get('system', 'maintenance_reason');

	if (substr(normalise_link($reason), 0, 7) == 'http://') {
		header("HTTP/1.1 307 Temporary Redirect");
		header("Location:".$reason);
		return;
	}

	header('HTTP/1.1 503 Service Temporarily Unavailable');
	header('Status: 503 Service Temporarily Unavailable');
	header('Retry-After: 600');

	return Renderer::replaceMacros(Renderer::getMarkupTemplate('maintenance.tpl'), [
		'$sysdown' => L10n::t('System down for maintenance'),
		'$reason' => $reason
	]);
}
