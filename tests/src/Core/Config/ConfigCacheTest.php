<?php

namespace Friendica\Test\Core\Config;

use Friendica\Core\Config\ConfigCache;
use Friendica\Test\MockedTest;

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

	private function assertConfigValues($data, ConfigCache $configCache, $uid = null)
	{
		foreach ($data as $cat => $values) {
			foreach ($values as $key => $value) {
				if (isset($uid)) {
					$this->assertEquals($data[$cat][$key], $configCache->getP($uid, $cat, $key));
				} else {
					$this->assertEquals($data[$cat][$key], $configCache->get($cat, $key));
				}
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
		$configCache->loadConfigArray($data);

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
		$configCache->loadConfigArray($data);
		$configCache->loadConfigArray($override);

		$this->assertConfigValues($data, $configCache);

		// override the value
		$configCache->loadConfigArray($override, true);

		$this->assertEquals($override['system']['test'], $configCache->get('system', 'test'));
		$this->assertEquals($override['system']['boolTrue'], $configCache->get('system', 'boolTrue'));
	}

	/**
	 * Test the getAll() method
	 * @dataProvider dataTests
	 */
	public function testGetAll($data)
	{
		$configCache = new ConfigCache();
		$configCache->loadConfigArray($data);

		$all = $configCache->getAll();

		$this->assertContains($data['system'], $all);

		// config values are stored directly in the array base
		$this->assertEquals($data['config']['a'], $all['a']);
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
	 * Test the setP() and getP() methods
	 * @dataProvider dataTests
	 */
	public function testSetGetP($data)
	{
		$configCache = new ConfigCache();
		$uid = 345;

		foreach ($data as $cat => $values) {
			foreach ($values as $key => $value) {
				$configCache->setP($uid, $cat, $key, $value);
			}
		}

		$this->assertConfigValues($data, $configCache, $uid);
	}


	/**
	 * Test the deleteP() method
	 * @dataProvider dataTests
	 */
	public function testDeleteP($data)
	{
		$configCache = new ConfigCache();
		$uid = 345;

		foreach ($data as $cat => $values) {
			foreach ($values as $key => $value) {
				$configCache->setP($uid, $cat, $key, $value);
			}
		}

		foreach ($data as $cat => $values) {
			foreach ($values as $key => $value) {
				$configCache->deleteP($uid, $cat, $key);
			}
		}

		$this->assertEmpty($configCache->getAll());
	}
}
