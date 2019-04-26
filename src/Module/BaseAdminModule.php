<?php

namespace Friendica\Module;

use Friendica\BaseModule;
use Friendica\Core\L10n;
use Friendica\Core\Renderer;
use Friendica\Database\DBA;

abstract class BaseAdminModule extends BaseModule
{
	public static function post()
	{
		if (!is_site_admin()) {
			return;
		}

		// do not allow a page manager to access the admin panel at all.
		if (!empty($_SESSION['submanage'])) {
			return;
		}
	}

	public static function content()
	{
		if (!is_site_admin()) {
			return Login::form();
		}

		if (!empty($_SESSION['submanage'])) {
			return '';
		}

		$a = self::getApp();

		// APC deactivated, since there are problems with PHP 5.5
		//if (function_exists("apc_delete")) {
		// $toDelete = new APCIterator('user', APC_ITER_VALUE);
		// apc_delete($toDelete);
		//}
		// Header stuff
		$a->page['htmlhead'] .= Renderer::replaceMacros(Renderer::getMarkupTemplate('admin/settings_head.tpl'), []);

		/*
		 * Side bar links
		 */

		// array(url, name, extra css classes)
		// not part of $aside to make the template more adjustable
		$aside_sub = [
			'information' => [L10n::t('Information'), [
				'overview'     => ['admin'             , L10n::t('Overview')                , 'overview'],
				'federation'   => ['admin/federation'  , L10n::t('Federation Statistics')   , 'federation']
			]],
			'configuration' => [L10n::t('Configuration'), [
				'site'         => ['admin/site'        , L10n::t('Site')                    , 'site'],
				'users'        => ['admin/users'       , L10n::t('Users')                   , 'users'],
				'addons'       => ['admin/addons'      , L10n::t('Addons')                  , 'addons'],
				'themes'       => ['admin/themes'      , L10n::t('Themes')                  , 'themes'],
				'features'     => ['admin/features'    , L10n::t('Additional features')     , 'features'],
				'tos'          => ['admin/tos'         , L10n::t('Terms of Service')        , 'tos'],
			]],
			'database' => [L10n::t('Database'), [
				'deferred'     => ['admin/queue/deferred', L10n::t('Inspect Deferred Workers'), 'deferred'],
				'workerqueue'  => ['admin/queue'       , L10n::t('Inspect worker Queue')    , 'workerqueue'],
			]],
			'tools' => [L10n::t('Tools'), [
				'contactblock' => ['admin/blocklist/contact', L10n::t('Contact Blocklist')  , 'contactblock'],
				'blocklist'    => ['admin/blocklist/server' , L10n::t('Server Blocklist')   , 'blocklist'],
			]],
			'logs' => [L10n::t('Logs'), [
				'logsconfig'   => ['admin/logs/', L10n::t('Logs')                   , 'logs'],
				'logsview'     => ['admin/logs/view'    , L10n::t('View Logs')              , 'viewlogs'],
			]],
		];

		$addons_admin = [];
		$addonsAdminStmt = DBA::select('addon', ['name'], ['plugin_admin' => 1], ['order' => ['name']]);
		foreach (DBA::toArray($addonsAdminStmt) as $addon) {
			$addons_admin[] = ['admin/addons/' . $addon['name'], $addon['name'], 'addon'];
		}

		$t = Renderer::getMarkupTemplate('admin/aside.tpl');
		$a->page['aside'] .= Renderer::replaceMacros($t, [
			'$admin' => ['addons_admin' => $addons_admin],
			'$subpages' => $aside_sub,
			'$admtxt' => L10n::t('Admin'),
			'$plugadmtxt' => L10n::t('Addon Features'),
			'$h_pending' => L10n::t('User registrations waiting for confirmation'),
			'$admurl' => 'admin/'
		]);

		return '';
	}
}
