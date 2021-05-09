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

namespace Friendica\Module\Contact;

use Friendica\BaseModule;
use Friendica\Core\Protocol;
use Friendica\Core\Renderer;
use Friendica\Core\Session;
use Friendica\DI;
use Friendica\Model;
use Friendica\Module\Contact;
use Friendica\Network\HTTPException\BadRequestException;
use Friendica\Network\HTTPException\ForbiddenException;
use Friendica\Util\Strings;

/**
 * GUI for advanced contact details manipulation
 */
class Advanced extends BaseModule
{
	public static function init(array $parameters = [])
	{
		if (!Session::isAuthenticated()) {
			throw new ForbiddenException(DI::l10n()->t('Permission denied.'));
		}
	}

	public static function post(array $parameters = [])
	{
		$cid = $parameters['id'];

		$contact = Model\Contact::selectFirst([], ['id' => $cid, 'uid' => local_user()]);
		if (empty($contact)) {
			throw new BadRequestException(DI::l10n()->t('Contact not found.'));
		}

		$name        = ($_POST['name'] ?? '') ?: $contact['name'];
		$nick        = $_POST['nick'] ?? '';
		$url         = $_POST['url'] ?? '';
		$alias       = $_POST['alias'] ?? '';
		$request     = $_POST['request'] ?? '';
		$confirm     = $_POST['confirm'] ?? '';
		$notify      = $_POST['notify'] ?? '';
		$poll        = $_POST['poll'] ?? '';
		$attag       = $_POST['attag'] ?? '';
		$photo       = $_POST['photo'] ?? '';
		$nurl        = Strings::normaliseLink($url);

		$r = DI::dba()->update(
			'contact',
			[
				'name'        => $name,
				'nick'        => $nick,
				'url'         => $url,
				'nurl'        => $nurl,
				'alias'       => $alias,
				'request'     => $request,
				'confirm'     => $confirm,
				'notify'      => $notify,
				'poll'        => $poll,
				'attag'       => $attag,
			],
			['id' => $contact['id'], 'uid' => local_user()]
		);

		if ($photo) {
			DI::logger()->notice('Updating photo.', ['photo' => $photo]);

			Model\Contact::updateAvatar($contact['id'], $photo, true);
		}

		if (!$r) {
			notice(DI::l10n()->t('Contact update failed.'));
		}

		return;
	}

	public static function content(array $parameters = [])
	{
		$cid = $parameters['id'];

		$contact = Model\Contact::selectFirst([], ['id' => $cid, 'uid' => local_user()]);
		if (empty($contact)) {
			throw new BadRequestException(DI::l10n()->t('Contact not found.'));
		}

		Model\Profile::load(DI::app(), "", Model\Contact::getByURL($contact["url"], false));

		$warning = DI::l10n()->t('<strong>WARNING: This is highly advanced</strong> and if you enter incorrect information your communications with this contact may stop working.');
		$info    = DI::l10n()->t('Please use your browser \'Back\' button <strong>now</strong> if you are uncertain what to do on this page.');

		$returnaddr = "contact/$cid";

		// This data is fetched automatically for most networks.
		// Editing does only makes sense for mail and feed contacts.
		if (!in_array($contact['network'], [Protocol::FEED, Protocol::MAIL])) {
			$readonly = 'readonly';
		} else {
			$readonly = '';
		}

		$tab_str = Contact::getTabsHTML($contact, Contact::TAB_ADVANCED);

		$tpl = Renderer::getMarkupTemplate('contact/advanced.tpl');
		return Renderer::replaceMacros($tpl, [
			'$tab_str'           => $tab_str,
			'$warning'           => $warning,
			'$info'              => $info,
			'$returnaddr'        => $returnaddr,
			'$return'            => DI::l10n()->t('Return to contact editor'),
			'$contact_id'        => $contact['id'],
			'$lbl_submit'        => DI::l10n()->t('Submit'),

			'$name'    => ['name', DI::l10n()->t('Name'), $contact['name'], '', '', $readonly],
			'$nick'    => ['nick', DI::l10n()->t('Account Nickname'), $contact['nick'], '', '', $readonly],
			'$attag'   => ['attag', DI::l10n()->t('@Tagname - overrides Name/Nickname'), $contact['attag']],
			'$url'     => ['url', DI::l10n()->t('Account URL'), $contact['url'], '', '', $readonly],
			'$alias'   => ['alias', DI::l10n()->t('Account URL Alias'), $contact['alias'], '', '', $readonly],
			'$request' => ['request', DI::l10n()->t('Friend Request URL'), $contact['request'], '', '', $readonly],
			'confirm'  => ['confirm', DI::l10n()->t('Friend Confirm URL'), $contact['confirm'], '', '', $readonly],
			'notify'   => ['notify', DI::l10n()->t('Notification Endpoint URL'), $contact['notify'], '', '', $readonly],
			'poll'     => ['poll', DI::l10n()->t('Poll/Feed URL'), $contact['poll'], '', '', $readonly],
			'photo'    => ['photo', DI::l10n()->t('New photo from this URL'), '', '', '', $readonly],
		]);
	}
}
