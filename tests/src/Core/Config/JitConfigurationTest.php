<?php

namespace Friendica\Test\src\Core\Config;

use Friendica\Core\Config\JitConfiguration;

class JitConfigurationTest extends ConfigurationTest
{
	public function getInstance()
	{
		return new JitConfiguration($this->configCache, $this->configModel);
	}

	/**
	 * @dataProvider dataConfigLoad
	 */
	public function testSetUp(array $data)
	{
		$this->configModel->shouldReceive('load')
		                  ->with('config')
		                  ->andReturn(['config' => $data['config']])
		                  ->once();

		parent::testSetUp($data);
	}

	/**
	 * @dataProvider dataConfigLoad
	 */
	public function testLoad(array $data, array $possibleCats, array $load)
	{
		$this->configModel->shouldReceive('isConnected')
		                  ->andReturn(true)
		                  ->times(count($load) + 1);

		$this->configModel->shouldReceive('load')
		                  ->with('config')
		                  ->andReturn(['config' => $data['config']])
		                  ->once();

		foreach ($load as $loadCat) {
			$this->configModel->shouldReceive('load')
			                  ->with($loadCat)
			                  ->andReturn([$loadCat => $data[$loadCat]])
			                  ->once();
		}

		parent::testLoad($data, $possibleCats, $load);
	}

	/**
	 * @dataProvider dataDoubleLoad
	 */
	public function testCacheLoadDouble(array $data1, array $data2, array $expect)
	{
		$this->configModel->shouldReceive('isConnected')
		                  ->andReturn(true)
		                  ->times(count($data1) + count($data2) + 1);

		$this->configModel->shouldReceive('load')
		                  ->with('config')
		                  ->andReturn(['config' => $data1['config']])
		                  ->once();

		foreach ($data1 as $cat => $data) {
			$this->configModel->shouldReceive('load')
			                  ->with($cat)
			                  ->andReturn([$cat => $data])
			                  ->once();
		}


		foreach ($data2 as $cat => $data) {
			$this->configModel->shouldReceive('load')
			                  ->with($cat)
			                  ->andReturn([$cat => $data])
			                  ->once();
		}

		parent::testCacheLoadDouble($data1, $data2, $expect);

		// Assert the expected categories
		foreach ($data2 as $cat => $data) {
			$this->assertConfig($cat, $expect[$cat]);
		}
	}

	/**
	 * @dataProvider dataTests
	 */
	public function testSetGetWithDB($data)
	{
		$this->configModel->shouldReceive('isConnected')
		                  ->andReturn(true)
		                  ->times(3);

		$this->configModel->shouldReceive('load')->with('config')->andReturn(['config' => []])->once();

		parent::testSetGetWithDB($data);
	}

	/**
	 * @dataProvider dataTests
	 */
	public function testGetWithRefresh($data)
	{
		$this->configModel->shouldReceive('isConnected')
		                  ->andReturn(true)
		                  ->times(4);

		// constructor loading
		$this->configModel->shouldReceive('load')
		                  ->with('config')
		                  ->andReturn(['config' => []])
		                  ->once();

		// mocking one get without result
		$this->configModel->shouldReceive('get')
		                  ->with('test', 'it')
		                  ->andReturn(null)
		                  ->once();

		// mocking the data get
		$this->configModel->shouldReceive('get')
		                  ->with('test', 'it')
		                  ->andReturn($data)
		                  ->once();

		// mocking second get
		$this->configModel->shouldReceive('get')
		                  ->with('test', 'not')
		                  ->andReturn(null)
		                  ->once();

		parent::testGetWithRefresh($data);
	}

	public function testGetWrongWithoutDB()
	{
		$this->configModel->shouldReceive('isConnected')
		                  ->andReturn(false)
		                  ->times(4);

		parent::testGetWrongWithoutDB();
	}

	/**
	 * @dataProvider dataTests
	 */
	public function testDeleteWithoutDB($data)
	{
		$this->configModel->shouldReceive('isConnected')
		                  ->andReturn(false)
		                  ->times(4);

		parent::testDeleteWithoutDB($data);
	}

	public function testDeleteWithDB()
	{
		$this->configModel->shouldReceive('isConnected')
		                  ->andReturn(true)
		                  ->times(6);

		// constructor loading
		$this->configModel->shouldReceive('load')
		                  ->with('config')
		                  ->andReturn(['config' => []])
		                  ->once();

		// mocking one get without result
		$this->configModel->shouldReceive('get')
		                  ->with('test', 'it')
		                  ->andReturn(null)
		                  ->once();

		parent::testDeleteWithDB();
	}
}
