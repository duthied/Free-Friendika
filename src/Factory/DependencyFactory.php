<?php

namespace Friendica\Factory;

use Dice\Dice;
use Friendica\App;
use Friendica\Core\Config\PConfiguration;
use Psr\Log\LoggerInterface;

class DependencyFactory
{
	/**
	 * Setting all default-dependencies of a friendica execution
	 *
	 * @param string $channel   The channel of this execution
	 * @param bool   $isBackend True, if it's a backend execution, otherwise false (Default true)
	 *
	 * @return App The application
	 *
	 * @throws \Exception
	 */
	public static function setUp($channel, Dice $dice, $isBackend = true)
	{
		$pConfig = $dice->create(PConfiguration::class);
		$logger = $dice->create(LoggerInterface::class, [$channel]);
		$devLogger = $dice->create('$devLogger', [$channel]);

		return $dice->create(App::class, [$isBackend]);
	}
}
