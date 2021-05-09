<?php
/**
 * @copyright Copyright (C) 2010-2021, the Friendica project
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

namespace Friendica\Module\Settings\TwoFactor;

use Friendica\Core\Renderer;
use Friendica\DI;
use Friendica\Security\TwoFactor\Model\RecoveryCode;
use Friendica\Module\BaseSettings;
use Friendica\Module\Security\Login;

/**
 * // Page 3: 2FA enabled but not verified, show recovery codes
 *
 * @package Friendica\Module\TwoFactor
 */
class Recovery extends BaseSettings
{
	public static function init(array $parameters = [])
	{
		if (!local_user()) {
			return;
		}

		$secret = DI::pConfig()->get(local_user(), '2fa', 'secret');

		if (!$secret) {
			DI::baseUrl()->redirect('settings/2fa');
		}

		if (!self::checkFormSecurityToken('settings_2fa_password', 't')) {
			notice(DI::l10n()->t('Please enter your password to access this page.'));
			DI::baseUrl()->redirect('settings/2fa');
		}
	}

	public static function post(array $parameters = [])
	{
		if (!local_user()) {
			return;
		}

		if (!empty($_POST['action'])) {
			self::checkFormSecurityTokenRedirectOnError('settings/2fa/recovery', 'settings_2fa_recovery');

			if ($_POST['action'] == 'regenerate') {
				RecoveryCode::regenerateForUser(local_user());
				info(DI::l10n()->t('New recovery codes successfully generated.'));
				DI::baseUrl()->redirect('settings/2fa/recovery?t=' . self::getFormSecurityToken('settings_2fa_password'));
			}
		}
	}

	public static function content(array $parameters = [])
	{
		if (!local_user()) {
			return Login::form('settings/2fa/recovery');
		}

		parent::content($parameters);

		if (!RecoveryCode::countValidForUser(local_user())) {
			RecoveryCode::generateForUser(local_user());
		}

		$recoveryCodes = RecoveryCode::getListForUser(local_user());

		$verified = DI::pConfig()->get(local_user(), '2fa', 'verified');
		
		return Renderer::replaceMacros(Renderer::getMarkupTemplate('settings/twofactor/recovery.tpl'), [
			'$form_security_token'     => self::getFormSecurityToken('settings_2fa_recovery'),
			'$password_security_token' => self::getFormSecurityToken('settings_2fa_password'),

			'$title'              => DI::l10n()->t('Two-factor recovery codes'),
			'$help_label'         => DI::l10n()->t('Help'),
			'$message'            => DI::l10n()->t('<p>Recovery codes can be used to access your account in the event you lose access to your device and cannot receive two-factor authentication codes.</p><p><strong>Put these in a safe spot!</strong> If you lose your device and don’t have the recovery codes you will lose access to your account.</p>'),
			'$recovery_codes'     => $recoveryCodes,
			'$regenerate_message' => DI::l10n()->t('When you generate new recovery codes, you must copy the new codes. Your old codes won’t work anymore.'),
			'$regenerate_label'   => DI::l10n()->t('Generate new recovery codes'),
			'$verified'           => $verified,
			'$verify_label'       => DI::l10n()->t('Next: Verification'),
		]);
	}
}
