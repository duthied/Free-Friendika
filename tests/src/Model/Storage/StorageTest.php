<?php

namespace Friendica\Test\src\Model\Storage;

use Friendica\Model\Storage\IStorage;
use Friendica\Test\MockedTest;

abstract class StorageTest extends MockedTest
{
	/** @return IStorage */
	abstract protected function getInstance();

	abstract protected function assertOption(IStorage $storage);

	/**
	 * Test if the instance is "really" implementing the interface
	 */
	public function testInstance()
	{
		$instance = $this->getInstance();
		$this->assertInstanceOf(IStorage::class, $instance);
	}

	/**
	 * Test if the "getOption" is asserted
	 */
	public function testGetOptions()
	{
		$instance = $this->getInstance();

		$this->assertOption($instance);
	}

	/**
	 * Test basic put, get and delete operations
	 */
	public function testPutGetDelete()
	{
		$instance = $this->getInstance();

		$ref = $instance->put('data12345');
		$this->assertNotEmpty($ref);

		$this->assertEquals('data12345', $instance->get($ref));

		$this->assertTrue($instance->delete($ref));
	}

	/**
	 * Test a delete with an invalid reference
	 */
	public function testInvalidDelete()
	{
		$instance = $this->getInstance();

		// Even deleting not existing references should return "true"
		$this->assertTrue($instance->delete(-1234456));
	}

	/**
	 * Test a get with an invalid reference
	 */
	public function testInvalidGet()
	{
		$instance = $this->getInstance();

		// Invalid references return an empty string
		$this->assertEmpty($instance->get(-123456));
	}

	/**
	 * Test an update with a given reference
	 */
	public function testUpdateReference()
	{
		$instance = $this->getInstance();

		$ref = $instance->put('data12345');
		$this->assertNotEmpty($ref);

		$this->assertEquals('data12345', $instance->get($ref));

		$this->assertEquals($ref, $instance->put('data5432', $ref));
		$this->assertEquals('data5432', $instance->get($ref));
	}

	/**
	 * Test that an invalid update results in an insert
	 */
	public function testInvalidUpdate()
	{
		$instance = $this->getInstance();

		$this->assertEquals(-123, $instance->put('data12345', -123));
	}
}
