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

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Friendica\App\BaseURL;
use Friendica\Core\L10n;
use Friendica\Core\PConfig\Capability\IManagePersonalConfigValues;
use Friendica\Core\Renderer;
use Friendica\Core\Session;
use Friendica\Module\BaseSettings;
use Friendica\Module\Security\Login;
use PragmaRX\Google2FA\Google2FA;

/**
 * // Page 4: 2FA enabled but not verified, QR code and verification
 *
 * @package Friendica\Module\TwoFactor\Settings
 */
class Verify extends BaseSettings
{
	/** @var IManagePersonalConfigValues */
	protected $pConfig;
	/** @var BaseURL */
	protected $baseUrl;

	public function __construct(IManagePersonalConfigValues $pConfig, BaseURL $baseUrl, L10n $l10n, array $parameters = [])
	{
		parent::__construct($l10n, $parameters);

		$this->pConfig = $pConfig;
		$this->baseUrl = $baseUrl;

		if (!local_user()) {
			return;
		}

		$secret   = $this->pConfig->get(local_user(), '2fa', 'secret');
		$verified = $this->pConfig->get(local_user(), '2fa', 'verified');

		if ($secret && $verified) {
			$this->baseUrl->redirect('settings/2fa');
		}

		if (!self::checkFormSecurityToken('settings_2fa_password', 't')) {
			notice($this->l10n->t('Please enter your password to access this page.'));
			$this->baseUrl->redirect('settings/2fa');
		}
	}

	public function post()
	{
		if (!local_user()) {
			return;
		}

		if (($_POST['action'] ?? '') == 'verify') {
			self::checkFormSecurityTokenRedirectOnError('settings/2fa/verify', 'settings_2fa_verify');

			$google2fa = new Google2FA();

			$valid = $google2fa->verifyKey($this->pConfig->get(local_user(), '2fa', 'secret'), $_POST['verify_code'] ?? '');

			if ($valid) {
				$this->pConfig->set(local_user(), '2fa', 'verified', true);
				Session::set('2fa', true);

				info($this->l10n->t('Two-factor authentication successfully activated.'));

				$this->baseUrl->redirect('settings/2fa');
			} else {
				notice($this->l10n->t('Invalid code, please retry.'));
			}
		}
	}

	public function content(): string
	{
		if (!local_user()) {
			return Login::form('settings/2fa/verify');
		}

		parent::content();

		$company = 'Friendica';
		$holder = Session::get('my_address');
		$secret = $this->pConfig->get(local_user(), '2fa', 'secret');

		$otpauthUrl = (new Google2FA())->getQRCodeUrl($company, $holder, $secret);

		$renderer = (new \BaconQrCode\Renderer\Image\Svg())
			->setHeight(256)
			->setWidth(256);

		$writer = new Writer($renderer);

		$qrcode_image = str_replace('<?xml version="1.0" encoding="UTF-8"?>', '', $writer->writeString($otpauthUrl));

		$shortOtpauthUrl = explode('?', $otpauthUrl)[0];

		$manual_message = $this->l10n->t('<p>Or you can submit the authentication settings manually:</p>
<dl>
	<dt>Issuer</dt>
	<dd>%s</dd>
	<dt>Account Name</dt>
	<dd>%s</dd>
	<dt>Secret Key</dt>
	<dd>%s</dd>
	<dt>Type</dt>
	<dd>Time-based</dd>
	<dt>Number of digits</dt>
	<dd>6</dd>
	<dt>Hashing algorithm</dt>
	<dd>SHA-1</dd>
</dl>', $company, $holder, $secret);

		return Renderer::replaceMacros(Renderer::getMarkupTemplate('settings/twofactor/verify.tpl'), [
			'$form_security_token'     => self::getFormSecurityToken('settings_2fa_verify'),
			'$password_security_token' => self::getFormSecurityToken('settings_2fa_password'),

			'$title'              => $this->l10n->t('Two-factor code verification'),
			'$help_label'         => $this->l10n->t('Help'),
			'$message'            => $this->l10n->t('<p>Please scan this QR Code with your authenticator app and submit the provided code.</p>'),
			'$qrcode_image'       => $qrcode_image,
			'$qrcode_url_message' => $this->l10n->t('<p>Or you can open the following URL in your mobile device:</p><p><a href="%s">%s</a></p>', $otpauthUrl, $shortOtpauthUrl),
			'$manual_message'     => $manual_message,
			'$company'            => $company,
			'$holder'             => $holder,
			'$secret'             => $secret,

			'$verify_code'  => ['verify_code', $this->l10n->t('Please enter a code from your authentication app'), '', '', $this->l10n->t('Required'), 'autofocus autocomplete="off" placeholder="000000"'],
			'$verify_label' => $this->l10n->t('Verify code and enable two-factor authentication'),
		]);
	}
}
