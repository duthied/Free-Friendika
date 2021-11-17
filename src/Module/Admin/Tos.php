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

namespace Friendica\Module\Admin;

use Friendica\App\BaseURL;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\L10n;
use Friendica\Core\Renderer;
use Friendica\Module\BaseAdmin;

class Tos extends BaseAdmin
{
	/** @var \Friendica\Module\Tos */
	protected $tos;
	/** @var IManageConfigValues */
	protected $config;
	/** @var BaseURL */
	protected $baseUrl;

	public function __construct(\Friendica\Module\Tos $tos, IManageConfigValues $config, BaseURL $baseUrl, L10n $l10n, array $parameters = [])
	{
		parent::__construct($l10n, $parameters);

		$this->tos     = $tos;
		$this->config  = $config;
		$this->baseUrl = $baseUrl;
	}

	public function post()
	{
		self::checkAdminAccess();

		if (empty($_POST['page_tos'])) {
			return;
		}

		self::checkFormSecurityTokenRedirectOnError('/admin/tos', 'admin_tos');

		$displaytos = !empty($_POST['displaytos']);
		$displayprivstatement = !empty($_POST['displayprivstatement']);
		$tostext = (!empty($_POST['tostext']) ? strip_tags(trim($_POST['tostext'])) : '');

		$this->config->set('system', 'tosdisplay', $displaytos);
		$this->config->set('system', 'tosprivstatement', $displayprivstatement);
		$this->config->set('system', 'tostext', $tostext);

		$this->baseUrl->redirect('admin/tos');
	}

	public function content(): string
	{
		parent::content();

		$t = Renderer::getMarkupTemplate('admin/tos.tpl');
		return Renderer::replaceMacros($t, [
			'$title' => $this->l10n->t('Administration'),
			'$page' => $this->l10n->t('Terms of Service'),
			'$displaytos' => ['displaytos', $this->l10n->t('Display Terms of Service'), $this->config->get('system', 'tosdisplay'), $this->l10n->t('Enable the Terms of Service page. If this is enabled a link to the terms will be added to the registration form and the general information page.')],
			'$displayprivstatement' => ['displayprivstatement', $this->l10n->t('Display Privacy Statement'), $this->config->get('system', 'tosprivstatement'), $this->l10n->t('Show some informations regarding the needed information to operate the node according e.g. to <a href="%s" target="_blank" rel="noopener noreferrer">EU-GDPR</a>.', 'https://en.wikipedia.org/wiki/General_Data_Protection_Regulation')],
			'$preview' => $this->l10n->t('Privacy Statement Preview'),
			'$privtext' => $this->tos->privacy_complete,
			'$tostext' => ['tostext', $this->l10n->t('The Terms of Service'), $this->config->get('system', 'tostext'), $this->l10n->t('Enter the Terms of Service for your node here. You can use BBCode. Headers of sections should be [h2] and below.')],
			'$form_security_token' => self::getFormSecurityToken('admin_tos'),
			'$submit' => $this->l10n->t('Save Settings'),
		]);
	}
}
