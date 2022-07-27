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

namespace Friendica\Module\Blocklist\Domain;

use Friendica\App;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\L10n;
use Friendica\Core\System;
use Friendica\Module\Response;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

class Download extends \Friendica\BaseModule
{
	/** @var IManageConfigValues */
	private $config;

	public function __construct(IManageConfigValues $config, L10n $l10n, App\BaseURL $baseUrl, App\Arguments $args, LoggerInterface $logger, Profiler $profiler, Response $response, array $server, array $parameters = [])
	{
		parent::__construct($l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		$this->config = $config;
	}

	protected function rawContent(array $request = [])
	{
		$blocklist = $this->config->get('system', 'blocklist');

		$blocklistJson = json_encode($blocklist, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

		$hash = md5($blocklistJson);

		$etag = 'W/"' . $hash . '"';

		if (trim($_SERVER['HTTP_IF_NONE_MATCH'] ?? '') == $etag) {
			header("HTTP/1.1 304 Not Modified");
		}

		header('Content-Type: text/csv');
		header('Content-Transfer-Encoding: Binary');
		header('Content-disposition: attachment; filename="' . $this->baseUrl->getHostname() . '_domain_blocklist_' . substr($hash, 0, 6) . '.csv"');
		header("Etag: $etag");

		$fp = fopen('php://output', 'w');
		foreach ($blocklist as $domain) {
			fputcsv($fp, $domain);
		}
		fclose($fp);

		System::exit();
	}
}
