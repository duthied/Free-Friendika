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

namespace Friendica\Module\Security\TwoFactor;

use Friendica\BaseModule;
use Friendica\Core\Renderer;
use Friendica\Core\Session;
use Friendica\DI;
use Friendica\Model\TwoFactor\RecoveryCode;

/**
 * // Page 1a: Recovery code verification
 *
 * @package Friendica\Module\TwoFactor
 */
class Recovery extends BaseModule
{
	public static function init(array $parameters = [])
	{
		if (!local_user()) {
			return;
		}
	}

	public static function post(array $parameters = [])
	{
		if (!local_user()) {
			return;
		}

		if (($_POST['action'] ?? '') == 'recover') {
			self::checkFormSecurityTokenRedirectOnError('2fa', 'twofactor_recovery');

			$a = DI::app();

			$recovery_code = $_POST['recovery_code'] ?? '';

			if (RecoveryCode::existsForUser(local_user(), $recovery_code)) {
				RecoveryCode::markUsedForUser(local_user(), $recovery_code);
				Session::set('2fa', true);
				notice(DI::l10n()->t('Remaining recovery codes: %d', RecoveryCode::countValidForUser(local_user())));

				DI::auth()->setForUser($a, $a->user, true, true);
			} else {
				notice(DI::l10n()->t('Invalid code, please retry.'));
			}
		}
	}

	public static function content(array $parameters = [])
	{
		if (!local_user()) {
			DI::baseUrl()->redirect();
		}

		// Already authenticated with 2FA token
		if (Session::get('2fa')) {
			DI::baseUrl()->redirect();
		}

		return Renderer::replaceMacros(Renderer::getMarkupTemplate('twofactor/recovery.tpl'), [
			'$form_security_token' => self::getFormSecurityToken('twofactor_recovery'),

			'$title'            => DI::l10n()->t('Two-factor recovery'),
			'$message'          => DI::l10n()->t('<p>You can enter one of your one-time recovery codes in case you lost access to your mobile device.</p>'),
			'$recovery_message' => DI::l10n()->t('Don’t have your phone? <a href="%s">Enter a two-factor recovery code</a>', '2fa/recovery'),
			'$recovery_code'    => ['recovery_code', DI::l10n()->t('Please enter a recovery code'), '', '', '', 'placeholder="000000-000000"'],
			'$recovery_label'   => DI::l10n()->t('Submit recovery code and complete login'),
		]);
	}
}
