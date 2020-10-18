<?php
namespace Friendica\Test\src\Database;

use Dice\Dice;
use Friendica\Database\Database;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Test\DatabaseTest;
use Friendica\Test\Util\Database\StaticDatabase;

class DBATest extends DatabaseTest
{
	protected function setUp()
	{
		parent::setUp();

		$dice = (new Dice())
			->addRules(include __DIR__ . '/../../../static/dependencies.config.php')
			->addRule(Database::class, ['instanceOf' => StaticDatabase::class, 'shared' => true]);
		DI::init($dice);

		// Default config
		DI::config()->set('config', 'hostname', 'localhost');
		DI::config()->set('system', 'throttle_limit_day', 100);
		DI::config()->set('system', 'throttle_limit_week', 100);
		DI::config()->set('system', 'throttle_limit_month', 100);
		DI::config()->set('system', 'theme', 'system_theme');
	}

	/**
	 * @small
	 */
	public function testExists() {

		self::assertTrue(DBA::exists('config', []));
		self::assertFalse(DBA::exists('notable', []));

		self::assertTrue(DBA::exists('config', null));
		self::assertFalse(DBA::exists('notable', null));

		self::assertTrue(DBA::exists('config', ['k' => 'hostname']));
		self::assertFalse(DBA::exists('config', ['k' => 'nonsense']));
	}
}
