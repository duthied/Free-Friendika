<?php
/**
 * @copyright Copyright (C) 2020, Friendica
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

namespace Friendica\Test\src\Util\Emailer;

use Friendica\App\BaseURL;
use Friendica\Core\Config\IConfig;
use Friendica\Core\L10n;
use Friendica\Test\MockedTest;
use Friendica\Test\Util\VFSTrait;
use Friendica\Util\EMailer\MailBuilder;
use Friendica\Util\EMailer\SystemMailBuilder;
use Psr\Log\NullLogger;

class SystemMailBuilderTest extends MockedTest
{
	use VFSTrait;

	/** @var IConfig */
	private $config;
	/** @var L10n */
	private $l10n;
	/** @var BaseURL */
	private $baseUrl;

	/** @var string */
	private $defaultHeaders;

	public function setUp()
	{
		parent::setUp();

		$this->setUpVfsDir();

		$this->config  = \Mockery::mock(IConfig::class);
		$this->config->shouldReceive('get')->with('config', 'admin_name')->andReturn('Admin');
		$this->l10n    = \Mockery::mock(L10n::class);
		$this->l10n->shouldReceive('t')->andReturnUsing(function ($msg) {
			return $msg;
		});
		$this->baseUrl = \Mockery::mock(BaseURL::class);
		$this->baseUrl->shouldReceive('getHostname')->andReturn('friendica.local');
		$this->baseUrl->shouldReceive('get')->andReturn('http://friendica.local');

		$this->defaultHeaders = "";
	}

	/**
	 * Test if the builder instance can get created
	 */
	public function testBuilderInstance()
	{
		$builder = new SystemMailBuilder($this->l10n, $this->baseUrl, $this->config, new NullLogger(), 'moreply@friendica.local', 'FriendicaSite');

		$this->assertInstanceOf(MailBuilder::class, $builder);
		$this->assertInstanceOf(SystemMailBuilder::class, $builder);
	}
}
