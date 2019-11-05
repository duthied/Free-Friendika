<?php

namespace Friendica\Module;

use Friendica\BaseModule;

/**
 * Return the default robots.txt
 */
class RobotsTxt extends BaseModule
{
	public static function rawContent(array $parameters = [])
	{
		$allDisalloweds = [
			'/settings/',
			'/admin/',
			'/message/',
			'/search',
			'/help',
			'/proxy',
		];

		header('Content-Type: text/plain');
		echo 'User-agent: *' . PHP_EOL;
		foreach ($allDisalloweds as $disallowed) {
			echo 'Disallow: ' . $disallowed . PHP_EOL;
		}
		exit();
	}
}
