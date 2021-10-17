<?php

namespace Friendica\Test\src\Security\PermissionSet\Depository;

use Dice\Dice;
use Friendica\Database\Database;
use Friendica\DI;
use Friendica\Security\PermissionSet\Depository\PermissionSet;
use Friendica\Test\MockedTest;
use Friendica\Test\Util\Database\StaticDatabase;

class PermissionSetTest extends MockedTest
{
	/** @var PermissionSet */
	private $depository;

	public function setUp(): void
	{
		$dice = (new Dice())
			->addRules(include __DIR__ . '/../../../../../static/dependencies.config.php')
			->addRule(Database::class, ['instanceOf' => StaticDatabase::class, 'shared' => true]);
		DI::init($dice);

		$this->depository = DI::permissionSet();
	}

	public function testSelectOneByIdPublicMissingUid()
	{
		$this->expectException(\InvalidArgumentException::class);

		$this->depository->selectOneById(PermissionSet::PUBLIC);
	}

	public function testSelectOneByIdPublic()
	{
		$permissionSet = $this->depository->selectOneById(PermissionSet::PUBLIC, 1);

		$this->assertInstanceOf(\Friendica\Security\PermissionSet\Entity\PermissionSet::class, $permissionSet);
	}
}
