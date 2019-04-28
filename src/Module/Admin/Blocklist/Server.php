<?php

namespace Friendica\Module\Admin\Blocklist;

use Friendica\Core\Config;
use Friendica\Core\L10n;
use Friendica\Core\Renderer;
use Friendica\Module\BaseAdminModule;
use Friendica\Util\Strings;

class Server extends BaseAdminModule
{
	public static function post()
	{
		parent::post();

		if (empty($_POST['page_blocklist_save']) && empty($_POST['page_blocklist_edit'])) {
			return;
		}

		parent::checkFormSecurityTokenRedirectOnError('/admin/blocklist/server', 'admin_blocklist');

		if (!empty($_POST['page_blocklist_save'])) {
			//  Add new item to blocklist
			$blocklist = Config::get('system', 'blocklist');
			$blocklist[] = [
				'domain' => Strings::escapeTags(trim($_POST['newentry_domain'])),
				'reason' => Strings::escapeTags(trim($_POST['newentry_reason']))
			];
			Config::set('system', 'blocklist', $blocklist);
			info(L10n::t('Server added to blocklist.') . EOL);
		} else {
			// Edit the entries from blocklist
			$blocklist = [];
			foreach ($_POST['domain'] as $id => $domain) {
				// Trimming whitespaces as well as any lingering slashes
				$domain = Strings::escapeTags(trim($domain, "\x00..\x1F/"));
				$reason = Strings::escapeTags(trim($_POST['reason'][$id]));
				if (empty($_POST['delete'][$id])) {
					$blocklist[] = [
						'domain' => $domain,
						'reason' => $reason
					];
				}
			}
			Config::set('system', 'blocklist', $blocklist);
			info(L10n::t('Site blocklist updated.') . EOL);
		}

		self::getApp()->internalRedirect('admin/blocklist/server');
	}

	public static function content()
	{
		parent::content();

		$a = self::getApp();

		$blocklist = Config::get('system', 'blocklist');
		$blocklistform = [];
		if (is_array($blocklist)) {
			foreach ($blocklist as $id => $b) {
				$blocklistform[] = [
					'domain' => ["domain[$id]", L10n::t('Blocked domain'), $b['domain'], '', L10n::t('The blocked domain'), 'required', '', ''],
					'reason' => ["reason[$id]", L10n::t("Reason for the block"), $b['reason'], L10n::t('The reason why you blocked this domain.') . '(' . $b['domain'] . ')', 'required', '', ''],
					'delete' => ["delete[$id]", L10n::t("Delete domain") . ' (' . $b['domain'] . ')', false, L10n::t("Check to delete this entry from the blocklist")]
				];
			}
		}

		$t = Renderer::getMarkupTemplate('admin/blocklist/server.tpl');
		return Renderer::replaceMacros($t, [
			'$title' => L10n::t('Administration'),
			'$page' => L10n::t('Server Blocklist'),
			'$intro' => L10n::t('This page can be used to define a black list of servers from the federated network that are not allowed to interact with your node. For all entered domains you should also give a reason why you have blocked the remote server.'),
			'$public' => L10n::t('The list of blocked servers will be made publically available on the /friendica page so that your users and people investigating communication problems can find the reason easily.'),
			'$addtitle' => L10n::t('Add new entry to block list'),
			'$newdomain' => ['newentry_domain', L10n::t('Server Domain'), '', L10n::t('The domain of the new server to add to the block list. Do not include the protocol.'), 'required', '', ''],
			'$newreason' => ['newentry_reason', L10n::t('Block reason'), '', L10n::t('The reason why you blocked this domain.'), 'required', '', ''],
			'$submit' => L10n::t('Add Entry'),
			'$savechanges' => L10n::t('Save changes to the blocklist'),
			'$currenttitle' => L10n::t('Current Entries in the Blocklist'),
			'$thurl' => L10n::t('Blocked domain'),
			'$threason' => L10n::t('Reason for the block'),
			'$delentry' => L10n::t('Delete entry from blocklist'),
			'$entries' => $blocklistform,
			'$baseurl' => $a->getBaseURL(true),
			'$confirm_delete' => L10n::t('Delete entry from blocklist?'),
			'$form_security_token' => parent::getFormSecurityToken("admin_blocklist")
		]);
	}
}
