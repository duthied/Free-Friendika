<?php

namespace Friendica\Test\src\Core\Config\Cache;

use Friendica\Core\Config\Cache\ConfigCache;
use Friendica\Test\MockedTest;
use ParagonIE\HiddenString\HiddenString;

class ConfigCacheTest extends MockedTest
{
	public function dataTests()
	{
		return [
			'normal' => [
				'data' => [
					'system' => [
						'test' => 'it',
						'boolTrue' => true,
						'boolFalse' => false,
						'int' => 235,
						'dec' => 2.456,
						'array' => ['1', 2, '3', true, false],
					],
					'config' => [
						'a' => 'value',
					],
				]
			]
		];
	}

	private function assertConfigValues($data, ConfigCache $configCache)
	{
		foreach ($data as $cat => $values) {
			foreach ($values as $key => $value) {
				$this->assertEquals($data[$cat][$key], $configCache->get($cat, $key));
			}
		}
	}

	/**
	 * Test the loadConfigArray() method without override
	 * @dataProvider dataTests
	 */
	public function testLoadConfigArray($data)
	{
		$configCache = new ConfigCache();
		$configCache->load($data);

		$this->assertConfigValues($data, $configCache);
	}

	/**
	 * Test the loadConfigArray() method with overrides
	 * @dataProvider dataTests
	 */
	public function testLoadConfigArrayOverride($data)
	{
		$override = [
			'system' => [
				'test' => 'not',
				'boolTrue' => false,
			]
		];

		$configCache = new ConfigCache();
		$configCache->load($data);
		$configCache->load($override);

		$this->assertConfigValues($data, $configCache);

		// override the value
		$configCache->load($override, true);

		$this->assertEquals($override['system']['test'], $configCache->get('system', 'test'));
		$this->assertEquals($override['system']['boolTrue'], $configCache->get('system', 'boolTrue'));
	}

	/**
	 * Test the loadConfigArray() method with wrong/empty datasets
	 */
	public function testLoadConfigArrayWrong()
	{
		$configCache = new ConfigCache();

		// empty dataset
		$configCache->load([]);
		$this->assertEmpty($configCache->getAll());

		// wrong dataset
		$configCache->load(['system' => 'not_array']);
		$this->assertEmpty($configCache->getAll());

		// incomplete dataset (key is integer ID of the array)
		$configCache->load(['system' => ['value']]);
		$this->assertEquals('value', $configCache->get('system', 0));
	}

	/**
	 * Test the getAll() method
	 * @dataProvider dataTests
	 */
	public function testGetAll($data)
	{
		$configCache = new ConfigCache();
		$configCache->load($data);

		$all = $configCache->getAll();

		$this->assertContains($data['system'], $all);
		$this->assertContains($data['config'], $all);
	}

	/**
	 * Test the set() and get() method
	 * @dataProvider dataTests
	 */
	public function testSetGet($data)
	{
		$configCache = new ConfigCache();

		foreach ($data as $cat => $values) {
			foreach ($values as $key => $value) {
				$configCache->set($cat, $key, $value);
			}
		}

		$this->assertConfigValues($data, $configCache);
	}

	/**
	 * Test the get() method without a value
	 */
	public function testGetEmpty()
	{
		$configCache = new ConfigCache();

		$this->assertNull($configCache->get('something', 'value'));
	}

	/**
	 * Test the get() method with a category
	 */
	public function testGetCat()
	{
		$configCache = new ConfigCache([
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
		], $configCache->get('system'));

		// explicit null as key
		$this->assertEquals([
			'key1' => 'value1',
			'key2' => 'value2',
		], $configCache->get('system', null));
	}

	/**
	 * Test the delete() method
	 * @dataProvider dataTests
	 */
	public function testDelete($data)
	{
		$configCache = new ConfigCache($data);

		foreach ($data as $cat => $values) {
			foreach ($values as $key => $value) {
				$configCache->delete($cat, $key);
			}
		}

		$this->assertEmpty($configCache->getAll());
	}

	/**
	 * Test the keyDiff() method with result
	 * @dataProvider dataTests
	 */
	public function testKeyDiffWithResult($data)
	{
		$configCache = new ConfigCache($data);

		$diffConfig = [
			'fakeCat' => [
				'fakeKey' => 'value',
			]
		];

		$this->assertEquals($diffConfig, $configCache->keyDiff($diffConfig));
	}

	/**
	 * Test the keyDiff() method without result
	 * @dataProvider dataTests
	 */
	public function testKeyDiffWithoutResult($data)
	{
		$configCache = new ConfigCache($data);

		$diffConfig = $configCache->getAll();

		$this->assertEmpty($configCache->keyDiff($diffConfig));
	}

	/**
	 * Test the default hiding of passwords inside the cache
	 */
	public function testPasswordHide()
	{
		$configCache = new ConfigCache([
			'database' => [
				'password' => 'supersecure',
				'username' => 'notsecured',
			],
		]);

		$this->assertEquals('supersecure', $configCache->get('database', 'password'));
		$this->assertNotEquals('supersecure', print_r($configCache->get('database', 'password'), true));
		$this->assertEquals('notsecured', print_r($configCache->get('database', 'username'), true));
	}

	/**
	 * Test disabling the hiding of passwords inside the cache
	 */
	public function testPasswordShow()
	{
		$configCache = new ConfigCache([
			'database' => [
				'password' => 'supersecure',
				'username' => 'notsecured',
			],
		], false);

		$this->assertEquals('supersecure', $configCache->get('database', 'password'));
		$this->assertEquals('supersecure', print_r($configCache->get('database', 'password'), true));
		$this->assertEquals('notsecured', print_r($configCache->get('database', 'username'), true));
	}

	/**
	 * Test a empty password
	 */
	public function testEmptyPassword()
	{
		$configCache = new ConfigCache([
			'database' => [
				'password' => '',
				'username' => '',
			]
		]);

		$this->assertNotEmpty($configCache->get('database', 'password'));
		$this->assertInstanceOf(HiddenString::class, $configCache->get('database', 'password'));
		$this->assertEmpty($configCache->get('database', 'username'));
	}

	public function testWrongTypePassword()
	{
		$configCache = new ConfigCache([
			'database' => [
				'password' => new \stdClass(),
				'username' => '',
			]
		]);

		$this->assertNotEmpty($configCache->get('database', 'password'));
		$this->assertEmpty($configCache->get('database', 'username'));

		$configCache = new ConfigCache([
			'database' => [
				'password' => 23,
				'username' => '',
			]
		]);

		$this->assertEquals(23, $configCache->get('database', 'password'));
		$this->assertEmpty($configCache->get('database', 'username'));
	}
}
