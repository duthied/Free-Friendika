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
 * ApiTest class.
 */

namespace Friendica\Test\legacy;

use Friendica\App;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\DI;
use Friendica\Module\BaseApi;
use Friendica\Security\BasicAuth;
use Friendica\Test\FixtureTest;
use Friendica\Util\Arrays;
use Friendica\Util\DateTimeFormat;
use Monolog\Handler\TestHandler;

/**
 * Tests for the API functions.
 *
 * Functions that use header() need to be tested in a separate process.
 * @see https://phpunit.de/manual/5.7/en/appendixes.annotations.html#appendixes.annotations.runTestsInSeparateProcesses
 *
 * @backupGlobals enabled
 */
class ApiTest extends FixtureTest
{
	/**
	 * @var TestHandler Can handle log-outputs
	 */
	protected $logOutput;

	/** @var array */
	protected $selfUser;
	/** @var array */
	protected $friendUser;
	/** @var array */
	protected $otherUser;

	protected $wrongUserId;

	/** @var App */
	protected $app;

	/** @var IManageConfigValues */
	protected $config;

	/**
	 * Create variables used by tests.
	 */
	protected function setUp() : void
	{
		global $API, $called_api;
		$API = [];
		$called_api = [];

		parent::setUp();

		/** @var IManageConfigValues $config */
		$this->config = $this->dice->create(IManageConfigValues::class);

		$this->config->set('system', 'url', 'http://localhost');
		$this->config->set('system', 'hostname', 'localhost');
		$this->config->set('system', 'worker_dont_fork', true);

		// Default config
		$this->config->set('config', 'hostname', 'localhost');
		$this->config->set('system', 'throttle_limit_day', 100);
		$this->config->set('system', 'throttle_limit_week', 100);
		$this->config->set('system', 'throttle_limit_month', 100);
		$this->config->set('system', 'theme', 'system_theme');


		/** @var App app */
		$this->app = DI::app();

		DI::args()->setArgc(1);

		// User data that the test database is populated with
		$this->selfUser   = [
			'id'   => 42,
			'name' => 'Self contact',
			'nick' => 'selfcontact',
			'nurl' => 'http://localhost/profile/selfcontact'
		];
		$this->friendUser = [
			'id'   => 44,
			'name' => 'Friend contact',
			'nick' => 'friendcontact',
			'nurl' => 'http://localhost/profile/friendcontact'
		];
		$this->otherUser  = [
			'id'   => 43,
			'name' => 'othercontact',
			'nick' => 'othercontact',
			'nurl' => 'http://localhost/profile/othercontact'
		];

		// User ID that we know is not in the database
		$this->wrongUserId = 666;

		DI::session()->start();

		// Most API require login so we force the session
		$_SESSION = [
			'authenticated' => true,
			'uid'           => $this->selfUser['id']
		];
		BasicAuth::setCurrentUserID($this->selfUser['id']);
	}

	/**
	 * Test the api_user() function.
	 *
	 * @return void
	 */
	public function testApiUser()
	{
		self::assertEquals($this->selfUser['id'], BaseApi::getCurrentUserID());
	}



	/**
	 * Test the api_source() function.
	 *
	 * @return void
	 */
	public function testApiSource()
	{
		self::assertEquals('api', BasicAuth::getCurrentApplicationToken()['name']);
	}

	/**
	 * Test the api_source() function with a Twidere user agent.
	 *
	 * @return void
	 */
	public function testApiSourceWithTwidere()
	{
		$_SERVER['HTTP_USER_AGENT'] = 'Twidere';
		self::assertEquals('Twidere', BasicAuth::getCurrentApplicationToken()['name']);
	}

	/**
	 * Test the api_source() function with a GET parameter.
	 *
	 * @return void
	 */
	public function testApiSourceWithGet()
	{
		$_REQUEST['source'] = 'source_name';
		self::assertEquals('source_name', BasicAuth::getCurrentApplicationToken()['name']);
	}

	/**
	 * Test the api_date() function.
	 *
	 * @return void
	 */
	public function testApiDate()
	{
		self::assertEquals('Wed Oct 10 00:00:00 +0000 1990', DateTimeFormat::utc('1990-10-10', DateTimeFormat::API));
	}

	/**
	 * Test the BasicAuth::getCurrentUserID() function without any login.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 * @preserveGlobalState disabled
	 */
	public function testApiLoginWithoutLogin()
	{
		BasicAuth::setCurrentUserID();
		$this->expectException(\Friendica\Network\HTTPException\UnauthorizedException::class);
		BasicAuth::getCurrentUserID(true);
	}

	/**
	 * Test the BasicAuth::getCurrentUserID() function with a bad login.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 * @preserveGlobalState disabled
	 */
	public function testApiLoginWithBadLogin()
	{
		BasicAuth::setCurrentUserID();
		$this->expectException(\Friendica\Network\HTTPException\UnauthorizedException::class);
		$_SERVER['PHP_AUTH_USER'] = 'user@server';
		BasicAuth::getCurrentUserID(true);
	}

	/**
	 * Test the BasicAuth::getCurrentUserID() function with oAuth.
	 *
	 * @return void
	 */
	public function testApiLoginWithOauth()
	{
		$this->markTestIncomplete('Can we test this easily?');
	}

	/**
	 * Test the BasicAuth::getCurrentUserID() function with authentication provided by an addon.
	 *
	 * @return void
	 */
	public function testApiLoginWithAddonAuth()
	{
		$this->markTestIncomplete('Can we test this easily?');
	}

	/**
	 * Test the BasicAuth::getCurrentUserID() function with a correct login.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 * @doesNotPerformAssertions
	 */
	public function testApiLoginWithCorrectLogin()
	{
		BasicAuth::setCurrentUserID();
		$_SERVER['PHP_AUTH_USER'] = 'Test user';
		$_SERVER['PHP_AUTH_PW']   = 'password';
		BasicAuth::getCurrentUserID(true);
	}

	/**
	 * Test the BasicAuth::getCurrentUserID() function with a remote user.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function testApiLoginWithRemoteUser()
	{
		BasicAuth::setCurrentUserID();
		$this->expectException(\Friendica\Network\HTTPException\UnauthorizedException::class);
		$_SERVER['REDIRECT_REMOTE_USER'] = '123456dXNlcjpwYXNzd29yZA==';
		BasicAuth::getCurrentUserID(true);
	}

	/**
	 * Test the Arrays::walkRecursive() function.
	 *
	 * @return void
	 */
	public function testApiWalkRecursive()
	{
		$array = ['item1'];
		self::assertEquals(
			$array,
			Arrays::walkRecursive(
				$array,
				function () {
					// Should we test this with a callback that actually does something?
					return true;
				}
			)
		);
	}

	/**
	 * Test the Arrays::walkRecursive() function with an array.
	 *
	 * @return void
	 */
	public function testApiWalkRecursiveWithArray()
	{
		$array = [['item1'], ['item2']];
		self::assertEquals(
			$array,
			Arrays::walkRecursive(
				$array,
				function () {
					// Should we test this with a callback that actually does something?
					return true;
				}
			)
		);
	}
}
