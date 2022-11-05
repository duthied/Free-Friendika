<?php
/**
 * @copyright Copyright (C) 2010-2022, the Friendica project
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

use Friendica\App;
use Friendica\BaseModule;
use Friendica\Content\Feature;
use Friendica\Content\Nav;
use Friendica\Core\Hook;
use Friendica\Core\Logger;
use Friendica\Core\Renderer;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Item;
use Friendica\Model\User;
use Friendica\Module\BaseSettings;
use Friendica\Module\Security\Login;
use Friendica\Protocol\Email;

function settings_init(App $a)
{
	if (!DI::userSession()->getLocalUserId()) {
		DI::sysmsg()->addNotice(DI::l10n()->t('Permission denied.'));
		return;
	}

	BaseSettings::createAside();
}

function settings_post(App $a)
{
	if (!$a->isLoggedIn()) {
		DI::sysmsg()->addNotice(DI::l10n()->t('Permission denied.'));
		return;
	}

	if (DI::userSession()->getSubManagedUserId()) {
		return;
	}

	if ((DI::args()->getArgc() > 1) && (DI::args()->getArgv()[1] === 'features')) {
		BaseModule::checkFormSecurityTokenRedirectOnError('/settings/features', 'settings_features');
		foreach ($_POST as $k => $v) {
			if (strpos($k, 'feature_') === 0) {
				DI::pConfig()->set(DI::userSession()->getLocalUserId(), 'feature', substr($k, 8), ((intval($v)) ? 1 : 0));
			}
		}
		return;
	}
}

function settings_content(App $a)
{
	$o = '';
	Nav::setSelected('settings');

	if (!DI::userSession()->getLocalUserId()) {
		//DI::sysmsg()->addNotice(DI::l10n()->t('Permission denied.'));
		return Login::form();
	}

	if (DI::userSession()->getSubManagedUserId()) {
		DI::sysmsg()->addNotice(DI::l10n()->t('Permission denied.'));
		return '';
	}

	if ((DI::args()->getArgc() > 1) && (DI::args()->getArgv()[1] === 'features')) {

		$arr = [];
		$features = Feature::get();
		foreach ($features as $fname => $fdata) {
			$arr[$fname] = [];
			$arr[$fname][0] = $fdata[0];
			foreach (array_slice($fdata,1) as $f) {
				$arr[$fname][1][] = ['feature_' . $f[0], $f[1], Feature::isEnabled(DI::userSession()->getLocalUserId(), $f[0]), $f[2]];
			}
		}

		$tpl = Renderer::getMarkupTemplate('settings/features.tpl');
		$o .= Renderer::replaceMacros($tpl, [
			'$form_security_token' => BaseModule::getFormSecurityToken("settings_features"),
			'$title'               => DI::l10n()->t('Additional Features'),
			'$features'            => $arr,
			'$submit'              => DI::l10n()->t('Save Settings'),
		]);
		return $o;
	}
}
