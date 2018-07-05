<?php

namespace Friendica\Test\Core\Lock;

use Friendica\App;
use Friendica\Core\Config;
use PHPUnit\Framework\TestCase;

abstract class LockTest extends TestCase
{
	/**
	 * @var \Friendica\Core\Lock\ILockDriver
	 */
	protected $instance;

	abstract protected function getInstance();

	protected function setUp()
	{
		global $a;
		parent::setUp();
		$this->instance = $this->getInstance();

		// Reusable App object
		$this->app = new App(__DIR__.'/../');
		$a = $this->app;

		// Default config
		Config::set('config', 'hostname', 'localhost');
		Config::set('system', 'throttle_limit_day', 100);
		Config::set('system', 'throttle_limit_week', 100);
		Config::set('system', 'throttle_limit_month', 100);
		Config::set('system', 'theme', 'system_theme');
	}

	public function testLock() {
		$this->instance->acquire('foo', 1);
		$this->assertTrue($this->instance->isLocked('foo'));
		$this->assertFalse($this->instance->isLocked('bar'));
	}

	public function testDoubleLock() {
		$this->instance->acquire('foo', 1);
		$this->assertTrue($this->instance->isLocked('foo'));
		// We already locked it
		$this->assertTrue($this->instance->acquire('foo', 1));
	}

	public function testReleaseLock() {
		$this->instance->acquire('foo', 1);
		$this->assertTrue($this->instance->isLocked('foo'));
		$this->instance->release('foo');
		$this->assertFalse($this->instance->isLocked('foo'));
	}

	public function testReleaseAll() {
		$this->instance->acquire('foo', 1);
		$this->instance->acquire('bar', 1);
		$this->instance->acquire('#/$%§', 1);

		$this->instance->releaseAll();

		$this->assertFalse($this->instance->isLocked('foo'));
		$this->assertFalse($this->instance->isLocked('bar'));
		$this->assertFalse($this->instance->isLocked('#/$%§'));
	}

	public function testReleaseAfterUnlock() {
		$this->instance->acquire('foo', 1);
		$this->instance->acquire('bar', 1);
		$this->instance->acquire('#/$%§', 1);

		$this->instance->release('foo');

		$this->instance->releaseAll();

		$this->assertFalse($this->instance->isLocked('bar'));
		$this->assertFalse($this->instance->isLocked('#/$%§'));
	}
}