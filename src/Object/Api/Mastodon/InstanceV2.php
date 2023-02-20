<?php
/**
 * @copyright Copyright (C) 2010-2023, the Friendica project
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

namespace Friendica\Object\Api\Mastodon;

use Friendica\App;
use Friendica\App\BaseURL;
use Friendica\BaseDataTransferObject;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Database\Database;
use Friendica\Network\HTTPException;
use Friendica\Object\Api\Mastodon\InstanceV2\Configuration;
use Friendica\Object\Api\Mastodon\InstanceV2\Contact;
use Friendica\Object\Api\Mastodon\InstanceV2\FriendicaExtensions;
use Friendica\Object\Api\Mastodon\InstanceV2\Registrations;
use Friendica\Object\Api\Mastodon\InstanceV2\Thumbnail;
use Friendica\Object\Api\Mastodon\InstanceV2\Usage;

/**
 * Class Instance
 *
 * @see https://docs.joinmastodon.org/entities/Instance/
 */
class InstanceV2 extends BaseDataTransferObject
{
	/** @var string */
	protected $domain;
	/** @var string */
	protected $title;
	/** @var string */
	protected $version;
	/** @var string */
	protected $source_url;
	/** @var string */
	protected $description;
	/** @var Usage */
	protected $usage;
	/** @var Thumbnail */
	protected $thumbnail;
	/** @var array */
	protected $languages;
	/** @var Configuration  */
	protected $configuration;
	/** @var Registrations */
	protected $registrations;
	/** @var Contact */
	protected $contact;
	/** @var array */
	protected $rules = [];
	/** @var FriendicaExtensions */
	protected $friendica;

	/**
	 * @param IManageConfigValues $config
	 * @param BaseURL             $baseUrl
	 * @param Database            $database
	 * @param array               $rules
	 * @throws HTTPException\InternalServerErrorException
	 * @throws HTTPException\NotFoundException
	 * @throws \ImagickException
	 */
	public function __construct(IManageConfigValues $config, BaseURL $baseUrl, Database $database, array $rules = [])
	{
		$this->domain        = $baseUrl->getHostname();
		$this->title         = $config->get('config', 'sitename');
		$this->version       = '2.8.0 (compatible; Friendica ' . App::VERSION . ')';
		$this->source_url	 = null; //not supported yet
		$this->description   = $config->get('config', 'info');
		$this->usage         = new Usage($config);
		$this->thumbnail     = new Thumbnail($baseUrl);
		$this->languages     = [$config->get('system', 'language')];
		$this->configuration = new Configuration();
		$this->registrations = new Registrations();
		$this->contact       = new Contact($database);
		$this->rules         = $rules;
		$this->friendica     = new FriendicaExtensions();
	}
}
