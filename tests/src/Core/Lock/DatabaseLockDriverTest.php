<?php

namespace Friendica\Test\src\Core\Lock;

use Friendica\Core\Cache;
use Friendica\Core\Lock\DatabaseLockDriver;
use Friendica\Test\Util\DbaLockMockTrait;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class DatabaseLockDriverTest extends LockTest
{
	use DbaLockMockTrait;

	protected $pid = 123;

	protected function setUp()
	{
		$this->mockConnected();
		$this->mockConnect();

		$this->mockReleaseAll($this->pid, 2);

		parent::setUp();
	}

	protected function getInstance()
	{
		return new DatabaseLockDriver($this->pid);
	}

	public function testLock()
	{
		$this->mockIsLocked('foo', false, $this->startTime, 1);
		$this->mockAcquireLock('foo', Cache::FIVE_MINUTES, false, $this->pid, false, $this->startTime, 1);
		$this->mockIsLocked('foo', true, $this->startTime, 1);
		$this->mockIsLocked('bar', false, $this->startTime, 1);

		parent::testLock();
	}

	public function testDoubleLock()
	{
		$this->mockIsLocked('foo', false, $this->startTime, 1);
		$this->mockAcquireLock('foo', Cache::FIVE_MINUTES, false, $this->pid, false, $this->startTime, 1);
		$this->mockIsLocked('foo', true, $this->startTime, 1);
		$this->mockAcquireLock('foo', Cache::FIVE_MINUTES, true, $this->pid, true, $this->startTime, 1);

		parent::testDoubleLock();
	}

	public function testReleaseLock()
	{
		$this->mockIsLocked('foo', false, $this->startTime, 1);
		$this->mockAcquireLock('foo', Cache::FIVE_MINUTES, false, $this->pid, false, $this->startTime, 1);
		$this->mockIsLocked('foo', true, $this->startTime, 1);
		$this->mockReleaseLock('foo', $this->pid, 1);
		$this->mockIsLocked('foo', false, $this->startTime, 1);

		parent::testReleaseLock();
	}

	public function testReleaseAll()
	{
		$this->mockAcquireLock('foo', Cache::FIVE_MINUTES, false, $this->pid, false, $this->startTime, 1);
		$this->mockAcquireLock('bar', Cache::FIVE_MINUTES, false, $this->pid, false, $this->startTime, 1);
		$this->mockAcquireLock('nice', Cache::FIVE_MINUTES, false, $this->pid, false, $this->startTime, 1);

		$this->mockIsLocked('foo', true, $this->startTime, 1);
		$this->mockIsLocked('bar', true, $this->startTime, 1);
		$this->mockIsLocked('nice', true, $this->startTime, 1);

		$this->mockReleaseAll($this->pid, 1);

		$this->mockIsLocked('foo', false, $this->startTime, 1);
		$this->mockIsLocked('bar', false, $this->startTime, 1);
		$this->mockIsLocked('nice', false, $this->startTime, 1);

		parent::testReleaseAll();
	}

	public function testReleaseAfterUnlock()
	{
		$this->mockIsLocked('foo', false, $this->startTime, 1);
		$this->mockIsLocked('bar', false, $this->startTime, 1);
		$this->mockIsLocked('nice', false, $this->startTime, 1);

		$this->mockAcquireLock('foo', Cache::FIVE_MINUTES, false, $this->pid, false, $this->startTime, 1);
		$this->mockAcquireLock('bar', Cache::FIVE_MINUTES, false, $this->pid, false, $this->startTime, 1);
		$this->mockAcquireLock('nice', Cache::FIVE_MINUTES, false, $this->pid, false, $this->startTime, 1);

		$this->mockReleaseLock('foo', $this->pid, 1);

		$this->mockIsLocked('foo', false, $this->startTime, 1);
		$this->mockIsLocked('bar', true, $this->startTime, 1);
		$this->mockIsLocked('nice', true, $this->startTime, 1);

		$this->mockReleaseAll($this->pid, 1);

		$this->mockIsLocked('bar', false, $this->startTime, 1);
		$this->mockIsLocked('nice', false, $this->startTime, 1);

		parent::testReleaseAfterUnlock();
	}
}
