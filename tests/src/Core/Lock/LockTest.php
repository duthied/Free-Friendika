<?php

namespace Friendica\Test\src\Core\Lock;

use Friendica\Test\MockedTest;

abstract class LockTest extends MockedTest
{
	/**
	 * @var int Start time of the mock (used for time operations)
	 */
	protected $startTime = 1417011228;

	/**
	 * @var \Friendica\Core\Lock\ILock
	 */
	protected $instance;

	abstract protected function getInstance();

	protected function setUp()
	{
		parent::setUp();

		$this->instance = $this->getInstance();
		$this->instance->releaseAll(true);
	}

	protected function tearDown()
	{
		$this->instance->releaseAll(true);
		parent::tearDown();
	}

	/**
	 * @small
	 */
	public function testLock()
	{
		$this->assertFalse($this->instance->isLocked('foo'));
		$this->assertTrue($this->instance->acquireLock('foo', 1));
		$this->assertTrue($this->instance->isLocked('foo'));
		$this->assertFalse($this->instance->isLocked('bar'));
	}

	/**
	 * @small
	 */
	public function testDoubleLock()
	{
		$this->assertFalse($this->instance->isLocked('foo'));
		$this->assertTrue($this->instance->acquireLock('foo', 1));
		$this->assertTrue($this->instance->isLocked('foo'));
		// We already locked it
		$this->assertTrue($this->instance->acquireLock('foo', 1));
	}

	/**
	 * @small
	 */
	public function testReleaseLock()
	{
		$this->assertFalse($this->instance->isLocked('foo'));
		$this->assertTrue($this->instance->acquireLock('foo', 1));
		$this->assertTrue($this->instance->isLocked('foo'));
		$this->instance->releaseLock('foo');
		$this->assertFalse($this->instance->isLocked('foo'));
	}

	/**
	 * @small
	 */
	public function testReleaseAll()
	{
		$this->assertTrue($this->instance->acquireLock('foo', 1));
		$this->assertTrue($this->instance->acquireLock('bar', 1));
		$this->assertTrue($this->instance->acquireLock('nice', 1));

		$this->assertTrue($this->instance->isLocked('foo'));
		$this->assertTrue($this->instance->isLocked('bar'));
		$this->assertTrue($this->instance->isLocked('nice'));

		$this->assertTrue($this->instance->releaseAll());

		$this->assertFalse($this->instance->isLocked('foo'));
		$this->assertFalse($this->instance->isLocked('bar'));
		$this->assertFalse($this->instance->isLocked('nice'));
	}

	/**
	 * @small
	 */
	public function testReleaseAfterUnlock()
	{
		$this->assertFalse($this->instance->isLocked('foo'));
		$this->assertFalse($this->instance->isLocked('bar'));
		$this->assertFalse($this->instance->isLocked('nice'));
		$this->assertTrue($this->instance->acquireLock('foo', 1));
		$this->assertTrue($this->instance->acquireLock('bar', 1));
		$this->assertTrue($this->instance->acquireLock('nice', 1));

		$this->assertTrue($this->instance->releaseLock('foo'));

		$this->assertFalse($this->instance->isLocked('foo'));
		$this->assertTrue($this->instance->isLocked('bar'));
		$this->assertTrue($this->instance->isLocked('nice'));

		$this->assertTrue($this->instance->releaseAll());

		$this->assertFalse($this->instance->isLocked('bar'));
		$this->assertFalse($this->instance->isLocked('nice'));
	}

	/**
	 * @small
	 */
	public function testReleaseWitTTL()
	{
		$this->assertFalse($this->instance->isLocked('test'));
		$this->assertTrue($this->instance->acquireLock('test', 1, 10));
		$this->assertTrue($this->instance->isLocked('test'));
		$this->assertTrue($this->instance->releaseLock('test'));
		$this->assertFalse($this->instance->isLocked('test'));
	}

	/**
	 * @small
	 */
	public function testGetLocks()
	{
		$this->assertTrue($this->instance->acquireLock('foo', 1));
		$this->assertTrue($this->instance->acquireLock('bar', 1));
		$this->assertTrue($this->instance->acquireLock('nice', 1));

		$this->assertTrue($this->instance->isLocked('foo'));
		$this->assertTrue($this->instance->isLocked('bar'));
		$this->assertTrue($this->instance->isLocked('nice'));

		$locks = $this->instance->getLocks();

		$this->assertContains('foo', $locks);
		$this->assertContains('bar', $locks);
		$this->assertContains('nice', $locks);
	}

	/**
	 * @small
	 */
	public function testGetLocksWithPrefix()
	{
		$this->assertTrue($this->instance->acquireLock('foo', 1));
		$this->assertTrue($this->instance->acquireLock('test1', 1));
		$this->assertTrue($this->instance->acquireLock('test2', 1));

		$this->assertTrue($this->instance->isLocked('foo'));
		$this->assertTrue($this->instance->isLocked('test1'));
		$this->assertTrue($this->instance->isLocked('test2'));

		$locks = $this->instance->getLocks('test');

		$this->assertContains('test1', $locks);
		$this->assertContains('test2', $locks);
		$this->assertNotContains('foo', $locks);
	}

	/**
	 * @medium
	 */
	function testLockTTL()
	{
		$this->markTestSkipped('taking too much time without mocking');

		$this->assertFalse($this->instance->isLocked('foo'));
		$this->assertFalse($this->instance->isLocked('bar'));

		// TODO [nupplaphil] - Because of the Datetime-Utils for the database, we have to wait a FULL second between the checks to invalidate the db-locks/cache
		$this->assertTrue($this->instance->acquireLock('foo', 2, 1));
		$this->assertTrue($this->instance->acquireLock('bar', 2, 3));

		$this->assertTrue($this->instance->isLocked('foo'));
		$this->assertTrue($this->instance->isLocked('bar'));

		sleep(2);

		$this->assertFalse($this->instance->isLocked('foo'));
		$this->assertTrue($this->instance->isLocked('bar'));

		sleep(2);

		$this->assertFalse($this->instance->isLocked('foo'));
		$this->assertFalse($this->instance->isLocked('bar'));
	}

	/**
	 * Test if releasing a non-existing lock doesn't throw errors
	 */
	public function testReleaseLockWithoutLock()
	{
		$this->assertFalse($this->instance->isLocked('wrongLock'));
		$this->assertFalse($this->instance->releaseLock('wrongLock'));
	}
}
