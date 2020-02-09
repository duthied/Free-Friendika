<?php
/**
 * @copyright Copyright (C) 2020, Friendica
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
use Friendica\Model\TwoFactor\AppSpecificPassword;
use Friendica\Module\BaseSettings;
use Friendica\Module\Security\Login;

/**
 * // Page 5: 2FA enabled, app-specific password generation
 *
 * @package Friendica\Module\TwoFactor
 */
class AppSpecific extends BaseSettings
{
	private static $appSpecificPassword = null;

	public static function init(array $parameters = [])
	{
		if (!local_user()) {
			return;
		}

		$verified = DI::pConfig()->get(local_user(), '2fa', 'verified');

		if (!$verified) {
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
			self::checkFormSecurityTokenRedirectOnError('settings/2fa/app_specific', 'settings_2fa_app_specific');

			switch ($_POST['action']) {
				case 'generate':
					$description = $_POST['description'] ?? '';
					if (empty($description)) {
						notice(DI::l10n()->t('App-specific password generation failed: The description is empty.'));
						DI::baseUrl()->redirect('settings/2fa/app_specific?t=' . self::getFormSecurityToken('settings_2fa_password'));
					} elseif (AppSpecificPassword::checkDuplicateForUser(local_user(), $description)) {
						notice(DI::l10n()->t('App-specific password generation failed: This description already exists.'));
						DI::baseUrl()->redirect('settings/2fa/app_specific?t=' . self::getFormSecurityToken('settings_2fa_password'));
					} else {
						self::$appSpecificPassword = AppSpecificPassword::generateForUser(local_user(), $_POST['description'] ?? '');
						notice(DI::l10n()->t('New app-specific password generated.'));
					}

					break;
				case 'revoke_all' :
					AppSpecificPassword::deleteAllForUser(local_user());
					notice(DI::l10n()->t('App-specific passwords successfully revoked.'));
					DI::baseUrl()->redirect('settings/2fa/app_specific?t=' . self::getFormSecurityToken('settings_2fa_password'));
					break;
			}
		}

		if (!empty($_POST['revoke_id'])) {
			self::checkFormSecurityTokenRedirectOnError('settings/2fa/app_specific', 'settings_2fa_app_specific');

			if (AppSpecificPassword::deleteForUser(local_user(), $_POST['revoke_id'])) {
				notice(DI::l10n()->t('App-specific password successfully revoked.'));
			}

			DI::baseUrl()->redirect('settings/2fa/app_specific?t=' . self::getFormSecurityToken('settings_2fa_password'));
		}
	}

	public static function content(array $parameters = [])
	{
		if (!local_user()) {
			return Login::form('settings/2fa/app_specific');
		}

		parent::content($parameters);

		$appSpecificPasswords = AppSpecificPassword::getListForUser(local_user());

		return Renderer::replaceMacros(Renderer::getMarkupTemplate('settings/twofactor/app_specific.tpl'), [
			'$form_security_token'     => self::getFormSecurityToken('settings_2fa_app_specific'),
			'$password_security_token' => self::getFormSecurityToken('settings_2fa_password'),

			'$title'                  => DI::l10n()->t('Two-factor app-specific passwords'),
			'$help_label'             => DI::l10n()->t('Help'),
			'$message'                => DI::l10n()->t('<p>App-specific passwords are randomly generated passwords used instead your regular password to authenticate your account on third-party applications that don\'t support two-factor authentication.</p>'),
			'$generated_message'      => DI::l10n()->t('Make sure to copy your new app-specific password now. You won’t be able to see it again!'),
			'$generated_app_specific_password' => self::$appSpecificPassword,

			'$description_label'      => DI::l10n()->t('Description'),
			'$last_used_label'        => DI::l10n()->t('Last Used'),
			'$revoke_label'           => DI::l10n()->t('Revoke'),
			'$revoke_all_label'       => DI::l10n()->t('Revoke All'),

			'$app_specific_passwords' => $appSpecificPasswords,
			'$generate_message'       => DI::l10n()->t('When you generate a new app-specific password, you must use it right away, it will be shown to you once after you generate it.'),
			'$generate_title'         => DI::l10n()->t('Generate new app-specific password'),
			'$description_placeholder_label' => DI::l10n()->t('Friendiqa on my Fairphone 2...'),
			'$generate_label' => DI::l10n()->t('Generate'),
		]);
	}
}
