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

namespace Friendica\Test\src\Model\User;

use Friendica\App\BaseURL;
use Friendica\Core\Config\IConfig;
use Friendica\Model\User\Cookie;
use Friendica\Test\MockedTest;
use Friendica\Test\Util\StaticCookie;
use Mockery\MockInterface;

class CookieTest extends MockedTest
{
	/** @var MockInterface|IConfig */
	private $config;
	/** @var MockInterface|BaseURL */
	private $baseUrl;

	protected function setUp()
	{
		StaticCookie::clearStatic();

		parent::setUp();

		$this->config = \Mockery::mock(IConfig::class);
		$this->baseUrl = \Mockery::mock(BaseURL::class);
	}

	protected function tearDown()
	{
		StaticCookie::clearStatic();
	}

	/**
	 * Test if we can create a basic cookie instance
	 */
	public function testInstance()
	{
		$this->baseUrl->shouldReceive('getSSLPolicy')->andReturn(true)->once();
		$this->config->shouldReceive('get')->with('system', 'site_prvkey')->andReturn('1235')->once();
		$this->config->shouldReceive('get')->with('system', 'auth_cookie_lifetime', Cookie::DEFAULT_EXPIRE)->andReturn('7')->once();

		$cookie = new Cookie($this->config, $this->baseUrl);
		self::assertInstanceOf(Cookie::class, $cookie);
	}

	public function dataGet()
	{
		return [
			'default'    => [
				'cookieData' => [
					Cookie::NAME => json_encode([
						'uid'  => -1,
						'hash' => 12345,
						'ip'   => '127.0.0.1',
					])
				],
				'hasValues'  => true,
				'uid'        => -1,
				'hash'       => 12345,
				'ip'         => '127.0.0.1',
			],
			'missing'    => [
				'cookieData' => [

				],
				'hasValues'  => false,
				'uid'        => null,
				'hash'       => null,
				'ip'         => null,
			],
			'invalid'    => [
				'cookieData' => [
					Cookie::NAME => 'test',
				],
				'hasValues'  => false,
				'uid'        => null,
				'hash'       => null,
				'ip'         => null,
			],
			'incomplete' => [
				'cookieData' => [
					Cookie::NAME => json_encode([
						'uid'  => -1,
						'hash' => 12345,
					])
				],
				'hasValues'  => true,
				'uid'        => -1,
				'hash'       => 12345,
				'ip'         => null,
			],
		];
	}

	/**
	 * Test the get() method of the cookie class
	 *
	 * @dataProvider dataGet
	 */
	public function testGet(array $cookieData, bool $hasValues, $uid, $hash, $ip)
	{
		$this->baseUrl->shouldReceive('getSSLPolicy')->andReturn(true)->once();
		$this->config->shouldReceive('get')->with('system', 'site_prvkey')->andReturn('1235')->once();
		$this->config->shouldReceive('get')->with('system', 'auth_cookie_lifetime', Cookie::DEFAULT_EXPIRE)->andReturn('7')->once();

		$cookie = new Cookie($this->config, $this->baseUrl, [], $cookieData);
		self::assertInstanceOf(Cookie::class, $cookie);

		$assertData = $cookie->getData();

		if (!$hasValues) {
			self::assertEmpty($assertData);
		} else {
			self::assertNotEmpty($assertData);
			if (isset($uid)) {
				self::assertObjectHasAttribute('uid', $assertData);
				self::assertEquals($uid, $assertData->uid);
			} else {
				self::assertObjectNotHasAttribute('uid', $assertData);
			}
			if (isset($hash)) {
				self::assertObjectHasAttribute('hash', $assertData);
				self::assertEquals($hash, $assertData->hash);
			} else {
				self::assertObjectNotHasAttribute('hash', $assertData);
			}
			if (isset($ip)) {
				self::assertObjectHasAttribute('ip', $assertData);
				self::assertEquals($ip, $assertData->ip);
			} else {
				self::assertObjectNotHasAttribute('ip', $assertData);
			}
		}
	}

	public function dataCheck()
	{
		return [
			'default'   => [
				'serverPrivateKey' => 'serverkey',
				'userPrivateKey'   => 'userkey',
				'password'         => 'test',
				'assertHash'       => 'e9b4eb16275a2907b5659d22905b248221d0517dde4a9d5c320b8fe051b1267b',
				'assertTrue'       => true,
			],
			'emptyUser' => [
				'serverPrivateKey' => 'serverkey',
				'userPrivateKey'   => '',
				'password'         => '',
				'assertHash'       => '',
				'assertTrue'       => false,
			],
			'invalid'   => [
				'serverPrivateKey' => 'serverkey',
				'userPrivateKey'   => 'bla',
				'password'         => 'nope',
				'assertHash'       => 'real wrong!',
				'assertTrue'       => false,
			]
		];
	}

	/**
	 * Test the check() method of the cookie class
	 *
	 * @dataProvider dataCheck
	 */
	public function testCheck(string $serverPrivateKey, string $userPrivateKey, string $password, string $assertHash, bool $assertTrue)
	{
		$this->baseUrl->shouldReceive('getSSLPolicy')->andReturn(true)->once();
		$this->config->shouldReceive('get')->with('system', 'site_prvkey')->andReturn($serverPrivateKey)->once();
		$this->config->shouldReceive('get')->with('system', 'auth_cookie_lifetime', Cookie::DEFAULT_EXPIRE)->andReturn('7')->once();

		$cookie = new Cookie($this->config, $this->baseUrl);
		self::assertInstanceOf(Cookie::class, $cookie);

		self::assertEquals($assertTrue, $cookie->check($assertHash, $password, $userPrivateKey));
	}

	public function dataSet()
	{
		return [
			'default'         => [
				'serverKey'   => 23,
				'uid'         => 0,
				'password'    => '234',
				'privateKey'  => '124',
				'assertHash'  => 'b657a15cfe7ed1f7289c9aa51af14a9a26c966f4ddd74e495fba103d8e872a39',
				'remoteIp'    => '0.0.0.0',
				'serverArray' => [],
				'lifetime'    => null,
			],
			'withServerArray' => [
				'serverKey'   => 23,
				'uid'         => 0,
				'password'    => '234',
				'privateKey'  => '124',
				'assertHash'  => 'b657a15cfe7ed1f7289c9aa51af14a9a26c966f4ddd74e495fba103d8e872a39',
				'remoteIp'    => '1.2.3.4',
				'serverArray' => ['REMOTE_ADDR' => '1.2.3.4',],
				'lifetime'    => null,
			],
			'withLifetime0'   => [
				'serverKey'   => 23,
				'uid'         => 0,
				'password'    => '234',
				'privateKey'  => '124',
				'assertHash'  => 'b657a15cfe7ed1f7289c9aa51af14a9a26c966f4ddd74e495fba103d8e872a39',
				'remoteIp'    => '1.2.3.4',
				'serverArray' => ['REMOTE_ADDR' => '1.2.3.4',],
				'lifetime'    => 0,
			],
			'withLifetime'     => [
				'serverKey'   => 23,
				'uid'         => 0,
				'password'    => '234',
				'privateKey'  => '124',
				'assertHash'  => 'b657a15cfe7ed1f7289c9aa51af14a9a26c966f4ddd74e495fba103d8e872a39',
				'remoteIp'    => '1.2.3.4',
				'serverArray' => ['REMOTE_ADDR' => '1.2.3.4',],
				'lifetime'    => 2 * 24 * 60 * 60,
			],
		];
	}

	public function assertCookie($uid, $hash, $remoteIp, $lifetime)
	{
		self::assertArrayHasKey(Cookie::NAME, StaticCookie::$_COOKIE);

		$data = json_decode(StaticCookie::$_COOKIE[Cookie::NAME]);

		self::assertObjectHasAttribute('uid', $data);
		self::assertEquals($uid, $data->uid);
		self::assertObjectHasAttribute('hash', $data);
		self::assertEquals($hash, $data->hash);
		self::assertObjectHasAttribute('ip', $data);
		self::assertEquals($remoteIp, $data->ip);

		if (isset($lifetime) && $lifetime !== 0) {
			self::assertLessThanOrEqual(time() + $lifetime, StaticCookie::$_EXPIRE);
		} else {
			self::assertLessThanOrEqual(time() + Cookie::DEFAULT_EXPIRE * 24 * 60 * 60, StaticCookie::$_EXPIRE);
		}
	}

	/**
	 * Test the set() method of the cookie class
	 *
	 * @dataProvider dataSet
	 */
	public function testSet($serverKey, $uid, $password, $privateKey, $assertHash, $remoteIp, $serverArray, $lifetime)
	{
		$this->baseUrl->shouldReceive('getSSLPolicy')->andReturn(true)->once();
		$this->config->shouldReceive('get')->with('system', 'site_prvkey')->andReturn($serverKey)->once();
		$this->config->shouldReceive('get')->with('system', 'auth_cookie_lifetime', Cookie::DEFAULT_EXPIRE)->andReturn(Cookie::DEFAULT_EXPIRE)->once();

		$cookie = new StaticCookie($this->config, $this->baseUrl, $serverArray);
		self::assertInstanceOf(Cookie::class, $cookie);

		$cookie->set($uid, $password, $privateKey, $lifetime);

		self::assertCookie($uid, $assertHash, $remoteIp, $lifetime);
	}

	/**
	 * Test two different set() of the cookie class (first set is invalid)
	 *
	 * @dataProvider dataSet
	 */
	public function testDoubleSet($serverKey, $uid, $password, $privateKey, $assertHash, $remoteIp, $serverArray, $lifetime)
	{
		$this->baseUrl->shouldReceive('getSSLPolicy')->andReturn(true)->once();
		$this->config->shouldReceive('get')->with('system', 'site_prvkey')->andReturn($serverKey)->once();
		$this->config->shouldReceive('get')->with('system', 'auth_cookie_lifetime', Cookie::DEFAULT_EXPIRE)->andReturn(Cookie::DEFAULT_EXPIRE)->once();

		$cookie = new StaticCookie($this->config, $this->baseUrl, $serverArray);
		self::assertInstanceOf(Cookie::class, $cookie);

		// Invalid set, should get overwritten
		$cookie->set(-1, 'invalid', 'nothing', -234);

		$cookie->set($uid, $password, $privateKey, $lifetime);

		self::assertCookie($uid, $assertHash, $remoteIp, $lifetime);
	}

	/**
	 * Test the clear() method of the cookie class
	 */
	public function testClear()
	{
		StaticCookie::$_COOKIE = [
			Cookie::NAME => 'test'
		];

		$this->baseUrl->shouldReceive('getSSLPolicy')->andReturn(true)->once();
		$this->config->shouldReceive('get')->with('system', 'site_prvkey')->andReturn(24)->once();
		$this->config->shouldReceive('get')->with('system', 'auth_cookie_lifetime', Cookie::DEFAULT_EXPIRE)->andReturn(Cookie::DEFAULT_EXPIRE)->once();

		$cookie = new StaticCookie($this->config, $this->baseUrl);
		self::assertInstanceOf(Cookie::class, $cookie);

		self::assertEquals('test', StaticCookie::$_COOKIE[Cookie::NAME]);
		self::assertEquals(null, StaticCookie::$_EXPIRE);

		$cookie->clear();

		self::assertEmpty(StaticCookie::$_COOKIE[Cookie::NAME]);
		self::assertEquals(-3600, StaticCookie::$_EXPIRE);
	}
}
