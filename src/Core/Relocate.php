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

namespace Friendica\Core;

use Friendica\App;
use Friendica\Database;
use Friendica\Util\Strings;
use Friendica\Worker\Delivery;

class Relocate
{
	/**
	 * @var App\BaseURL
	 */
	private $baseUrl;
	/**
	 * @var Database\Database
	 */
	private $database;
	/**
	 * @var Config\Capability\IManageConfigValues
	 */
	private $config;

	public function __construct(App\BaseURL $baseUrl, Database\Database $database, Config\Capability\IManageConfigValues $config)
	{
		$this->baseUrl  = $baseUrl;
		$this->database = $database;
		$this->config   = $config;
	}

	/**
	 * Performs relocation
	 *
	 * @param string $new_url The new node URL, including the scheme
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public function run(string $new_url)
	{
		$new_url = rtrim($new_url, '/');

		$parsed = @parse_url($new_url);
		if (!is_array($parsed) || empty($parsed['host']) || empty($parsed['scheme'])) {
			throw new \InvalidArgumentException('Can not parse base URL. Must have at least <scheme>://<domain>');
		}

		/* steps:
		 * replace all "baseurl" to "new_url" in config, profile, term, items and contacts
		 * send relocate for every local user
		 * */
		$old_url = $this->baseUrl->get(true);

		// Generate host names for relocation the addresses in the format user@address.tld
		$new_host = str_replace('http://', '@', Strings::normaliseLink($new_url));
		$old_host = str_replace('http://', '@', Strings::normaliseLink($old_url));

		// update tables
		// update profile links in the format "http://server.tld"
		$this->database->replaceInTableFields('profile', ['photo', 'thumb'], $old_url, $new_url);
		$this->database->replaceInTableFields('contact', ['photo', 'thumb', 'micro', 'url', 'nurl', 'alias', 'request', 'notify', 'poll', 'confirm', 'poco', 'avatar'], $old_url, $new_url);
		$this->database->replaceInTableFields('post-content', ['body'], $old_url, $new_url);

		// update profile addresses in the format "user@server.tld"
		$this->database->replaceInTableFields('contact', ['addr'], $old_host, $new_host);

		// update config
		$this->config->set('system', 'url', $new_url);
		$this->baseUrl->saveByURL($new_url);

		// send relocate
		$users = $this->database->selectToArray('user', ['uid'], ['account_removed' => false, 'account_expired' => false]);
		foreach ($users as $user) {
			Worker::add(PRIORITY_HIGH, 'Notifier', Delivery::RELOCATION, $user['uid']);
		}
	}
}
