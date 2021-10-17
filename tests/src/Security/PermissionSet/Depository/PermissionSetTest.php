<?php

namespace Friendica\Test\src\Security\PermissionSet\Depository;

use Dice\Dice;
use Friendica\Database\Database;
use Friendica\DI;
use Friendica\Security\PermissionSet\Depository\PermissionSet as PermissionSetDepository;
use Friendica\Security\PermissionSet\Factory\PermissionSet as PermissionSetFactory;
use Friendica\Test\DatabaseTest;
use Friendica\Test\Util\Database\StaticDatabase;

class PermissionSetTest extends DatabaseTest
{
	/** @var PermissionSetDepository */
	private $depository;
	/** @var PermissionSetFactory */
	private $factory;

	public function setUp(): void
	{
		parent::setUp();

		$dice = (new Dice())
			->addRules(include __DIR__ . '/../../../../../static/dependencies.config.php')
			->addRule(Database::class, ['instanceOf' => StaticDatabase::class, 'shared' => true]);

		$this->depository = $dice->create(PermissionSetDepository::class);
		$this->factory    = $dice->create(PermissionSetFactory::class);
	}

	public function testSelectOneByIdPublic()
	{
		$permissionSet = $this->depository->selectPublicForUser(1);

		$this->assertInstanceOf(\Friendica\Security\PermissionSet\Entity\PermissionSet::class, $permissionSet);
		self::assertEmpty($permissionSet->allow_cid);
		self::assertEmpty($permissionSet->allow_gid);
		self::assertEmpty($permissionSet->deny_cid);
		self::assertEmpty($permissionSet->deny_gid);
		self::assertEmpty(PermissionSetDepository::PUBLIC, $permissionSet->id);
		self::assertEquals(1, $permissionSet->uid);
	}

	/**
	 * Test create/update PermissionSets
	 */
	public function testSaving()
	{
		$this->loadFixture(__DIR__ . '/../../../../datasets/api.fixture.php', DI::dba());

		$permissionSet = $this->factory->createFromString(42, '', '<~>');

		$permissionSet = $this->depository->selectOrCreate($permissionSet);

		self::assertNotNull($permissionSet->id);

		$permissionSetSelected = $this->depository->selectOneById($permissionSet->id, 42);

		self::assertEquals($permissionSet, $permissionSetSelected);

		$newPermissionSet   = $permissionSet->withAllowedContacts(['1', '2']);
		$savedPermissionSet = $this->depository->save($newPermissionSet);

		self::assertNotNull($savedPermissionSet->id);
		self::assertNull($newPermissionSet->id);

		$permissionSetSavedSelected = $this->depository->selectOneById($savedPermissionSet->id, 42);

		self::assertEquals($savedPermissionSet, $permissionSetSavedSelected);
	}
}
