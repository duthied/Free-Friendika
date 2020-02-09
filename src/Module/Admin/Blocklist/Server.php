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

namespace Friendica\Module\Admin\Blocklist;

use Friendica\Core\Renderer;
use Friendica\DI;
use Friendica\Module\BaseAdmin;
use Friendica\Util\Strings;

class Server extends BaseAdmin
{
	public static function post(array $parameters = [])
	{
		parent::post($parameters);

		if (empty($_POST['page_blocklist_save']) && empty($_POST['page_blocklist_edit'])) {
			return;
		}

		parent::checkFormSecurityTokenRedirectOnError('/admin/blocklist/server', 'admin_blocklist');

		if (!empty($_POST['page_blocklist_save'])) {
			//  Add new item to blocklist
			$blocklist = DI::config()->get('system', 'blocklist');
			$blocklist[] = [
				'domain' => Strings::escapeTags(trim($_POST['newentry_domain'])),
				'reason' => Strings::escapeTags(trim($_POST['newentry_reason']))
			];
			DI::config()->set('system', 'blocklist', $blocklist);
			info(DI::l10n()->t('Server domain pattern added to blocklist.') . EOL);
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
			DI::config()->set('system', 'blocklist', $blocklist);
			info(DI::l10n()->t('Site blocklist updated.') . EOL);
		}

		DI::baseUrl()->redirect('admin/blocklist/server');
	}

	public static function content(array $parameters = [])
	{
		parent::content($parameters);

		$blocklist = DI::config()->get('system', 'blocklist');
		$blocklistform = [];
		if (is_array($blocklist)) {
			foreach ($blocklist as $id => $b) {
				$blocklistform[] = [
					'domain' => ["domain[$id]", DI::l10n()->t('Blocked server domain pattern'), $b['domain'], '', 'required', '', ''],
					'reason' => ["reason[$id]", DI::l10n()->t("Reason for the block"), $b['reason'], '', 'required', '', ''],
					'delete' => ["delete[$id]", DI::l10n()->t("Delete server domain pattern") . ' (' . $b['domain'] . ')', false, DI::l10n()->t("Check to delete this entry from the blocklist")]
				];
			}
		}

		$t = Renderer::getMarkupTemplate('admin/blocklist/server.tpl');
		return Renderer::replaceMacros($t, [
			'$title' => DI::l10n()->t('Administration'),
			'$page' => DI::l10n()->t('Server Domain Pattern Blocklist'),
			'$intro' => DI::l10n()->t('This page can be used to define a blacklist of server domain patterns from the federated network that are not allowed to interact with your node. For each domain pattern you should also provide the reason why you block it.'),
			'$public' => DI::l10n()->t('The list of blocked server domain patterns will be made publically available on the <a href="/friendica">/friendica</a> page so that your users and people investigating communication problems can find the reason easily.'),
			'$syntax' => DI::l10n()->t('<p>The server domain pattern syntax is case-insensitive shell wildcard, comprising the following special characters:</p>
<ul>
	<li><code>*</code>: Any number of characters</li>
	<li><code>?</code>: Any single character</li>
	<li><code>[&lt;char1&gt;&lt;char2&gt;...]</code>: char1 or char2</li>
</ul>'),
			'$addtitle' => DI::l10n()->t('Add new entry to block list'),
			'$newdomain' => ['newentry_domain', DI::l10n()->t('Server Domain Pattern'), '', DI::l10n()->t('The domain pattern of the new server to add to the block list. Do not include the protocol.'), 'required', '', ''],
			'$newreason' => ['newentry_reason', DI::l10n()->t('Block reason'), '', DI::l10n()->t('The reason why you blocked this server domain pattern.'), 'required', '', ''],
			'$submit' => DI::l10n()->t('Add Entry'),
			'$savechanges' => DI::l10n()->t('Save changes to the blocklist'),
			'$currenttitle' => DI::l10n()->t('Current Entries in the Blocklist'),
			'$thurl' => DI::l10n()->t('Blocked server domain pattern'),
			'$threason' => DI::l10n()->t('Reason for the block'),
			'$delentry' => DI::l10n()->t('Delete entry from blocklist'),
			'$entries' => $blocklistform,
			'$baseurl' => DI::baseUrl()->get(true),
			'$confirm_delete' => DI::l10n()->t('Delete entry from blocklist?'),
			'$form_security_token' => parent::getFormSecurityToken("admin_blocklist")
		]);
	}
}
