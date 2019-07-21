<?php
namespace Friendica\Test\src\Util;

use Friendica\Test\MockedTest;
use Friendica\Util\BasePath;

class BasePathTest extends MockedTest
{
	public function dataPaths()
	{
		return [
			'fullPath' => [
				'server' => [],
				'input' => dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'config',
				'output' => dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'config',
			],
			'relative' => [
				'server' => [],
				'input' => 'config',
				'output' => dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'config',
			],
			'document_root' => [
				'server' => [
					'DOCUMENT_ROOT' => dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'config',
				],
				'input' => '/noooop',
				'output' => dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'config',
			],
			'pwd' => [
				'server' => [
					'PWD' => dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'config',
				],
				'input' => '/noooop',
				'output' => dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'config',
			],
			'no_overwrite' => [
				'server' => [
					'DOCUMENT_ROOT' => dirname(__DIR__, 3),
					'PWD' => dirname(__DIR__, 3),
				],
				'input' => 'config',
				'output' => dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'config',
			],
			'no_overwrite_if_invalid' => [
				'server' => [
					'DOCUMENT_ROOT' => '/nopopop',
					'PWD' => dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'config',
				],
				'input' => '/noatgawe22fafa',
				'output' => dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'config',
			]
		];
	}

	/**
	 * Test the basepath determination
	 * @dataProvider dataPaths
	 */
	public function testDetermineBasePath(array $server, $input, $output)
	{
		$basepath = new BasePath($input, $server);
		$this->assertEquals($output, $basepath->getPath());
	}

	/**
	 * Test the basepath determination with a complete wrong path
	 * @expectedException \Exception
	 * @expectedExceptionMessageRegExp /(.*) is not a valid basepath/
	 */
	public function testFailedBasePath()
	{
		$basepath = new BasePath('/now23452sgfgas', []);
		$basepath->getPath();
	}
}
