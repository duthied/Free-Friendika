<?php

namespace Friendica\Test\src\Core\Config;

use Friendica\Core\Config\PreloadConfiguration;

class PreloadConfigurationTest extends ConfigurationTest
{
	public function getInstance()
	{
		return new PreloadConfiguration($this->configCache, $this->configModel);
	}

	/**
	 * @dataProvider dataConfigLoad
	 */
	public function testSetUp(array $data)
	{
		$this->configModel->shouldReceive('load')
		                  ->andReturn($data)
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
		                  ->once();

		$this->configModel->shouldReceive('load')
		                  ->andReturn($data)
		                  ->once();

		parent::testLoad($data, $possibleCats, $load);

		// Assert that every category is loaded everytime
		foreach ($data as $cat => $values) {
			$this->assertConfig($cat, $values);
		}
	}

	/**
	 * @dataProvider dataDoubleLoad
	 */
	public function testCacheLoadDouble(array $data1, array $data2, array $expect)
	{
		$this->configModel->shouldReceive('isConnected')
		                  ->andReturn(true)
		                  ->once();

		$this->configModel->shouldReceive('load')
		                  ->andReturn($data1)
		                  ->once();

		parent::testCacheLoadDouble($data1, $data2, $expect);

		// Assert that every category is loaded everytime and is NOT overwritten
		foreach ($data1 as $cat => $values) {
			$this->assertConfig($cat, $values);
		}
	}

	/**
	 * @dataProvider dataTests
	 */
	public function testSetGetWithDB($data)
	{
		$this->configModel->shouldReceive('isConnected')
		                  ->andReturn(true)
		                  ->times(2);

		$this->configModel->shouldReceive('load')->andReturn(['config' => []])->once();

		parent::testSetGetWithDB($data);
	}

	/**
	 * @dataProvider dataTests
	 */
	public function testGetWithRefresh($data)
	{
		$this->configModel->shouldReceive('isConnected')
		                  ->andReturn(true)
		                  ->times(2);

		// constructor loading
		$this->configModel->shouldReceive('load')
		                  ->andReturn(['config' => []])
		                  ->once();

		// mocking one get
		$this->configModel->shouldReceive('get')
		                  ->with('test', 'it')
		                  ->andReturn($data)
		                  ->once();

		parent::testGetWithRefresh($data);
	}


	public function testGetWrongWithoutDB()
	{
		$this->configModel->shouldReceive('isConnected')
		                  ->andReturn(false)
		                  ->times(2);

		parent::testGetWrongWithoutDB();
	}

	/**
	 * @dataProvider dataTests
	 */
	public function testDeleteWithoutDB($data)
	{
		$this->configModel->shouldReceive('isConnected')
		                  ->andReturn(false)
		                  ->times(2);

		parent::testDeleteWithoutDB($data);
	}

	public function testDeleteWithDB()
	{
		$this->configModel->shouldReceive('isConnected')
		                  ->andReturn(true)
		                  ->times(5);

		// constructor loading
		$this->configModel->shouldReceive('load')
		                  ->andReturn(['config' => []])
		                  ->once();

		parent::testDeleteWithDB();
	}
}
