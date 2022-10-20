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
use Friendica\Core\Session;
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

	if (!empty($_SESSION['submanage'])) {
		return;
	}

	if ((DI::args()->getArgc() > 1) && (DI::args()->getArgv()[1] == 'addon')) {
		BaseModule::checkFormSecurityTokenRedirectOnError(DI::args()->getQueryString(), 'settings_addon');

		Hook::callAll('addon_settings_post', $_POST);
		DI::baseUrl()->redirect(DI::args()->getQueryString());
		return;
	}

	$user = User::getById($a->getLoggedInUserId());

	if ((DI::args()->getArgc() > 1) && (DI::args()->getArgv()[1] == 'connectors')) {
		BaseModule::checkFormSecurityTokenRedirectOnError(DI::args()->getQueryString(), 'settings_connectors');

		if (!empty($_POST['general-submit'])) {
			DI::pConfig()->set(DI::userSession()->getLocalUserId(), 'system', 'accept_only_sharer', intval($_POST['accept_only_sharer']));
			DI::pConfig()->set(DI::userSession()->getLocalUserId(), 'system', 'disable_cw', !intval($_POST['enable_cw']));
			DI::pConfig()->set(DI::userSession()->getLocalUserId(), 'system', 'no_intelligent_shortening', !intval($_POST['enable_smart_shortening']));
			DI::pConfig()->set(DI::userSession()->getLocalUserId(), 'system', 'simple_shortening', intval($_POST['simple_shortening']));
			DI::pConfig()->set(DI::userSession()->getLocalUserId(), 'system', 'attach_link_title', intval($_POST['attach_link_title']));
			DI::pConfig()->set(DI::userSession()->getLocalUserId(), 'ostatus', 'legacy_contact', $_POST['legacy_contact']);
		} elseif (!empty($_POST['mail-submit'])) {
			$mail_server       =                 $_POST['mail_server']       ?? '';
			$mail_port         =                 $_POST['mail_port']         ?? '';
			$mail_ssl          = strtolower(trim($_POST['mail_ssl']          ?? ''));
			$mail_user         =                 $_POST['mail_user']         ?? '';
			$mail_pass         =            trim($_POST['mail_pass']         ?? '');
			$mail_action       =            trim($_POST['mail_action']       ?? '');
			$mail_movetofolder =            trim($_POST['mail_movetofolder'] ?? '');
			$mail_replyto      =                 $_POST['mail_replyto']      ?? '';
			$mail_pubmail      =                 $_POST['mail_pubmail']      ?? '';

			if (function_exists('imap_open') && !DI::config()->get('system', 'imap_disabled')) {
				if (!DBA::exists('mailacct', ['uid' => DI::userSession()->getLocalUserId()])) {
					DBA::insert('mailacct', ['uid' => DI::userSession()->getLocalUserId()]);
				}
				if (strlen($mail_pass)) {
					$pass = '';
					openssl_public_encrypt($mail_pass, $pass, $user['pubkey']);
					DBA::update('mailacct', ['pass' => bin2hex($pass)], ['uid' => DI::userSession()->getLocalUserId()]);
				}

				$r = DBA::update('mailacct', [
					'server'       => $mail_server,
					'port'         => $mail_port,
					'ssltype'      => $mail_ssl,
					'user'         => $mail_user,
					'action'       => $mail_action,
					'movetofolder' => $mail_movetofolder,
					'mailbox'      => 'INBOX',
					'reply_to'     => $mail_replyto,
					'pubmail'      => $mail_pubmail
				], ['uid' => DI::userSession()->getLocalUserId()]);

				Logger::debug('updating mailaccount', ['response' => $r]);
				$mailacct = DBA::selectFirst('mailacct', [], ['uid' => DI::userSession()->getLocalUserId()]);
				if (DBA::isResult($mailacct)) {
					$mb = Email::constructMailboxName($mailacct);

					if (strlen($mailacct['server'])) {
						$dcrpass = '';
						openssl_private_decrypt(hex2bin($mailacct['pass']), $dcrpass, $user['prvkey']);
						$mbox = Email::connect($mb, $mail_user, $dcrpass);
						unset($dcrpass);
						if (!$mbox) {
							DI::sysmsg()->addNotice(DI::l10n()->t('Failed to connect with email account using the settings provided.'));
						}
					}
				}
			}
		}

		Hook::callAll('connector_settings_post', $_POST);
		DI::baseUrl()->redirect(DI::args()->getQueryString());
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

	if (!empty($_SESSION['submanage'])) {
		DI::sysmsg()->addNotice(DI::l10n()->t('Permission denied.'));
		return '';
	}

	if ((DI::args()->getArgc() > 1) && (DI::args()->getArgv()[1] === 'oauth')) {
		if ((DI::args()->getArgc() > 3) && (DI::args()->getArgv()[2] === 'delete')) {
			BaseModule::checkFormSecurityTokenRedirectOnError('/settings/oauth', 'settings_oauth', 't');

			DBA::delete('application-token', ['application-id' => DI::args()->getArgv()[3], 'uid' => DI::userSession()->getLocalUserId()]);
			DI::baseUrl()->redirect('settings/oauth/', true);
			return '';
		}

		$applications = DBA::selectToArray('application-view', ['id', 'uid', 'name', 'website', 'scopes', 'created_at'], ['uid' => DI::userSession()->getLocalUserId()]);

		$tpl = Renderer::getMarkupTemplate('settings/oauth.tpl');
		$o .= Renderer::replaceMacros($tpl, [
			'$form_security_token' => BaseModule::getFormSecurityToken("settings_oauth"),
			'$baseurl'             => DI::baseUrl()->get(true),
			'$title'               => DI::l10n()->t('Connected Apps'),
			'$name'                => DI::l10n()->t('Name'),
			'$website'             => DI::l10n()->t('Home Page'),
			'$created_at'          => DI::l10n()->t('Created'),
			'$delete'              => DI::l10n()->t('Remove authorization'),
			'$apps'                => $applications,
		]);
		return $o;
	}

	if ((DI::args()->getArgc() > 1) && (DI::args()->getArgv()[1] === 'addon')) {
		$addon_settings_forms = [];
		foreach (DI::dba()->selectToArray('hook', ['file', 'function'], ['hook' => 'addon_settings']) as $hook) {
			$data = [];
			Hook::callSingle(DI::app(), 'addon_settings', [$hook['file'], $hook['function']], $data);

			if (!empty($data['href'])) {
				$tpl = Renderer::getMarkupTemplate('settings/addon/link.tpl');
				$addon_settings_forms[] = Renderer::replaceMacros($tpl, [
					'$addon' => $data['addon'],
					'$title' => $data['title'],
					'$href'  => $data['href'],
				]);
			} elseif(!empty($data['addon'])) {
				$tpl = Renderer::getMarkupTemplate('settings/addon/panel.tpl');
				$addon_settings_forms[$data['addon']] = Renderer::replaceMacros($tpl, [
					'$addon'  => $data['addon'],
					'$title'  => $data['title'],
					'$open'   => (DI::args()->getArgv()[2] ?? '') === $data['addon'],
					'$html'   => $data['html'] ?? '',
					'$submit' => $data['submit'] ?? DI::l10n()->t('Save Settings'),
				]);
			}
		}

		$tpl = Renderer::getMarkupTemplate('settings/addons.tpl');
		$o .= Renderer::replaceMacros($tpl, [
			'$form_security_token' => BaseModule::getFormSecurityToken("settings_addon"),
			'$title'	=> DI::l10n()->t('Addon Settings'),
			'$no_addons_settings_configured' => DI::l10n()->t('No Addon settings configured'),
			'$addon_settings_forms' => $addon_settings_forms,
		]);
		return $o;
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

	if ((DI::args()->getArgc() > 1) && (DI::args()->getArgv()[1] === 'connectors')) {
		$accept_only_sharer        = intval(DI::pConfig()->get(DI::userSession()->getLocalUserId(), 'system', 'accept_only_sharer'));
		$enable_cw                 = !intval(DI::pConfig()->get(DI::userSession()->getLocalUserId(), 'system', 'disable_cw'));
		$enable_smart_shortening   = !intval(DI::pConfig()->get(DI::userSession()->getLocalUserId(), 'system', 'no_intelligent_shortening'));
		$simple_shortening         = intval(DI::pConfig()->get(DI::userSession()->getLocalUserId(), 'system', 'simple_shortening'));
		$attach_link_title         = intval(DI::pConfig()->get(DI::userSession()->getLocalUserId(), 'system', 'attach_link_title'));
		$legacy_contact            = DI::pConfig()->get(DI::userSession()->getLocalUserId(), 'ostatus', 'legacy_contact');

		if (!empty($legacy_contact)) {
			/// @todo Isn't it supposed to be a $a->internalRedirect() call?
			DI::page()['htmlhead'] = '<meta http-equiv="refresh" content="0; URL=' . DI::baseUrl().'/ostatus_subscribe?url=' . urlencode($legacy_contact) . '">';
		}

		$connector_settings_forms = [];
		foreach (DI::dba()->selectToArray('hook', ['file', 'function'], ['hook' => 'connector_settings']) as $hook) {
			$data = [];
			Hook::callSingle(DI::app(), 'connector_settings', [$hook['file'], $hook['function']], $data);

			$tpl = Renderer::getMarkupTemplate('settings/addon/connector.tpl');
			$connector_settings_forms[$data['connector']] = Renderer::replaceMacros($tpl, [
				'$connector' => $data['connector'],
				'$title'     => $data['title'],
				'$image'     => $data['image'] ?? '',
				'$enabled'   => $data['enabled'] ?? true,
				'$open'      => (DI::args()->getArgv()[2] ?? '') === $data['connector'],
				'$html'      => $data['html'] ?? '',
				'$submit'    => $data['submit'] ?? DI::l10n()->t('Save Settings'),
			]);
		}

		if ($a->isSiteAdmin()) {
			$diasp_enabled = DI::l10n()->t('Built-in support for %s connectivity is %s', DI::l10n()->t('Diaspora (Socialhome, Hubzilla)'), ((DI::config()->get('system', 'diaspora_enabled')) ? DI::l10n()->t('enabled') : DI::l10n()->t('disabled')));
			$ostat_enabled = DI::l10n()->t('Built-in support for %s connectivity is %s', DI::l10n()->t('OStatus (GNU Social)'), ((DI::config()->get('system', 'ostatus_disabled')) ? DI::l10n()->t('disabled') : DI::l10n()->t('enabled')));
		} else {
			$diasp_enabled = "";
			$ostat_enabled = "";
		}

		$mail_disabled = ((function_exists('imap_open') && (!DI::config()->get('system', 'imap_disabled'))) ? 0 : 1);
		if (!$mail_disabled) {
			$mailacct = DBA::selectFirst('mailacct', [], ['uid' => DI::userSession()->getLocalUserId()]);
		} else {
			$mailacct = null;
		}

		$mail_server       = $mailacct['server'] ?? '';
		$mail_port         = (!empty($mailacct['port']) && is_numeric($mailacct['port'])) ? (int)$mailacct['port'] : '';
		$mail_ssl          = $mailacct['ssltype'] ?? '';
		$mail_user         = $mailacct['user'] ?? '';
		$mail_replyto      = $mailacct['reply_to'] ?? '';
		$mail_pubmail      = $mailacct['pubmail'] ?? 0;
		$mail_action       = $mailacct['action'] ?? 0;
		$mail_movetofolder = $mailacct['movetofolder'] ?? '';
		$mail_chk          = $mailacct['last_check'] ?? DBA::NULL_DATETIME;


		$tpl = Renderer::getMarkupTemplate('settings/connectors.tpl');

		$mail_disabled_message = ($mail_disabled ? DI::l10n()->t('Email access is disabled on this site.') : '');

		$ssl_options = ['TLS' => 'TLS', 'SSL' => 'SSL'];

		if (DI::config()->get('system', 'insecure_imap')) {
			$ssl_options['notls'] = DI::l10n()->t('None');
		}

		$o .= Renderer::replaceMacros($tpl, [
			'$form_security_token' => BaseModule::getFormSecurityToken("settings_connectors"),

			'$title'	=> DI::l10n()->t('Social Networks'),

			'$diasp_enabled' => $diasp_enabled,
			'$ostat_enabled' => $ostat_enabled,

			'$general_settings' => DI::l10n()->t('General Social Media Settings'),
			'$accept_only_sharer' => [
				'accept_only_sharer',
				DI::l10n()->t('Followed content scope'),
				$accept_only_sharer,
				DI::l10n()->t('By default, conversations in which your follows participated but didn\'t start will be shown in your timeline. You can turn this behavior off, or expand it to the conversations in which your follows liked a post.'),
				[
					Item::COMPLETION_NONE    => DI::l10n()->t('Only conversations my follows started'),
					Item::COMPLETION_COMMENT => DI::l10n()->t('Conversations my follows started or commented on (default)'),
					Item::COMPLETION_LIKE    => DI::l10n()->t('Any conversation my follows interacted with, including likes'),
				]
			],
			'$enable_cw' => ['enable_cw', DI::l10n()->t('Enable Content Warning'), $enable_cw, DI::l10n()->t('Users on networks like Mastodon or Pleroma are able to set a content warning field which collapse their post by default. This enables the automatic collapsing instead of setting the content warning as the post title. Doesn\'t affect any other content filtering you eventually set up.')],
			'$enable_smart_shortening' => ['enable_smart_shortening', DI::l10n()->t('Enable intelligent shortening'), $enable_smart_shortening, DI::l10n()->t('Normally the system tries to find the best link to add to shortened posts. If disabled, every shortened post will always point to the original friendica post.')],
			'$simple_shortening' => ['simple_shortening', DI::l10n()->t('Enable simple text shortening'), $simple_shortening, DI::l10n()->t('Normally the system shortens posts at the next line feed. If this option is enabled then the system will shorten the text at the maximum character limit.')],
			'$attach_link_title' => ['attach_link_title', DI::l10n()->t('Attach the link title'), $attach_link_title, DI::l10n()->t('When activated, the title of the attached link will be added as a title on posts to Diaspora. This is mostly helpful with "remote-self" contacts that share feed content.')],
			'$legacy_contact' => ['legacy_contact', DI::l10n()->t('Your legacy ActivityPub/GNU Social account'), $legacy_contact, DI::l10n()->t("If you enter your old account name from an ActivityPub based system or your GNU Social/Statusnet account name here (in the format user@domain.tld), your contacts will be added automatically. The field will be emptied when done.")],

			'$repair_ostatus_url' => DI::baseUrl() . '/repair_ostatus',
			'$repair_ostatus_text' => DI::l10n()->t('Repair OStatus subscriptions'),

			'$connector_settings_forms' => $connector_settings_forms,

			'$h_mail' => DI::l10n()->t('Email/Mailbox Setup'),
			'$mail_desc' => DI::l10n()->t("If you wish to communicate with email contacts using this service \x28optional\x29, please specify how to connect to your mailbox."),
			'$mail_lastcheck' => ['mail_lastcheck', DI::l10n()->t('Last successful email check:'), $mail_chk, ''],
			'$mail_disabled' => $mail_disabled_message,
			'$mail_server'	=> ['mail_server',	DI::l10n()->t('IMAP server name:'), $mail_server, ''],
			'$mail_port'	=> ['mail_port', 	DI::l10n()->t('IMAP port:'), $mail_port, ''],
			'$mail_ssl'	=> ['mail_ssl',		DI::l10n()->t('Security:'), strtoupper($mail_ssl), '', $ssl_options],
			'$mail_user'	=> ['mail_user',	DI::l10n()->t('Email login name:'), $mail_user, ''],
			'$mail_pass'	=> ['mail_pass',	DI::l10n()->t('Email password:'), '', ''],
			'$mail_replyto'	=> ['mail_replyto',	DI::l10n()->t('Reply-to address:'), $mail_replyto, 'Optional'],
			'$mail_pubmail'	=> ['mail_pubmail',	DI::l10n()->t('Send public posts to all email contacts:'), $mail_pubmail, ''],
			'$mail_action'	=> ['mail_action',	DI::l10n()->t('Action after import:'), $mail_action, '', [0 => DI::l10n()->t('None'), 1 => DI::l10n()->t('Delete'), 2 => DI::l10n()->t('Mark as seen'), 3 => DI::l10n()->t('Move to folder')]],
			'$mail_movetofolder' => ['mail_movetofolder', DI::l10n()->t('Move to folder:'), $mail_movetofolder, ''],
			'$submit' => DI::l10n()->t('Save Settings'),
		]);

		Hook::callAll('display_settings', $o);
		return $o;
	}
}
