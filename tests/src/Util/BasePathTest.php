<?php
namespace Friendica\Test\src\Util;

use Friendica\Test\MockedTest;
use Friendica\Util\BasePath;

class BasePathTest extends MockedTest
{
	/**
	 * Test the basepath determination
	 */
	public function testDetermineBasePath()
	{
		$serverArr = ['DOCUMENT_ROOT' => '/invalid', 'PWD' => '/invalid2'];
		$this->assertEquals('/valid', BasePath::create('/valid', $serverArr));
	}

	/**
	 * Test the basepath determination with DOCUMENT_ROOT and PWD
	 */
	public function testDetermineBasePathWithServer()
	{
		$serverArr = ['DOCUMENT_ROOT' => '/valid'];
		$this->assertEquals('/valid', BasePath::create('', $serverArr));

		$serverArr = ['PWD' => '/valid_too'];
		$this->assertEquals('/valid_too', BasePath::create('', $serverArr));
	}
}
