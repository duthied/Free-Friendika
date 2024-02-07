<?php
/**
 * @copyright Copyright (C) 2010-2024, the Friendica project
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

namespace Friendica\Module;

use Friendica\App;
use Friendica\BaseModule;
use Friendica\Capabilities\ICanCreateResponses;
use Friendica\Core\Addon;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\L10n;
use Friendica\Model\Nodeinfo;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

/**
 * Version 1.0 of Nodeinfo, a standardized way of exposing metadata about a server running one of the distributed social networks.
 * @see https://github.com/jhass/nodeinfo/blob/master/PROTOCOL.md
 */
class NodeInfo110 extends BaseModule
{
	/** @var IManageConfigValues */
	protected $config;

	public function __construct(L10n $l10n, App\BaseURL $baseUrl, App\Arguments $args, LoggerInterface $logger, Profiler $profiler, Response $response, IManageConfigValues $config, array $server, array $parameters = [])
	{
		parent::__construct($l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		$this->config = $config;
	}

	protected function rawContent(array $request = [])
	{
		$nodeinfo = [
			'version'           => '1.0',
			'software'          => [
				'name'    => 'friendica',
				'version' => App::VERSION . '-' . DB_UPDATE_VERSION,
			],
			'protocols'         => [
				'inbound'  => [
					'friendica'
				],
				'outbound' => [
					'friendica'
				],
			],
			'services'          => Nodeinfo::getServices(),
			'usage'             => Nodeinfo::getUsage(),
			'openRegistrations' => intval($this->config->get('config', 'register_policy')) !== Register::CLOSED,
			'metadata'          => [
				'nodeName' => $this->config->get('config', 'sitename'),
			],
		];

		if (!empty($this->config->get('system', 'diaspora_enabled'))) {
			$nodeinfo['protocols']['inbound'][]  = 'diaspora';
			$nodeinfo['protocols']['outbound'][] = 'diaspora';
		}

		if (empty($this->config->get('system', 'ostatus_disabled'))) {
			$nodeinfo['protocols']['inbound'][]  = 'gnusocial';
			$nodeinfo['protocols']['outbound'][] = 'gnusocial';
		}

		$nodeinfo['metadata']['protocols']               = $nodeinfo['protocols'];
		$nodeinfo['metadata']['protocols']['outbound'][] = 'atom1.0';
		$nodeinfo['metadata']['protocols']['inbound'][]  = 'atom1.0';
		$nodeinfo['metadata']['protocols']['inbound'][]  = 'rss2.0';

		$nodeinfo['metadata']['services'] = $nodeinfo['services'];

		$nodeinfo['metadata']['explicitContent'] = $this->config->get('system', 'explicit_content', false) == true;

		$this->response->setType(ICanCreateResponses::TYPE_JSON);
		$this->response->addContent(json_encode($nodeinfo, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
	}
}
