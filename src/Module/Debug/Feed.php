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

namespace Friendica\Module\Debug;

use Friendica\App\BaseURL;
use Friendica\BaseModule;
use Friendica\Core\L10n;
use Friendica\Core\Renderer;
use Friendica\Model;
use Friendica\Network\HTTPClient\Capability\ICanSendHttpRequests;
use Friendica\Protocol;

/**
 * Tests a given feed of a contact
 */
class Feed extends BaseModule
{
	/** @var ICanSendHttpRequests */
	protected $httpClient;

	public function __construct(BaseURL $baseUrl, ICanSendHttpRequests $httpClient, L10n $l10n, array $parameters = [])
	{
		parent::__construct($l10n, $parameters);

		$this->httpClient = $httpClient;

		if (!local_user()) {
			notice($this->l10n->t('You must be logged in to use this module'));
			$baseUrl->redirect();
		}
	}

	public function content(): string
	{
		$result = [];
		if (!empty($_REQUEST['url'])) {
			$url = $_REQUEST['url'];

			$contact = Model\Contact::getByURLForUser($url, local_user(), null);

			$xml = $this->httpClient->fetch($contact['poll']);

			$import_result = Protocol\Feed::import($xml);

			$result = [
				'input' => $xml,
				'output' => var_export($import_result, true),
			];
		}

		$tpl = Renderer::getMarkupTemplate('feedtest.tpl');
		return Renderer::replaceMacros($tpl, [
			'$url'    => ['url', $this->l10n->t('Source URL'), $_REQUEST['url'] ?? '', ''],
			'$result' => $result
		]);
	}
}
