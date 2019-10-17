<?php

namespace Friendica\Test\src\Core\Config\Cache;

use Friendica\Core\Config\Cache\PConfigCache;
use Friendica\Test\MockedTest;

class PConfigCacheTest extends MockedTest
{
	public function dataTests()
	{
		return [
			'normal' => [
				'data' => [
					'system' => [
						'test'      => 'it',
						'boolTrue'  => true,
						'boolFalse' => false,
						'int'       => 235,
						'dec'       => 2.456,
						'array'     => ['1', 2, '3', true, false],
					],
					'config' => [
						'a' => 'value',
					],
				]
			]
		];
	}

	private function assertConfigValues($data, PConfigCache $configCache, $uid)
	{
		foreach ($data as $cat => $values) {
			foreach ($values as $key => $value) {
				$this->assertEquals($data[$cat][$key], $configCache->get($uid, $cat, $key));
			}
		}
	}

	/**
	 * Test the setP() and getP() methods
	 *
	 * @dataProvider dataTests
	 */
	public function testSetGet($data)
	{
		$configCache = new PConfigCache();
		$uid         = 345;

		foreach ($data as $cat => $values) {
			foreach ($values as $key => $value) {
				$configCache->set($uid, $cat, $key, $value);
			}
		}

		$this->assertConfigValues($data, $configCache, $uid);
	}


	/**
	 * Test the getP() method with a category
	 */
	public function testGetCat()
	{
		$configCache = new PConfigCache();
		$uid         = 345;

		$configCache->load($uid, [
			'system' => [
				'key1' => 'value1',
				'key2' => 'value2',
			],
			'config' => [
				'key3' => 'value3',
			],
		]);

		$this->assertEquals([
			'key1' => 'value1',
			'key2' => 'value2',
		], $configCache->get($uid, 'system'));

		// test explicit cat with null as key
		$this->assertEquals([
			'key1' => 'value1',
			'key2' => 'value2',
		], $configCache->get($uid, 'system', null));
	}

	/**
	 * Test the deleteP() method
	 *
	 * @dataProvider dataTests
	 */
	public function testDelete($data)
	{
		$configCache = new PConfigCache();
		$uid         = 345;

		foreach ($data as $cat => $values) {
			foreach ($values as $key => $value) {
				$configCache->set($uid, $cat, $key, $value);
			}
		}

		foreach ($data as $cat => $values) {
			foreach ($values as $key => $value) {
				$configCache->delete($uid, $cat, $key);
			}
		}

		$this->assertEmpty($configCache->getAll());
	}

	/**
	 * Test the keyDiff() method with result
	 *
	 * @dataProvider dataTests
	 */
	public function testKeyDiffWithResult($data)
	{
		$configCache = new PConfigCache();

		$diffConfig = [
			'fakeCat' => [
				'fakeKey' => 'value',
			]
		];

		$this->assertEquals($diffConfig, $configCache->keyDiff($diffConfig));
	}

	/**
	 * Test the keyDiff() method without result
	 *
	 * @dataProvider dataTests
	 */
	public function testKeyDiffWithoutResult($data)
	{
		$configCache = new PConfigCache();

		$configCache->load(1, $data);

		$diffConfig = $configCache->getAll();

		$this->assertEmpty($configCache->keyDiff($diffConfig));
	}

	/**
	 * Test the default hiding of passwords inside the cache
	 */
	public function testPasswordHide()
	{
		$configCache = new PConfigCache();

		$configCache->load(1, [
			'database' => [
				'password' => 'supersecure',
				'username' => 'notsecured',
			]
		]);

		$this->assertEquals('supersecure', $configCache->get(1, 'database', 'password'));
		$this->assertNotEquals('supersecure', print_r($configCache->get(1, 'database', 'password'), true));
		$this->assertEquals('notsecured', print_r($configCache->get(1, 'database', 'username'), true));
	}

	/**
	 * Test disabling the hiding of passwords inside the cache
	 */
	public function testPasswordShow()
	{
		$configCache = new PConfigCache(false);

		$configCache->load(1, [
			'database' => [
				'password' => 'supersecure',
				'username' => 'notsecured',
			]
		]);

		$this->assertEquals('supersecure', $configCache->get(1, 'database', 'password'));
		$this->assertEquals('supersecure', print_r($configCache->get(1, 'database', 'password'), true));
		$this->assertEquals('notsecured', print_r($configCache->get(1, 'database', 'username'), true));
	}

	/**
	 * Test a empty password
	 */
	public function testEmptyPassword()
	{
		$configCache = new PConfigCache();

		$configCache->load(1, [
			'database' => [
				'password' => '',
				'username' => '',
			]
		]);

		$this->assertEmpty($configCache->get(1, 'database', 'password'));
		$this->assertEmpty($configCache->get(1, 'database', 'username'));
	}

	public function testWrongTypePassword()
	{
		$configCache = new PConfigCache();

		$configCache->load(1, [
			'database' => [
				'password' => new \stdClass(),
				'username' => '',
			]
		]);

		$this->assertNotEmpty($configCache->get(1, 'database', 'password'));
		$this->assertEmpty($configCache->get(1, 'database', 'username'));

		$configCache = new PConfigCache();

		$configCache->load(1, [
			'database' => [
				'password' => 23,
				'username' => '',
			],
		]);

		$this->assertEquals(23, $configCache->get(1, 'database', 'password'));
		$this->assertEmpty($configCache->get(1, 'database', 'username'));
	}

	/**
	 * Test two different UID configs and make sure that there is no overlapping possible
	 */
	public function testTwoUid()
	{
		$configCache = new PConfigCache();

		$configCache->load(1, [
			'cat1' => [
				'key1' => 'value1',
			],
		]);


		$configCache->load(2, [
			'cat2' => [
				'key2' => 'value2',
			],
		]);

		$this->assertEquals('value1', $configCache->get(1, 'cat1', 'key1'));
		$this->assertEquals('value2', $configCache->get(2, 'cat2', 'key2'));

		$this->assertNull($configCache->get(1, 'cat2', 'key2'));
		$this->assertNull($configCache->get(2, 'cat1', 'key1'));
	}

	/**
	 * Test when using an invalid UID
	 * @todo check it the clean way before using the config class
	 */
	public function testInvalidUid()
	{
		// bad UID!
		$uid = null;

		$configCache = new PConfigCache();

		$this->assertNull($configCache->get($uid, 'cat1', 'cat2'));

		$this->assertFalse($configCache->set($uid, 'cat1', 'key1', 'doesn\'t matter!'));
		$this->assertFalse($configCache->delete($uid, 'cat1', 'key1'));
	}
}
