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

namespace Friendica\Module\Search;

use Friendica\App\Arguments;
use Friendica\App\BaseURL;
use Friendica\BaseModule;
use Friendica\Core\L10n;
use Friendica\Core\Search;
use Friendica\Database\Database;

class Saved extends BaseModule
{
	/** @var Arguments */
	protected $args;
	/** @var Database */
	protected $dba;
	/** @var BaseURL */
	protected $baseUrl;

	public function __construct(BaseURL $baseUrl, Database $dba, Arguments $args, L10n $l10n, array $parameters = [])
	{
		parent::__construct($l10n, $parameters);

		$this->baseUrl = $baseUrl;
		$this->dba     = $dba;
		$this->args    = $args;
	}

	public function rawContent()
	{
		$action = $this->args->get(2, 'none');
		$search = trim(rawurldecode($_GET['term'] ?? ''));

		$return_url = $_GET['return_url'] ?? Search::getSearchPath($search);

		if (local_user() && $search) {
			switch ($action) {
				case 'add':
					$fields = ['uid' => local_user(), 'term' => $search];
					if (!$this->dba->exists('search', $fields)) {
						if (!$this->dba->insert('search', $fields)) {
							notice($this->l10n->t('Search term was not saved.'));
						}
					} else {
						notice($this->l10n->t('Search term already saved.'));
					}
					break;

				case 'remove':
					if (!$this->dba->delete('search', ['uid' => local_user(), 'term' => $search])) {
						notice($this->l10n->t('Search term was not removed.'));
					}
					break;
			}
		}

		$this->baseUrl->redirect($return_url);
	}
}
