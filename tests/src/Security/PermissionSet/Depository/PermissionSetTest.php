<?php

namespace Friendica\Test\src\Security\PermissionSet\Depository;

use Friendica\Security\PermissionSet\Depository\PermissionSet as PermissionSetDepository;
use Friendica\Security\PermissionSet\Entity\PermissionSet;
use Friendica\Security\PermissionSet\Factory\PermissionSet as PermissionSetFactory;
use Friendica\Test\FixtureTest;
use Friendica\DI;

class PermissionSetTest extends FixtureTest
{
	/** @var PermissionSetDepository */
	private $depository;
	/** @var PermissionSetFactory */
	private $factory;

	public function setUp(): void
	{
		parent::setUp();

		$this->depository = DI::permissionSet();
		$this->factory    = DI::permissionSetFactory();
	}

	public function testSelectOneByIdPublic()
	{
		$permissionSet = $this->depository->selectPublicForUser(1);

		$this->assertInstanceOf(PermissionSet::class, $permissionSet);
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
