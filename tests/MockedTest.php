<?php

namespace Friendica\Test;

use PHPUnit\Framework\TestCase;

/**
 * This class verifies each mock after each call
 */
abstract class MockedTest extends TestCase
{
	protected function tearDown()
	{
		\Mockery::close();

		parent::tearDown();
	}
}
