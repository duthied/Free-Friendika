<?php

namespace Friendica\Testsrc\Model\User;

use Friendica\Core\Config\Configuration;
use Friendica\Model\User\Cookie;
use Friendica\Test\DatabaseTest;
use Mockery\MockInterface;

class CookieTest extends DatabaseTest
{
	/** @var MockInterface|Configuration */
	private $config;

	protected function setUp()
	{
		parent::setUp();;

		$this->config = \Mockery::mock(Configuration::class);
	}

	public function testInstance()
	{
		$this->config->shouldReceive('get')->with('system', 'ssl_policy')->andReturn(1)->once();
		$this->config->shouldReceive('get')->with('system', 'site_prvkey')->andReturn('1235')->once();
		$this->config->shouldReceive('get')->with('system', 'auth_cookie_lifetime', Cookie::DEFAULT_EXPIRE)->andReturn('7')->once();

		$cookie = new Cookie($this->config, []);
		$this->assertInstanceOf(Cookie::class, $cookie);
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
	 * @dataProvider dataGet
	 */
	public function testGet(array $cookieData, bool $hasValues, $uid, $hash, $ip)
	{
		$this->config->shouldReceive('get')->with('system', 'ssl_policy')->andReturn(1)->once();
		$this->config->shouldReceive('get')->with('system', 'site_prvkey')->andReturn('1235')->once();
		$this->config->shouldReceive('get')->with('system', 'auth_cookie_lifetime', Cookie::DEFAULT_EXPIRE)->andReturn('7')->once();

		$cookie = new Cookie($this->config, [], $cookieData);
		$this->assertInstanceOf(Cookie::class, $cookie);

		$assertData = $cookie->getData();

		if (!$hasValues) {
			$this->assertEmpty($assertData);
		} else {
			$this->assertNotEmpty($assertData);
			if (isset($uid)) {
				$this->assertObjectHasAttribute('uid', $assertData);
				$this->assertEquals($uid, $assertData->uid);
			} else {
				$this->assertObjectNotHasAttribute('uid', $assertData);
			}
			if (isset($hash)) {
				$this->assertObjectHasAttribute('hash', $assertData);
				$this->assertEquals($hash, $assertData->hash);
			} else {
				$this->assertObjectNotHasAttribute('hash', $assertData);
			}
			if (isset($ip)) {
				$this->assertObjectHasAttribute('ip', $assertData);
				$this->assertEquals($ip, $assertData->ip);
			} else {
				$this->assertObjectNotHasAttribute('ip', $assertData);
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
			'invalid' => [
				'serverPrivateKey' => 'serverkey',
				'userPrivateKey'   => 'bla',
				'password'         => 'nope',
				'assertHash'       => 'real wrong!',
				'assertTrue'       => false,
			]
		];
	}

	/**
	 * @dataProvider dataCheck
	 */
	public function testCheck(string $serverPrivateKey, string $userPrivateKey, string $password, string $assertHash, bool $assertTrue)
	{
		$this->config->shouldReceive('get')->with('system', 'ssl_policy')->andReturn(1)->once();
		$this->config->shouldReceive('get')->with('system', 'site_prvkey')->andReturn($serverPrivateKey)->once();
		$this->config->shouldReceive('get')->with('system', 'auth_cookie_lifetime', Cookie::DEFAULT_EXPIRE)->andReturn('7')->once();

		$cookie = new Cookie($this->config, []);
		$this->assertInstanceOf(Cookie::class, $cookie);

		$this->assertEquals($assertTrue, $cookie->check($assertHash, $password, $userPrivateKey));
	}

	public function testSet()
	{
		$this->markTestIncomplete('Needs mocking of setcookie() first.');
	}

	public function testClear()
	{
		$this->markTestIncomplete('Needs mocking of setcookie() first.');
	}
}
