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
use PragmaRX\Google2FA\Google2FA;

/**
 * Page 1: Authenticator code verification
 *
 * @package Friendica\Module\TwoFactor
 */
class Verify extends BaseModule
{
	private static $errors = [];

	public static function post(array $parameters = [])
	{
		if (!local_user()) {
			return;
		}

		if (($_POST['action'] ?? '') == 'verify') {
			self::checkFormSecurityTokenRedirectOnError('2fa', 'twofactor_verify');

			$a = DI::app();

			$code = $_POST['verify_code'] ?? '';

			$valid = (new Google2FA())->verifyKey(DI::pConfig()->get(local_user(), '2fa', 'secret'), $code);

			// The same code can't be used twice even if it's valid
			if ($valid && Session::get('2fa') !== $code) {
				Session::set('2fa', $code);

				// Resume normal login workflow
				DI::auth()->setForUser($a, $a->user, true, true);
			} else {
				self::$errors[] = DI::l10n()->t('Invalid code, please retry.');
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

		return Renderer::replaceMacros(Renderer::getMarkupTemplate('twofactor/verify.tpl'), [
			'$form_security_token' => self::getFormSecurityToken('twofactor_verify'),

			'$title'            => DI::l10n()->t('Two-factor authentication'),
			'$message'          => DI::l10n()->t('<p>Open the two-factor authentication app on your device to get an authentication code and verify your identity.</p>'),
			'$errors_label'     => DI::l10n()->tt('Error', 'Errors', count(self::$errors)),
			'$errors'           => self::$errors,
			'$recovery_message' => DI::l10n()->t('Don’t have your phone? <a href="%s">Enter a two-factor recovery code</a>', '2fa/recovery'),
			'$verify_code'      => ['verify_code', DI::l10n()->t('Please enter a code from your authentication app'), '', '', 'required', 'autofocus placeholder="000000"', 'tel'],
			'$verify_label'     => DI::l10n()->t('Verify code and complete login'),
		]);
	}
}
