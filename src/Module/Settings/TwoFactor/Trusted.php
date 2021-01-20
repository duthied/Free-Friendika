<?php

namespace Friendica\Module\Settings\TwoFactor;

use Friendica\Core\Renderer;
use Friendica\DI;
use Friendica\Module\BaseSettings;
use Friendica\Security\TwoFactor;
use Friendica\Util\Temporal;
use UAParser\Parser;

/**
 * Manages users' two-factor trusted browsers in the 2fa_trusted_browsers table
 */
class Trusted extends BaseSettings
{
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

		$trustedBrowserRepository = new TwoFactor\Repository\TrustedBrowser(DI::dba(), DI::logger());

		if (!empty($_POST['action'])) {
			self::checkFormSecurityTokenRedirectOnError('settings/2fa/trusted', 'settings_2fa_trusted');

			switch ($_POST['action']) {
				case 'remove_all' :
					$trustedBrowserRepository->removeAllForUser(local_user());
					info(DI::l10n()->t('Trusted browsers successfully removed.'));
					DI::baseUrl()->redirect('settings/2fa/trusted?t=' . self::getFormSecurityToken('settings_2fa_password'));
					break;
			}
		}

		if (!empty($_POST['remove_id'])) {
			self::checkFormSecurityTokenRedirectOnError('settings/2fa/trusted', 'settings_2fa_trusted');

			if ($trustedBrowserRepository->removeForUser(local_user(), $_POST['remove_id'])) {
				info(DI::l10n()->t('Trusted browser successfully removed.'));
			}

			DI::baseUrl()->redirect('settings/2fa/trusted?t=' . self::getFormSecurityToken('settings_2fa_password'));
		}
	}


	public static function content(array $parameters = []): string
	{
		parent::content($parameters);

		$trustedBrowserRepository = new TwoFactor\Repository\TrustedBrowser(DI::dba(), DI::logger());
		$trustedBrowsers = $trustedBrowserRepository->selectAllByUid(local_user());

		$parser = Parser::create();

		$trustedBrowserDisplay = array_map(function (TwoFactor\Model\TrustedBrowser $trustedBrowser) use ($parser) {
			$dates = [
				'created_ago' => Temporal::getRelativeDate($trustedBrowser->created),
				'last_used_ago' => Temporal::getRelativeDate($trustedBrowser->last_used),
			];

			$result = $parser->parse($trustedBrowser->user_agent);

			$uaData = [
				'os' => $result->os->family,
				'device' => $result->device->family,
				'browser' => $result->ua->family,
			];

			return $trustedBrowser->toArray() + $dates + $uaData;
		}, $trustedBrowsers->getArrayCopy());

		return Renderer::replaceMacros(Renderer::getMarkupTemplate('settings/twofactor/trusted_browsers.tpl'), [
			'$form_security_token' => self::getFormSecurityToken('settings_2fa_trusted'),
			'$password_security_token' => self::getFormSecurityToken('settings_2fa_password'),

			'$title'               => DI::l10n()->t('Two-factor Trusted Browsers'),
			'$message'             => DI::l10n()->t('Trusted browsers are individual browsers you chose to skip two-factor authentication to access Friendica. Please use this feature sparingly, as it can negate the benefit of two-factor authentication.'),
			'$device_label'        => DI::l10n()->t('Device'),
			'$os_label'            => DI::l10n()->t('OS'),
			'$browser_label'       => DI::l10n()->t('Browser'),
			'$created_label'       => DI::l10n()->t('Trusted'),
			'$last_used_label'     => DI::l10n()->t('Last Use'),
			'$remove_label'        => DI::l10n()->t('Remove'),
			'$remove_all_label'    => DI::l10n()->t('Remove All'),

			'$trusted_browsers'    => $trustedBrowserDisplay,
		]);
	}
}
