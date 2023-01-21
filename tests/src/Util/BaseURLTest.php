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

namespace Friendica\Test\src\Util;

use Friendica\App\BaseURL;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\Config\Model\Config;
use Friendica\Core\Config\Util\ConfigFileManager;
use Friendica\Core\Config\ValueObject\Cache;
use Friendica\Test\MockedTest;
use Friendica\Test\Util\VFSTrait;

class BaseURLTest extends MockedTest
{
	use VFSTrait;

	protected function setUp(): void
	{
		parent::setUp();

		$this->setUpVfsDir();
	}

	public function dataDefault()
	{
		return [
			'null' => [
				'server' => [],
				'input' => [
				'hostname' => null,
				'urlPath' => null,
				'sslPolicy' => null,
				'url' => null,
					],
				'assert' => [
					'hostname'  => '',
					'urlPath'   => '',
					'sslPolicy' => BaseURL::DEFAULT_SSL_SCHEME,
					'url'       => 'http://',
					'scheme'    => 'http',
				],
			],
			'WithSubDirectory' => [
				'server' => [
					'SERVER_NAME'  => 'friendica.local',
					'REDIRECT_URI' => 'test/module/more',
					'QUERY_STRING' => 'module/more',
				],
				'input' => [
					'hostname'  => null,
					'urlPath'   => null,
					'sslPolicy' => null,
					'url'       => null,
				],
				'assert' => [
					'hostname'  => 'friendica.local',
					'urlPath'   => 'test',
					'sslPolicy' => BaseURL::DEFAULT_SSL_SCHEME,
					'url'       => 'http://friendica.local/test',
					'scheme'    => 'http',
				],
			],
			'input' => [
				'server' => [],
				'input' => [
					'hostname'  => 'friendica.local',
					'urlPath'   => 'test',
					'sslPolicy' => BaseURL::SSL_POLICY_FULL,
					'url'       => 'http://friendica.local/test',
				],
				'assert' => [
					'hostname'  => 'friendica.local',
					'urlPath'   => 'test',
					'sslPolicy' => BaseURL::SSL_POLICY_FULL,
					'url'       => 'http://friendica.local/test',
					'scheme'    => 'http',
				],
			],
			'WithHttpsScheme' => [
				'server' => [
					'SERVER_NAME'    => 'friendica.local',
					'REDIRECT_URI'   => 'test/module/more',
					'QUERY_STRING'   => 'module/more',
					'HTTPS'          => true,
				],
				'input' => [
					'hostname'  => null,
					'urlPath'   => null,
					'sslPolicy' => null,
					'url'       => null,
				],
				'assert' => [
					'hostname'  => 'friendica.local',
					'urlPath'   => 'test',
					'sslPolicy' => BaseURL::SSL_POLICY_FULL,
					'url'       => 'https://friendica.local/test',
					'scheme'    => 'https',
				],
			],
			'WithoutQueryString' => [
				'server' => [
					'SERVER_NAME'    => 'friendica.local',
					'REDIRECT_URI'   => 'test/more',
					'HTTPS'          => true,
				],
				'input' => [
					'hostname'  => null,
					'urlPath'   => null,
					'sslPolicy' => null,
					'url'       => null,
				],
				'assert' => [
					'hostname'  => 'friendica.local',
					'urlPath'   => 'test/more',
					'sslPolicy' => BaseURL::SSL_POLICY_FULL,
					'url'       => 'https://friendica.local/test/more',
					'scheme'    => 'https',
				],
			],
			'WithPort' => [
				'server' => [
					'SERVER_NAME'    => 'friendica.local',
					'SERVER_PORT'    => '1234',
					'REDIRECT_URI'   => 'test/more',
					'HTTPS'          => true,
				],
				'input' => [
					'hostname'  => null,
					'urlPath'   => null,
					'sslPolicy' => null,
					'url'       => null,
				],
				'assert' => [
					'hostname'  => 'friendica.local:1234',
					'urlPath'   => 'test/more',
					'sslPolicy' => BaseURL::SSL_POLICY_FULL,
					'url'       => 'https://friendica.local:1234/test/more',
					'scheme'    => 'https',
				],
			],
			'With443Port' => [
				'server' => [
					'SERVER_NAME'    => 'friendica.local',
					'SERVER_PORT'    => '443',
					'REDIRECT_URI'   => 'test/more',
				],
				'input' => [
					'hostname'  => null,
					'urlPath'   => null,
					'sslPolicy' => null,
					'url'       => null,
				],
				'assert' => [
					'hostname'  => 'friendica.local',
					'urlPath'   => 'test/more',
					'sslPolicy' => BaseURL::SSL_POLICY_FULL,
					'url'       => 'https://friendica.local/test/more',
					'scheme'    => 'https',
				],
			],
			'With80Port' => [
				'server' => [
					'SERVER_NAME'  => 'friendica.local',
					'SERVER_PORT'  => '80',
					'REDIRECT_URI' => 'test/more',
				],
				'input' => [
					'hostname'  => null,
					'urlPath'   => null,
					'sslPolicy' => null,
					'url'       => null,
				],
				'assert' => [
					'hostname'  => 'friendica.local',
					'urlPath'   => 'test/more',
					'sslPolicy' => BaseURL::DEFAULT_SSL_SCHEME,
					'url'       => 'http://friendica.local/test/more',
					'scheme'    => 'http',
				],
			],
		];
	}

	public function dataSave()
	{
		return [
			'no_change' => [
				'input' => [
					'hostname'  => 'friendica.local',
					'urlPath'   => 'path',
					'sslPolicy' => BaseURL::SSL_POLICY_FULL,
					'url'       => 'https://friendica.local/path',
					'force_ssl' => true,
				],
				'save' => [
					'hostname'  => 'friendica.local',
					'urlPath'   => 'path',
					'sslPolicy' => BaseURL::SSL_POLICY_FULL,
				],
				'url' => 'https://friendica.local/path',
			],
			'default' => [
				'input' => [
					'hostname'  => 'friendica.old',
					'urlPath'   => 'is/old/path',
					'sslPolicy' => BaseURL::DEFAULT_SSL_SCHEME,
					'url'       => 'http://friendica.old/is/old/path',
					'force_ssl' => true,
				],
				'save' => [
					'hostname'  => 'friendica.local',
					'urlPath'   => 'new/path',
					'sslPolicy' => BaseURL::SSL_POLICY_FULL,
				],
				'url' => 'https://friendica.local/new/path',
			],
			'null' => [
				'input' => [
					'hostname'  => 'friendica.old',
					'urlPath'   => 'is/old/path',
					'sslPolicy' => BaseURL::DEFAULT_SSL_SCHEME,
					'url'       => 'http://friendica.old/is/old/path',
					'force_ssl' => true,
				],
				'save' => [
					'hostname'  => null,
					'urlPath'   => null,
					'sslPolicy' => null,
				],
				'url' => 'http://friendica.old/is/old/path',
			],
			'changeHostname' => [
				'input' => [
					'hostname'  => 'friendica.old',
					'urlPath'   => 'is/old/path',
					'sslPolicy' => BaseURL::DEFAULT_SSL_SCHEME,
					'url'       => 'http://friendica.old/is/old/path',
					'force_ssl' => true,
				],
				'save' => [
					'hostname'  => 'friendica.local',
					'urlPath'   => null,
					'sslPolicy' => null,
				],
				'url' => 'http://friendica.local/is/old/path',
			],
			'changeUrlPath' => [
				'input' => [
					'hostname'  => 'friendica.old',
					'urlPath'   => 'is/old/path',
					'sslPolicy' => BaseURL::DEFAULT_SSL_SCHEME,
					'url'       => 'http://friendica.old/is/old/path',
					'force_ssl' => true,
				],
				'save' => [
					'hostname'  => null,
					'urlPath'   => 'new/path',
					'sslPolicy' => null,
				],
				'url' => 'http://friendica.old/new/path',
			],
			'changeSSLPolicy' => [
				'input' => [
					'hostname'  => 'friendica.old',
					'urlPath'   => 'is/old/path',
					'sslPolicy' => BaseURL::DEFAULT_SSL_SCHEME,
					'url'       => 'http://friendica.old/is/old/path',
					'force_ssl' => true,
				],
				'save' => [
					'hostname'  => null,
					'urlPath'   => null,
					'sslPolicy' => BaseURL::SSL_POLICY_FULL,
				],
				'url' => 'https://friendica.old/is/old/path',
			],
		];
	}

	/**
	 * Test the save() method
	 * @dataProvider dataSave
	 */
	public function testSave($input, $save, $url)
	{
		$configFileManager = new ConfigFileManager($this->root->url(), $this->root->url() . '/config/', $this->root->url() . '/static/');
		$config = new Config($configFileManager, new Cache([
			'config' => [
				'hostname' => $input['hostname'] ?? null,
			],
			'system' => [
				'urlpath' => $input['urlPath'] ?? null,
				'ssl_policy' => $input['sslPolicy'] ?? null,
				'url' => $input['url'] ?? null,
				'force_ssl' => $input['force_ssl'] ?? null,
			],
		]));

		$baseUrl = new BaseURL($config, []);

		$baseUrl->save($save['hostname'], $save['sslPolicy'], $save['urlPath']);

		self::assertEquals($url, $baseUrl->get());
	}

	/**
	 * Test the saveByUrl() method
	 * @dataProvider dataSave
	 *
	 * @param $input
	 * @param $save
	 * @param $url
	 */
	public function testSaveByUrl($input, $save, $url)
	{
		$configFileManager = new ConfigFileManager($this->root->url(), $this->root->url() . '/config/', $this->root->url() . '/static/');
		$config = new Config($configFileManager, new Cache([
			'config' => [
				'hostname' => $input['hostname'] ?? null,
			],
			'system' => [
				'urlpath' => $input['urlPath'] ?? null,
				'ssl_policy' => $input['sslPolicy'] ?? null,
				'url' => $input['url'] ?? null,
				'force_ssl' => $input['force_ssl'] ?? null,
			],
		]));

		$baseUrl = new BaseURL($config, []);

		$baseUrl->saveByURL($url);

		self::assertEquals($url, $baseUrl->get());
	}

	public function dataGetBaseUrl()
	{
		return [
			'default'           => [
				'sslPolicy' => BaseURL::DEFAULT_SSL_SCHEME,
				'ssl'       => false,
				'url'       => 'http://friendica.local/new/test',
				'assert'    => 'http://friendica.local/new/test',
			],
			'DefaultWithSSL'    => [
				'sslPolicy' => BaseURL::DEFAULT_SSL_SCHEME,
				'ssl'       => true,
				'url'       => 'http://friendica.local/new/test',
				'assert'    => 'https://friendica.local/new/test',
			],
			'SSLFullWithSSL'    => [
				'sslPolicy' => BaseURL::SSL_POLICY_FULL,
				'ssl'       => true,
				'url'       => 'http://friendica.local/new/test',
				'assert'    => 'http://friendica.local/new/test',
			],
			'SSLFullWithoutSSL' => [
				'sslPolicy' => BaseURL::SSL_POLICY_FULL,
				'ssl'       => false,
				'url'       => 'https://friendica.local/new/test',
				'assert'    => 'https://friendica.local/new/test',
			],
			'NoSSLWithSSL'      => [
				'sslPolicy' => BaseURL::SSL_POLICY_NONE,
				'ssl'       => true,
				'url'       => 'http://friendica.local/new/test',
				'assert'    => 'http://friendica.local/new/test',
			],
			'NoSSLWithoutSSL'   => [
				'sslPolicy' => BaseURL::SSL_POLICY_NONE,
				'ssl'       => false,
				'url'       => 'http://friendica.local/new/test',
				'assert'    => 'http://friendica.local/new/test',
			],
		];
	}

	/**
	 * Test the get() method
	 * @dataProvider dataGetBaseUrl
	 */
	public function testGetURL($sslPolicy, $ssl, $url, $assert)
	{
		$configMock = \Mockery::mock(IManageConfigValues::class);
		$configMock->shouldReceive('get')->with('config', 'hostname')->andReturn('friendica.local');
		$configMock->shouldReceive('get')->with('system', 'urlpath')->andReturn('new/test');
		$configMock->shouldReceive('get')->with('system', 'ssl_policy')->andReturn($sslPolicy);
		$configMock->shouldReceive('get')->with('system', 'url')->andReturn($url);

		$baseUrl = new BaseURL($configMock, []);

		self::assertEquals($assert, $baseUrl->get($ssl));
	}

	public function dataCheckRedirectHTTPS()
	{
		return [
			'default' => [
				'server' => [
					'REQUEST_METHOD' => 'GET',
					'HTTPS' => true,
				],
				'forceSSL'  => false,
				'sslPolicy' => BaseURL::DEFAULT_SSL_SCHEME,
				'url'       => 'https://friendica.local',
				'redirect'  => false,
			],
			'forceSSL' => [
				'server' => [
					'REQUEST_METHOD' => 'GET',
				],
				'forceSSL'  => true,
				'sslPolicy' => BaseURL::DEFAULT_SSL_SCHEME,
				'url'       => 'https://friendica.local',
				'redirect'  => false,
			],
			'forceSSLWithSSLPolicy' => [
				'server' => [],
				'forceSSL'  => true,
				'sslPolicy' => BaseURL::SSL_POLICY_FULL,
				'url'       => 'https://friendica.local',
				'redirect'  => false,
			],
			'forceSSLWithSSLPolicyAndGet' => [
				'server' => [
					'REQUEST_METHOD' => 'GET',
				],
				'forceSSL'  => true,
				'sslPolicy' => BaseURL::SSL_POLICY_FULL,
				'url'       => 'https://friendica.local',
				'redirect'  => true,
			],
		];
	}

	/**
	 * Test the checkRedirectHTTPS() method
	 * @dataProvider dataCheckRedirectHTTPS
	 */
	public function testCheckRedirectHTTPS($server, $forceSSL, $sslPolicy, $url, $redirect)
	{
		$configMock = \Mockery::mock(IManageConfigValues::class);
		$configMock->shouldReceive('get')->with('config', 'hostname')->andReturn('friendica.local');
		$configMock->shouldReceive('get')->with('system', 'urlpath')->andReturn('new/test');
		$configMock->shouldReceive('get')->with('system', 'ssl_policy')->andReturn($sslPolicy);
		$configMock->shouldReceive('get')->with('system', 'url')->andReturn($url);
		$configMock->shouldReceive('get')->with('system', 'force_ssl')->andReturn($forceSSL);

		$baseUrl = new BaseURL($configMock, $server);

		self::assertEquals($redirect, $baseUrl->checkRedirectHttps());
	}
}
