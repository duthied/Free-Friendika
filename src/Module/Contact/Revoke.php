<?php
/**
 * @copyright Copyright (C) 2010-2021, the Friendica project
 *
 * @license   GNU AGPL version 3 or any later version
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

use Friendica\App\Arguments;
use Friendica\App\BaseURL;
use Friendica\BaseModule;
use Friendica\Content\Nav;
use Friendica\Core\L10n;
use Friendica\Core\Protocol;
use Friendica\Core\Renderer;
use Friendica\Database\Database;
use Friendica\Model;
use Friendica\Module\Contact;
use Friendica\Module\Security\Login;
use Friendica\Network\HTTPException;

class Revoke extends BaseModule
{
	/** @var array */
	protected $contact;
	
	/** @var Database */
	protected $dba;
	/** @var BaseURL */
	protected $baseUrl;
	/** @var Arguments */
	protected $args;
	
	public function __construct(Database $dba, BaseURL $baseUrl, Arguments $args, L10n $l10n, array $parameters = [])
	{
		parent::__construct($l10n, $parameters);

		$this->dba     = $dba;
		$this->baseUrl = $baseUrl;
		$this->args    = $args;

		if (!local_user()) {
			return;
		}

		$data = Model\Contact::getPublicAndUserContactID($this->parameters['id'], local_user());
		if (!$this->dba->isResult($data)) {
			throw new HTTPException\NotFoundException($this->t('Unknown contact.'));
		}

		if (empty($data['user'])) {
			throw new HTTPException\ForbiddenException();
		}

		$this->contact = Model\Contact::getById($data['user']);

		if ($this->contact['deleted']) {
			throw new HTTPException\NotFoundException($this->t('Contact is deleted.'));
		}

		if (!empty($this->contact['network']) && $this->contact['network'] == Protocol::PHANTOM) {
			throw new HTTPException\NotFoundException($this->t('Contact is being deleted.'));
		}
	}

	public function post()
	{
		if (!local_user()) {
			throw new HTTPException\UnauthorizedException();
		}

		self::checkFormSecurityTokenRedirectOnError('contact/' . $this->parameters['id'], 'contact_revoke');

		$result = Model\Contact::revokeFollow($this->contact);
		if ($result === true) {
			notice($this->t('Follow was successfully revoked.'));
		} elseif ($result === null) {
			notice($this->t('Follow was successfully revoked, however the remote contact won\'t be aware of this revokation.'));
		} else {
			notice($this->t('Unable to revoke follow, please try again later or contact the administrator.'));
		}

		$this->baseUrl->redirect('contact/' . $this->parameters['id']);
	}

	public function content(): string
	{
		if (!local_user()) {
			return Login::form($_SERVER['REQUEST_URI']);
		}

		Nav::setSelected('contact');

		return Renderer::replaceMacros(Renderer::getMarkupTemplate('contact_drop_confirm.tpl'), [
			'$l10n' => [
				'header'  => $this->t('Revoke Follow'),
				'message' => $this->t('Do you really want to revoke this contact\'s follow? This cannot be undone and they will have to manually follow you back again.'),
				'confirm' => $this->t('Yes'),
				'cancel'  => $this->t('Cancel'),
			],
			'$contact'       => Contact::getContactTemplateVars($this->contact),
			'$method'        => 'post',
			'$confirm_url'   => $this->args->getCommand(),
			'$confirm_name'  => 'form_security_token',
			'$confirm_value' => BaseModule::getFormSecurityToken('contact_revoke'),
		]);
	}
}
