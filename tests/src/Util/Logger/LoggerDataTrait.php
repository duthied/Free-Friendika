<?php

namespace Friendica\Test\src\Util\Logger;

trait LoggerDataTrait
{
	public function dataTests()
	{
		return [
			'emergency' => [
				'function' => 'emergency',
				'message' => 'test',
				'context' => ['a' => 'context'],
			],
			'alert' => [
				'function' => 'alert',
				'message' => 'test {test}',
				'context' => ['a' => 'context', 2 => 'so', 'test' => 'works'],
			],
			'critical' => [
				'function' => 'critical',
				'message' => 'test crit 2345',
				'context' => ['a' => 'context', 'wit' => ['more', 'array']],
			],
			'error' => [
				'function' => 'error',
				'message' => 2.554,
				'context' => [],
			],
			'warning' => [
				'function' => 'warning',
				'message' => 'test warn',
				'context' => ['a' => 'context'],
			],
			'notice' => [
				'function' => 'notice',
				'message' => 2346,
				'context' => ['a' => 'context'],
			],
			'info' => [
				'function' => 'info',
				'message' => null,
				'context' => ['a' => 'context'],
			],
			'debug' => [
				'function' => 'debug',
				'message' => true,
				'context' => ['a' => false],
			],
		];
	}
}
