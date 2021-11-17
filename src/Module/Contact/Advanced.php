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

use Friendica\App\Page;
use Friendica\BaseModule;
use Friendica\Content\Widget;
use Friendica\Core\L10n;
use Friendica\Core\Protocol;
use Friendica\Core\Renderer;
use Friendica\Core\Session;
use Friendica\Database\Database;
use Friendica\Model;
use Friendica\Module\Contact;
use Friendica\Network\HTTPException\BadRequestException;
use Friendica\Network\HTTPException\ForbiddenException;
use Friendica\Util\Strings;
use Psr\Log\LoggerInterface;

/**
 * GUI for advanced contact details manipulation
 */
class Advanced extends BaseModule
{
	/** @var Database */
	protected $dba;
	/** @var LoggerInterface */
	protected $logger;
	/** @var Page */
	protected $page;

	public function __construct(Database $dba, LoggerInterface $logger, Page $page, L10n $l10n, array $parameters = [])
	{
		parent::__construct($l10n, $parameters);

		$this->dba    = $dba;
		$this->logger = $logger;
		$this->page   = $page;

		if (!Session::isAuthenticated()) {
			throw new ForbiddenException($this->l10n->t('Permission denied.'));
		}
	}

	public function post()
	{
		$cid = $this->parameters['id'];

		$contact = Model\Contact::selectFirst([], ['id' => $cid, 'uid' => local_user()]);
		if (empty($contact)) {
			throw new BadRequestException($this->l10n->t('Contact not found.'));
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

		$r = $this->dba->update(
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
			$this->logger->notice('Updating photo.', ['photo' => $photo]);

			Model\Contact::updateAvatar($contact['id'], $photo, true);
		}

		if (!$r) {
			notice($this->l10n->t('Contact update failed.'));
		}
	}

	public function content(): string
	{
		$cid = $this->parameters['id'];

		$contact = Model\Contact::selectFirst([], ['id' => $cid, 'uid' => local_user()]);
		if (empty($contact)) {
			throw new BadRequestException($this->l10n->t('Contact not found.'));
		}

		$this->page['aside'] = Widget\VCard::getHTML($contact);

		$warning = $this->l10n->t('<strong>WARNING: This is highly advanced</strong> and if you enter incorrect information your communications with this contact may stop working.');
		$info    = $this->l10n->t('Please use your browser \'Back\' button <strong>now</strong> if you are uncertain what to do on this page.');

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
			'$return'            => $this->l10n->t('Return to contact editor'),
			'$contact_id'        => $contact['id'],
			'$lbl_submit'        => $this->l10n->t('Submit'),

			'$name'    => ['name', $this->l10n->t('Name'), $contact['name'], '', '', $readonly],
			'$nick'    => ['nick', $this->l10n->t('Account Nickname'), $contact['nick'], '', '', $readonly],
			'$attag'   => ['attag', $this->l10n->t('@Tagname - overrides Name/Nickname'), $contact['attag']],
			'$url'     => ['url', $this->l10n->t('Account URL'), $contact['url'], '', '', $readonly],
			'$alias'   => ['alias', $this->l10n->t('Account URL Alias'), $contact['alias'], '', '', $readonly],
			'$request' => ['request', $this->l10n->t('Friend Request URL'), $contact['request'], '', '', $readonly],
			'confirm'  => ['confirm', $this->l10n->t('Friend Confirm URL'), $contact['confirm'], '', '', $readonly],
			'notify'   => ['notify', $this->l10n->t('Notification Endpoint URL'), $contact['notify'], '', '', $readonly],
			'poll'     => ['poll', $this->l10n->t('Poll/Feed URL'), $contact['poll'], '', '', $readonly],
			'photo'    => ['photo', $this->l10n->t('New photo from this URL'), '', '', '', $readonly],
		]);
	}
}
